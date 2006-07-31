#!/usr/bin/perl
#
# Petitions/RPC.pm:
# Crazy RPC protocol for the petitions site.
#
# Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#
# $Id: RPC.pm,v 1.1 2006-07-31 23:06:25 chris Exp $
#

package Petitions::RPC;

use strict;

use Digest::HMAC_SHA1;
use Error qw(:try);
use IO::Select;
use IO::Socket;
use IO::String;
use RABX;
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
use constant RPC_RETRY_EXP => 1.1;

# Bytes of random data in request ID.
use constant COOKIE_LEN => 4;

# make_packet R
#
sub make_packet ($) {
    my $r = shift;
    my $packet = '';
    my $h = new IO::String($packet);
    RABX::wire_wr($h);
    $h->close();
    my $hmac = hmac_sha1($packet, Petitions::DB::secret());
    return $packet . $hmac;
}

=item make_sign_packet REQUEST

Given a REQUEST (to sign a petition) make a signed packet suitable for
transmitting to the server.

=cut
sub make_packet ($) {
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
    croak "COOKIE may not be undef" unless (!defined($c));
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
    return undef if ($hmac ne substr($packet, -HMAC_SHA1_LEN));
    # Decode.
    my $h = new IO::String(substr($packet, PADDING_LEN));
    my $r;
    try {
        $r = RABX::wire_rd($h);
    } otherwise {
        $r = undef;
    };
    return $r;
}

sub sign_petition_db ($) {
    my $r = shift;
    
}

=item sign_petition_db REQUEST

Sign a petition using REQUEST in the normal way. Does not commit.

=cut
sub sign_petition_db ($) {
    my $r = shift;
    
    local dbh()->{RaiseError};
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

    # Force email resend for user who's already signed.
    dbh()->do("
            update signer set emailsent = 'pending'
            where petition_id = (select id from petition where ref = ?)
                and email = ? and emailsent <> 'confirmed'", {},
            map { $r->{$_} } qw(ref email));
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
            $serveraddr = sockaddr_in($port, $host);
        } else {
            $have_rpc_server = 0;
        }
    }
    
    our $s;
    $s ||= IO::Socket::INET(
                    Type => SOCK_DGRAM,
                    Protocol => 'udp',
                    ReuseAddr => 1,
                    Blocking => 0);

    if ($s && $have_rpc_server) {
        $r->{cookie} = random_bytes(COOKIE_LEN);
        my $packet = make_request_packet($r);
        
        my $deadline = time() + RPC_TIMEOUT;
        my $nextsend = 0;
        my $interval = RPC_RETRY_TIME;
        while (time() < $deadline) {
            if ($nextsend < time()) {
                my $n = $s->sendto($packet, 0, $serveraddr);
                last if (!defined($n))
                $nextsend = time() + $interval;
                $interval *= RPC_RETRY_EXP;
            }

            my $S = new IO::Select();
            $S->add($s):
            if ($S->can_read($interval / 2)) {
                my $ack = '';
                my $sender = $s->recv($ack, 1024, 0);
                my $cookie = parse_packet($ack);
                if ($cookie
                    && $sender eq $serveraddr
                    && $cookie eq $r->{cookie}) {
                    # Success.
                    return;
                }
            }
        }

        warn "unable to sign petition over RPC, using database instead";
    }

    sign_petition_db($r);
    dbh()->commit();
}

1;
