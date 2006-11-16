#!/usr/bin/perl
#
# Petitions.pm:
# Petition utilities.
#
# Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#
# $Id: Petitions.pm,v 1.30 2006-11-16 11:19:30 matthew Exp $
#

package Petitions::DB;

use strict;

use Carp;
use DBI;

use mySociety::Config;
use mySociety::DBHandle qw(dbh);
use mySociety::Util qw(random_bytes print_log is_valid_email);

my $secret;

BEGIN {
    mySociety::DBHandle::configure(
            Name => mySociety::Config::get('PET_DB_NAME'),
            User => mySociety::Config::get('PET_DB_USER'),
            Password => mySociety::Config::get('PET_DB_PASS'),
            Host => mySociety::Config::get('PET_DB_HOST', undef),
            Port => mySociety::Config::get('PET_DB_PORT', undef),
            OnFirstUse => sub {
                if (!dbh()->selectrow_array('select secret from secret')) {
                    local dbh()->{HandleError};
                    dbh()->do('insert into secret (secret) values (?)',
                                {}, unpack('h*', random_bytes(32)));
                    dbh()->commit();
                }
            }
        );
}

=item secret

Return the site shared secret.

=cut
sub secret () {
    $secret ||= dbh()->selectrow_array('select secret from secret');
    return $secret;
}

=item today

Return today's date.

=cut
sub today () {
    return scalar(dbh()->selectrow_array('select ms_current_date()'));
}

=item check_ref REFERENCE

Given a petition REFERENCE, return its canonical reference, or undef if there
is none.

