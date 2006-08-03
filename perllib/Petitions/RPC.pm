#!/usr/bin/perl
#
# Petitions/RPC.pm:
# Crazy RPC protocol for the petitions site.
#
# Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#
# $Id: RPC.pm,v 1.6 2006-08-03 09:38:22 chris Exp $
#

package Petitions::RPC;

use strict;

use Carp;
use Digest::HMAC_SHA1 qw(hmac_sha1);
use Errno;
use Error qw(:try);
use IO::Select;
use IO::Socket;
use IO::String;
use RABX;
use Socket;
use Time::HiRes qw(time alarm sleep);

use mySociety::Config;
use mySociety::DBHandle qw(dbh);
use mySociety::Util qw(random_bytes);

use Petitions;

use constant HMAC_SHA1_LEN => 20;

# Total amount of time we wait until our request is acknowledged.
use constant RPC_TIMEOUT => 2;

# Retry schedule for the RPC packets. The back end caches completed requests
# so we can be reasonably aggressive with this.
use constant RPC_RETRY_TIME => 0.1;
use constant RPC_RETRY_EXP => 1.5;

# Bytes of random data in request ID.
use constant COOKIE_LEN => 4;

# make_packet R
#
sub make_packet ($) {
    my $r = shift;
    my $packet = '';
    my $h = new IO::String($packet);
    RABX::wire_wr($r, $h);
    $h->close();
    my $hmac = hmac_sha1($packet, Petitions::DB::secret());
    return $packet . $hmac;
}

=item make_sign_packet REQUEST

Given a REQUEST (to sign a petition) make a signed packet suitable for
transmitting to the server.

=cut
sub make_sign_packet ($) {
    my $r = shift;
    croak "REQUEST must be reference-to-hash" unless (ref($r) eq 'HASH');
    my @required = qw(cookie ref email name address postcode);
    my @missing = grep { !exists($r->{$_}) } @required;
    croak "REQUEST is missing fields " . join(", ", @missing)
        if (@missing);

    return make_packet($r);;
}

=item make_ack_packet COOKIE

Given the COOKIE from a request, return an ack packet.

=cut
sub make_ack_packet ($) {
    my $c = shift;
    croak "COOKIE may not be undef" unless (defined($c));
    croak "COOKIE must be scalar" unless (ref($c) eq '');

    return make_packet($c);
}


=item parse_packet PACKET

Given an on-the-wire PACKET, parse it.

=cut
sub parse_packet ($) {
    my $packet = shift;
    # Check packet is long enough.
    return undef if (length($packet) < 4 + HMAC_SHA1_LEN);
    # Check signature.
    my $hmac = hmac_sha1(substr($packet, 0, length($packet) - HMAC_SHA1_LEN), Petitions::DB::secret());
    return undef if ($hmac ne substr($packet, length($packet) - HMAC_SHA1_LEN));
    # Decode.
    my $h = new IO::String($packet);
    my $r;
    try {
        $r = RABX::wire_rd($h);
    } catch RABX::Error with {
        $r = undef;
    };
    return $r;
}

=item sign_petition_db REQUEST

Sign a petition using REQUEST in the normal way. Does not commit. Must be
called while holding a lock against concurrent signatures (e.g. row exclusive).

=cut
sub sign_petition_db ($) {
    my $r = shift;
    
    # First try updating the row.
    my $n = dbh()->do("
            update signer set emailsent = 'pending'
            where petition_id = (select id from petition where ref = ?)
                and email = ? and emailsent <> 'confirmed'", {},
            map { $r->{$_} } qw(ref email));

    return if ($n > 0);

    dbh()->do('
            insert into signer (
                petition_id,
                email, name, address, postcode,
                showname,
                signtime
            ) values (
                (select id from petition where ref = ?),
                ?, ?, ?, ?,
                true,
                ms_current_timestamp()
            )', {},
            map { $r->{$_} } qw(ref email name address postcode));
}

=item sign_petition REQUEST

Sign a petition using REQUEST by talking to the server over UDP. If this fails
then sign up using the database instead. REQUEST must contain keys 'ref', 
'email', 'name', 'address' and 'postcode'.

=cut
sub sign_petition ($) {
    my $r = shift;

    our $have_rpc_server;
    our $serveraddr;
    if (!defined($have_rpc_server)) {
        my $host = mySociety::Config::get('RPC_SERVER_HOST', '');
        my $port = mySociety::Config::get('RPC_SERVER_PORT', 0);

        if ($host && $port) {
            $have_rpc_server = 1;
            $serveraddr = sockaddr_in($port, inet_aton($host));
        } else {
            $have_rpc_server = 0;
        }
    }
    
    our $s;
    if (!$s && $have_rpc_server) {
        $s = new IO::Socket::INET(
                        LocalAddr => '0.0.0.0',
                        Type => SOCK_DGRAM,
                        Proto => 'udp',
                        ReuseAddr => 0,
                        Blocking => 0)
            or warn "socket: $!";
    }

    if ($s && $have_rpc_server) {
        $r->{cookie} = pack('N', int(rand(0xffffffff))); #random_bytes(COOKIE_LEN);
        my $packet = make_sign_packet($r);

        my $interval = RPC_RETRY_TIME;
        my $alarmfired = 0;
        local $SIG{ALRM} = sub { $alarmfired = 1; };
        alarm(RPC_TIMEOUT);
        my $t0 = time();
        my $n;
        do {
            $n = $s->send($packet, 0, $serveraddr);
        } while (!defined($n) && !$!{EINTR});
        while (!$alarmfired && IO::Select->new($s)->can_read($interval)) {
            my $ack = '';
            my $sender;
            do {
                $sender = $s->recv($ack, 1024, 0);
            } while (!$sender && $!{EINTR});
            if (!$sender) {
                if ($!{EAGAIN}) {
                    next;
                } else {
                    alarm(0);
                    last;
                }
            }

            my $cookie = parse_packet($ack);
            if ($cookie && $cookie eq $r->{cookie}) {
                # Success.
                alarm(0);
                return;
            } elsif ($ack) {
                warn "got a response packet after " . (time() - $t0) . "s but it was bad; ignoring it";
                if ($cookie) {
                    warn "sent cookie = "
                        . unpack('h*', $r->{cookie})
                        . "; received cookie = "
                        . unpack('h*', $cookie);
                }
            }

            $interval *= RPC_RETRY_EXP;
        }
        alarm(0);

        warn "unable to sign petition over RPC, using database instead";
    }

    my $d = dbh();
    sign_petition_db($r);
    dbh()->commit();
    dbh()->disconnect();
}

1;
