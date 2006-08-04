#!/usr/bin/perl
#
# Petitions.pm:
# Petition utilities.
#
# Copyright (c) 2006 Chris Lightfoot. All rights reserved.
# Email: chris@ex-parrot.com; WWW: http://www.ex-parrot.com/~chris/
#
# $Id: Petitions.pm,v 1.17 2006-08-04 00:20:23 chris Exp $
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

=item get REF [NOCOUNT]

=item get ID [NOCOUNT]

Return a hash of database fields to values for the petition with the given REF
or ID, or undef if there is no such petition. Also make up a signers field
giving the number of people who've signed the petition so far, unless NOCOUNT
is set.

=cut
sub get ($;$) {
    my $ref = shift;
    return undef unless ($ref);
    my $nocount = shift;
    my $s = "
            select *,
                ms_current_date() <= deadline as open";
    $s .= ", (select count(id) from signer
                    where showname and signer.petition_id = petition.id
                        and signer.emailsent = 'confirmed')
                    as signers" unless ($nocount);
    $s .= "
            from petition";
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

use Crypt::IDEA;
use Digest::HMAC_SHA1 qw(hmac_sha1);
use Digest::SHA1 qw(sha1);
use MIME::Base64;

use mySociety::DBHandle ();
use mySociety::Util qw(random_bytes);

sub encode_base64ish ($) {
    my $b64 = encode_base64($_[0]);
    $b64 =~ s#\+#_#g;
    $b64 =~ s#/#-#g;
    $b64 =~ s#=+$##;
    return $b64;
}

sub decode_base64ish ($) {
    my $b64 = shift;
    while (length($b64) % 4) {
        $b64 .= '=';
    }
    $b64 =~ s#_#+#g;
    $b64 =~ s#-#/#g;
    return decode_base64($b64);
}

use constant TOKEN_LENGTH => 15;
use constant TOKEN_LENGTH_B64 => 20;

=item make WHAT ID

=cut
sub make ($$) {
    my ($what, $id) = @_;
    croak("WHAT must be 'p' or 's'")
        unless ($what =~ /^[ps]/);
    $what = substr($what, 0, 1);
    croak("ID must be a positive integer")
        unless (defined($id) && $id =~ /^[1-9]\d*$/);

    warn "ID '$id' is quite large; the token format may have to be expanded soon"
        if ($id > 0x10000000);

    my @salt = unpack('C4', random_bytes(4));
    # Top two bits of first byte of salt encode WHAT.
    # 00        p
    # 01        s
    # 1x        reserved
    $salt[0] &= 0x3f;
    $salt[0] |= 0x40 if ($what eq 's');
    
    my $plaintext = pack('C4N', @salt, $id);
    my $hmac = hmac_sha1($plaintext, Petitions::DB::secret());
    
        # XXX is this safe or ought we to have two different secrets?
    our $crypt ||= new IDEA(substr(sha1(Petitions::DB::secret()), 0, IDEA::keysize()));
    my $ciphertext = $crypt->encrypt($plaintext);

    # 8 bytes of ciphertext plus 7 bytes of HMAC gives 15 bytes, 20 chars
    # base64.
    my $token = encode_base64ish($ciphertext . substr($hmac, 0, 7));
}

=item check TOKEN

=cut
sub check ($) {
    my $token = shift;
    croak("TOKEN must be defined") unless (defined($token));
    return () if (length($token) != TOKEN_LENGTH_B64);

    my $data = decode_base64ish($token);
    return () unless ($data);
    
    my $ciphertext = substr($data, 0, 8);
    my $hmac7 = substr($data, 8, 7);
    
    our $crypt ||= new IDEA(substr(sha1(Petitions::DB::secret()), 0, IDEA::keysize()));
    my $plaintext = $crypt->decrypt($ciphertext);
    my $hmac = hmac_sha1($plaintext, Petitions::DB::secret());

    return () unless ($hmac7 eq substr($hmac, 0, 7));

    # ugh. What I want is my (@salt[0 .. 3], $id) = unpack(...), but that
    # doesn't work, at least as written.
    my (@salt) = unpack('C4N', $plaintext);
    my $id = pop(@salt);

    return () if ($salt[0] & 0x80);     # reserved for future
    my $what = ($salt[0] & 0x40) ? 's' : 'p';

    return ($what, $id);
}

package Petitions;

use strict;

use Carp;
use POSIX qw();

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
    $sentence = ent($sentence) if ($html);
    return $sentence;
}

=item pretty_deadline PETITION [HTML]

=cut
sub pretty_deadline ($;$) {
    my ($p, $html) = @_;
    croak("PETITION may not be undef") unless (defined($p));
    croak("PETITION must be a hash of db fields") unless (ref($p) eq 'HASH');
    my ($Y, $m, $d) = split(/-/, $p->{deadline});
    my $day = mySociety::Util::ordinal($d);
    $day =~ s#^(\d+)(.+)#sprintf('%s<sup>%s</sup>', $1, ent($2))#e
        if ($html);

    my @months = qw(x January February March April May June July August September October November December);   # XXX lazy
    my $monthyear = "$months[$m] $Y";
    $monthyear = ent($monthyear) if ($html);
    
    return "$day $monthyear";
}



1;
