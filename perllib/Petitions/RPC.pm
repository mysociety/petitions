#!/usr/bin/perl
#
# Petitions/RPC.pm:
# Crazy RPC protocol for the petitions site.
#
# Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#
# $Id: RPC.pm,v 1.45 2010-03-12 19:02:56 matthew Exp $
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
use mySociety::Random qw(random_bytes);

use Petitions;

use constant HMAC_SHA1_LEN => 20;

# Total amount of time we wait until our request is acknowledged.
use constant RPC_TIMEOUT => 20;

# Retry schedule for the RPC packets. The back end caches completed requests
# so we can be reasonably aggressive with this.
use constant RPC_RETRY_TIME => 0.1;
use constant RPC_RETRY_EXP => 1.25;

# Bytes of random data in request ID.
use constant COOKIE_LEN => 6;

=item make_packet REQUEST

Given a REQUEST, make a signed packet expressing it.

=cut
sub make_packet ($) {
    my $r = shift;
    croak "REQUEST must not be undef" unless (defined($r));
    my $packet = RABX::serialise($r);
    my $hmac = hmac_sha1($packet, Petitions::DB::secret());
    return $packet . $hmac;
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
    my $r;
    try {
        $r = RABX::unserialise($packet);
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
    
    my $s = dbh()->selectrow_array("
            select emailsent from signer
            where petition_id = (select id from petition where ref = ?)
                and email!='' and lower(email) = lower(?)", {}, map { $r->{$_} } qw(ref email));
    return if defined($s) && $s eq 'pending';
    
    # First try updating the row.
    if (defined($s) && $s eq 'confirmed') {
        dbh()->do("
                update signer set emailsent = 'duplicate'
                where petition_id = (select id from petition where ref = ?)
                    and lower(email) = lower(?)", {},
                map { $r->{$_} } qw(ref email));
    } elsif (defined($s)) {
        dbh()->do("
                update signer set emailsent = 'pending', signtime = ms_current_timestamp()
                where petition_id = (select id from petition where ref = ?)
                    and lower(email) = lower(?) and emailsent <> 'confirmed'", {},
                map { $r->{$_} } qw(ref email));
    } else {
        dbh()->do('
                insert into signer (
                    petition_id,
                    email, name, address, postcode,
                    address_type, overseas,
                    showname, receive_updates,
                    signtime
                ) values (
                    (select id from petition where ref = ?),
                    ?, ?, ?, ?, ?, ?,
                    true, ?,
                    ms_current_timestamp()
                )', {},
                map { $r->{$_} } qw(ref email name address postcode address_type overseas receive_updates));
    }
}

=item confirm_db REQUEST [SIGNERCOUNT]

Confirm a signature or petition creation according to REQUEST. If the reference
to hash SIGNERCOUNT is passed, record in the element of the hash for the
petition ID corresponding to a confirmed signature the added signature count.

=cut
sub confirm_db ($;$) {
    my ($r, $signercount) = @_;

    if ($r->{confirm} eq 'p') {
        # never move a petition backwards in status...
        my $status = mySociety::Config::get('SITE_APPROVAL') ? 'draft' : 'live';
        my $n = dbh()->do("
                update petition set status = '$status', laststatuschange = ms_current_timestamp(),
                deadline=deadline+(ms_current_date()-date_trunc('day', creationtime))
                where id = ? and status = 'sentconfirm'", {},
                $r->{id});
        Petitions::send_message(
                $r->{id},
                Petitions::MSG_ADMIN,
                Petitions::MSG_ADMIN,
                'created',
                'admin-new-petition'
            ) if ($n > 0 && $status eq 'draft');
    } elsif ($r->{confirm} eq 's') {
        my ($petition_id, $creator_email, $signer_email) = dbh()->selectrow_array("
                select petition.id, petition.email, signer.email from petition, signer
                where petition.id = signer.petition_id
                  and signer.id = ?", {}, $r->{id});
        return if $creator_email eq $signer_email;
        my $n = dbh()->do("
                update signer set emailsent = 'confirmed'
                where id = ? and emailsent in ('sent', 'pending')", {},
                $r->{id});
        if ($signercount && $n > 0) {
            $signercount->{$petition_id} += $n;
        }
    }
}

=item do_rpc REQUEST

Submit an RPC REQUEST to the server, returning true if it is positively
acknowledged and false otherwise. REQUEST should be a reference to a hash of
fields which are sent to the server; this function will add a unique
identifying cookie to distinguish it from other requests.

=cut
sub do_rpc ($) {
    my $r = shift;
    croak("REQUEST should be reference to hash") unless (defined($r) && ref($r) eq 'HASH');

    our $s;
    our $serveraddr;
    if (!$serveraddr) {
        my $host = mySociety::Config::get('PETSIGNUPD_HOST');
        my $port = mySociety::Config::get('PETSIGNUPD_PORT');
        $serveraddr = sockaddr_in($port, inet_aton($host))
            or die "sockaddr_in($port, '$host'): $!";
    }

    if (!$s) {
        $s ||= new IO::Socket::INET(
                        LocalAddr => '0.0.0.0',
                        Type => SOCK_DGRAM,
                        Proto => 'udp',
                        ReuseAddr => 0,
                        Blocking => 0)
            or die "socket: $!";
    }

    # Cookie is only exposed on the internal network so it only needs to be
    # unpredictable, not securely random. Probably we could just use rand in
    # fact.
    $r->{cookie} = random_bytes(COOKIE_LEN, 1);

    my $packet = make_packet($r);

    my $interval = RPC_RETRY_TIME;
    my $alarmfired = 0;
    local $SIG{ALRM} = sub { $alarmfired = 1; };
    alarm(RPC_TIMEOUT);

my @sent = ( );

    while (!$alarmfired) {
        # Send request.
        my $n;
        do {
            $n = $s->send($packet, 0, $serveraddr);
        } while (!defined($n) && $!{EINTR});
        # XXX handle transmission errors?
push(@sent, time());
        
        if (IO::Select->new($s)->can_read($interval)) {
            my $ack = '';
            my $sender;
            do {
                $sender = $s->recv($ack, 1024, 0);
            } while (!$sender && $!{EINTR});
            # XXX errors?
    
            if ($sender) {
                my ($port, $host) = sockaddr_in($sender);
                $host = inet_ntoa($host);
                my $cookie = parse_packet($ack);
                if ($cookie) {
                    if ($cookie eq $r->{cookie}) {
                        alarm(0);
                        return 1;
                    } # else it's an old ack, presumably; just ignore it
                } else {
                    warn "received invalid packet from $host:$port";
                }
            }
        }

        $interval *= RPC_RETRY_EXP;
    }

warn "no RPC response; packets sent at: "
        . join(", ", @sent) . "; timed out at " . time()
        . "; cookie = " . unpack('h*', $r->{cookie}) . "\n";

    return 0;
}

1;