=cut
sub check_ref ($) {
    my $ref = shift;
    return undef if (!defined($ref) || $ref !~ /^[A-Za-z0-9-]{6,16}$/);
    if (dbh()->selectrow_array("
                select ref from petition
                where status in ('live', 'rejected', 'finished')
                and ref = ?", {}, $ref)
        || defined($ref = dbh()->selectrow_array("
                select ref from petition
                where status in ('live', 'rejected', 'finished')
                and ref ilike ?", {}, $ref))) {
        return $ref;
    } else {
        return undef;
    }
}

=item get REF [NOCOUNT] [GOVTRESPONSE]

=item get ID [NOCOUNT] [GOVTRESPONSE]

Return a hash of database fields to values for the petition with the given REF
or ID, or undef if there is no such petition. Also make up a signers field
giving the number of people who've signed the petition so far, unless NOCOUNT
is set, and try and return the Government response if GOVTRESPONSE is set.

=cut
sub get ($;$$) {
    my $ref = shift;
    return undef unless ($ref);
    my $nocount = shift;
    my $govtresponse = shift;
    my $s = "
            select petition.*,
                ms_current_date() <= deadline as open";
    $s .= ", message.id AS message_id, message.emailbody as response" if ($govtresponse);
    $s .= ", (select count(id) from signer
                    where showname and signer.petition_id = petition.id
                        and signer.emailsent = 'confirmed')
                    as signers" unless ($nocount);
    $s .= "
            from petition";
    $s .= " left join message on petition.id = message.petition_id and circumstance = 'government-response'"
        if ($govtresponse);
    my $p;
    $p ||= dbh()->selectrow_hashref("$s where id = ?", {}, $ref)
            if ($ref =~ /^[1-9]\d*$/);
    $p ||= dbh()->selectrow_hashref("$s where ref = ?", {}, $ref);
    $p ||= dbh()->selectrow_hashref("$s where ref ilike ?", {}, $ref);
    return $p;
}

=item is_valid_to_sign PETITION EMAIL

Test whether the user with the given EMAIL may sign PETITION. Returns one of
'ok', meaning they may; 'none', if the petition does not exist, 'finished' if
it has expired, or 'signed' if they have already signed it.

=cut
sub is_valid_to_sign ($$) {
    my ($id, $email) = @_;
    $id = $id->{id} if (ref($id));
    return scalar(dbh()->selectrow_array('select petition_is_valid_to_sign(?, ?)', {}, $id, $email));
}

package Petitions::Token;

use strict;

use Crypt::Blowfish;
use Digest::HMAC_SHA1 qw(hmac_sha1);
use Digest::SHA1 qw(sha1);
use MIME::Base64;

use mySociety::DBHandle ();
use mySociety::Util qw(random_bytes);

sub encode_base64ish ($) {
    my $b64 = encode_base64($_[0]);
    $b64 =~ s#\+#\$#g;
    $b64 =~ s#=+$##;
    return $b64;
}

sub decode_base64ish ($) {
    my $b64 = shift;
    while (length($b64) % 4) {
        $b64 .= '=';
    }
    $b64 =~ s#_|\$#+#g;
    $b64 =~ s#-|'#/#g;
    return decode_base64($b64);
}

use constant TOKEN_LENGTH => 15;
use constant TOKEN_LENGTH_B64 => 20;

=item make WHAT ID

Make a token identifying the given ID (of a petition or signer). WHAT indicates
what is identified and the context in which it is identified; 'p' means a
petition for confirmation; 's' means a signer for confirmation; and 'e' means a
petition for editing after a first rejection.

=cut
sub make ($$) {
    my ($what, $id) = @_;
    croak("WHAT must be 'p', 's' or 'e'")
        unless ($what =~ /^[pse]/);
    $what = substr($what, 0, 1);
    croak("ID must be a positive integer")
        unless (defined($id) && $id =~ /^[1-9]\d*$/);

    warn "ID '$id' is quite large; the token format may have to be expanded soon"
        if ($id > 0x10000000);

    my @salt = unpack('C4', random_bytes(4));
    # Top two bits of first byte of salt encode WHAT.
    # 00        p
    # 01        s
    # 10        e
    # 11        reserved
    $salt[0] &= 0x3f;
    $salt[0] |= 0x40 if ($what eq 's');
    $salt[0] |= 0x80 if ($what eq 'e');
    
    my $plaintext = pack('C4N', @salt, $id);
    my $hmac = hmac_sha1($plaintext, Petitions::DB::secret());
    
        # XXX is this safe or ought we to have two different secrets?
    our $crypt ||= new Crypt::Blowfish(substr(sha1(Petitions::DB::secret()), 0, 8));
    my $ciphertext = $crypt->encrypt($plaintext);

    # 8 bytes of ciphertext plus 7 bytes of HMAC gives 15 bytes, 20 chars
    # base64.
    return encode_base64ish($ciphertext . substr($hmac, 0, 7));
}

=item check TOKEN

Check the validity of a TOKEN. Returns in list context the WHAT and ID that
were passed to make; or, if TOKEN is invalid, the empty list.

=cut
sub check ($) {
    my $token = shift;
    croak("TOKEN must be defined") unless (defined($token));
    return () if (length($token) != TOKEN_LENGTH_B64);

    my $data = decode_base64ish($token);
    return () unless ($data);

    my $ciphertext = substr($data, 0, 8);
    my $hmac7 = substr($data, 8, 7);
    
    our $crypt ||= new Crypt::Blowfish(substr(sha1(Petitions::DB::secret()), 0, 8));
    my $plaintext = $crypt->decrypt($ciphertext);
    my $hmac = hmac_sha1($plaintext, Petitions::DB::secret());

    return () unless ($hmac7 eq substr($hmac, 0, 7));

    # ugh. What I want is my (@salt[0 .. 3], $id) = unpack(...), but that
    # doesn't work, at least as written.
    my (@salt) = unpack('C4N', $plaintext);
    my $id = pop(@salt);

    return () if (($salt[0] & 0xc0) == 0xc0);   # reserved for future

    my $what;
    if ($salt[0] & 0x40) {
        $what = 's';
    } elsif ($salt[0] & 0x80) {
        $what = 'e';
    } else {
        $what = 'p';
    }

    return ($what, $id);
}

package Petitions;

use strict;

use Carp;
use POSIX qw();

use mySociety::DBHandle qw(dbh);
use mySociety::Util;
use mySociety::Web qw(ent);

my $petition_prefix = "We the undersigned petition the Prime Minister to";

=item sentence PETITION [HTML]

=cut
sub sentence ($;$) {
    my ($p, $html) = @_;
    croak("PETITION may not be undef") unless (defined($p));
    croak("PETITION must be a hash of db fields") unless (ref($p) eq 'HASH');
    croak("Field 'content' missing from PETITION") unless (exists($p->{content}));
    my $sentence = sprintf('%s %s', $petition_prefix, $p->{content});
    $sentence = 'This petition cannot be shown' unless Petitions::show_part($p, 'content');
    $sentence = ent($sentence) if ($html);
    return $sentence;
}

=item detail PETITION

=cut
sub detail ($) {
    my ($p) = @_;
    croak("PETITION may not be undef") unless (defined($p));
    croak("PETITION must be a hash of db fields") unless (ref($p) eq 'HASH');
    croak("Field 'detail' missing from PETITION") unless (exists($p->{detail}));
    my $detail = Petitions::show_part($p, 'detail') ? ent($p->{detail}) : 'More details cannot be shown';
    $detail =~ s/\r//g;
    $detail =~ s/\n\n+/<\/p> <p>/g;
    if ($detail) {
        $detail = <<EOF;
<div style="float: right; width:45%">
<h2><span dir="ltr">More details from petition creator</span></h2>
<p>$detail</p></div>
EOF
    }
    return $detail;
}

=item show_part PETITION PART

=cut
sub show_part ($$) {
    my ($p, $part) = @_;
    my %map = ('ref'=>1, 'content'=>2, 'detail'=>4,
        'name'=>8, 'organisation'=>16, 'org_url'=>32);
    return 0 if $p->{'rejection_hidden_parts'} & $map{$part};
    return 1;
}

=item pretty_deadline PETITION [HTML]

=cut
sub pretty_deadline ($;$) {
    my ($p, $html) = @_;
    croak("PETITION may not be undef") unless (defined($p));
    croak("PETITION must be a hash of db fields") unless (ref($p) eq 'HASH');
    my ($Y, $m, $d) = split(/-/, $p->{deadline});

    my @months = qw(x January February March April May June July August September October November December);   # XXX lazy
    my $monthyear = "$months[$m] $Y";
    $monthyear = ent($monthyear) if ($html);
    
    return "$d $monthyear";
}

use constant MSG_ADMIN => 1;
use constant MSG_CREATOR => 2;
use constant MSG_SIGNERS => 4;
use constant MSG_ALL => MSG_ADMIN | MSG_CREATOR | MSG_SIGNERS;

=item send_message ID SENDER RECIPIENTS CIRCUMSTANCE TEMPLATE

Send a message to the RECIPIENTS in respect of the petition with the given ID,
constructing it from the given email TEMPLATE. RECIPIENTS should be the bitwise
combination of one or more of MSG_ADMIN, MSG_CREATOR and MSG_SIGNERS. The
message will appear to come from SENDER, which must be MSG_ADMIN or
MSG_CREATOR; CIRCUMSTANCE indicates the reason for its sending.

=cut
sub send_message ($$$$$) {
    my ($id, $sender, $recipients, $circumstance, $template) = @_;

    croak "ID must be a positive integer"
        unless (defined($id) && $id =~ /^[1-9]\d*$/);
    croak "SENDER must be MSG_ADMIN or MSG_CREATOR"
        unless (defined($sender) && ($sender == MSG_ADMIN || $sender == MSG_CREATOR));
    croak "RECIPIENTS must be a combination of MSG_ADMIN, MSG_CREATOR and MSG_SIGNERS"
        unless (defined($recipients) && $recipients =~ /^[1-9]\d*$/
                    && !($recipients & ~MSG_ALL));

    dbh()->do("
            insert into message(
                petition_id,
                circumstance,
                circumstance_count,
                fromaddress,
                sendtoadmin, sendtocreator, sendtosigners, sendtolatesigners,
                emailtemplatename
            ) values (
                ?,
                ?,
                coalesce((select max(circumstance_count)
                            from message where petition_id = ?
                                and circumstance = ?), 0) + 1,
                ?,
                ?, ?, ?, 'f', -- XXX
                ?
            )", {},
            $id,
            $circumstance,
                $id,
                $circumstance,
            $sender == MSG_ADMIN ? 'number10' : 'creator',
            (map { $recipients & $_ } (MSG_ADMIN, MSG_CREATOR, MSG_SIGNERS)),
            $template);
}


1;
