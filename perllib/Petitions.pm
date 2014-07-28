#!/usr/bin/perl
#
# Petitions.pm:
# Petition utilities.
#
# Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#
# $Id: Petitions.pm,v 1.60 2010-03-12 00:06:37 matthew Exp $
#

package Petitions::DB;

use strict;

use Carp;
use DBI;

use mySociety::Config;
use mySociety::DBHandle qw(dbh select_all);
use mySociety::Memcached;
mySociety::Memcached::set_config(mySociety::Config::get('MEMCACHE_HOST'), mySociety::Config::get('PET_DB_NAME'));
use mySociety::Random qw(random_bytes);
use Petitions::Cobrand;

my $secret;

my %petition_categories = (
    0 => 'None',
    692 => 'Business and industry',
    726 => 'Economics and finance',
    439 => 'Education and skills',
    981 => 'Employment, jobs and careers',
    499 => 'Environment',
    760 => 'Government, politics and public administration',
    557 => 'Health, well-being and care',
    460 => 'Housing',
    758 => 'Information and communication',
    911 => 'International affairs and defence',
    616 => 'Leisure and culture',
    642 => 'Life in the community',
    6999 => 'People and organisations',
    564 => 'Public order, justice and rights',
    652 => 'Science, technology and innovation',
    521 => 'Transport and infrastructure'
);

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

=item check_ref BODY_REF REFERENCE

Given a petition REFERENCE and BODY_REF (can be undef), return its canonical
reference, or undef if there is none.

=cut
sub check_ref ($$) {
    my ($body, $ref) = @_;
    return undef if (!defined($ref) || $ref !~ /^[A-Za-z0-9-]{6,16}$/);
    my @row;
    if (mySociety::Config::get('SITE_TYPE') eq 'multiple') {
        if (@row = dbh()->selectrow_array("
                select petition.id, petition.ref from petition, body
                where status in ('live', 'rejected', 'finished')
                    and body.id = body_id
                    and lower(body.ref) = ?
                    and lower(petition.ref) = ?", {}, lc $body, lc $ref)) {
            return @row;
        }
    } else {
        if (@row = dbh()->selectrow_array("
                select id, ref from petition
                where status in ('live', 'rejected', 'finished')
                and lower(ref) = ?", {}, lc $ref)) {
            return @row;
        }
    }
    return undef;
}

=item get REF [GOVTRESPONSE]

=item get ID [GOVTRESPONSE]

Return a hash of database fields to values for the petition with the given REF
or ID, or undef if there is no such petition. Include total number signers
based on cached values, and also return petition response(s) if GOVTRESPONSE is set.

=cut
sub get ($;$$) {
    my $ref = shift;
    return undef unless ($ref);
    my $govtresponse = shift;
    my $s = "select petition.*, ms_current_date() <= deadline as open,
        cached_signers+coalesce(offline_signers,0) as signers";
    $s .= ", message.emailbody as response, message.whencreated as responsetime" if ($govtresponse);
    $s .= ', body.id as body_id, body.ref as body_ref, body.name as body_name, body.area_id as body_area_id' if mySociety::Config::get('SITE_TYPE') eq 'multiple';
    $s .= " from petition";
    $s .= ' inner join body on body.id = body_id' if mySociety::Config::get('SITE_TYPE') eq 'multiple';
    $s .= " left join message on petition.id = message.petition_id and circumstance = 'government-response'"
        if ($govtresponse);
    my $p;
    my $order = '';
    $order = ' order by responsetime desc' if $govtresponse;
    $p ||= select_all("$s where petition.id = ?$order", $ref)
            if ($ref =~ /^[1-9]\d*$/);
    $p ||= select_all("$s where lower(petition.ref) = ?$order", lc $ref);
    
    return undef unless $p && @$p > 0;
    $p->[0]->{category} = $petition_categories{$p->[0]->{category}};

    return $p->[0] if @$p == 1;
    my $o = shift @$p;
    foreach (@$p) {
        $o->{response} .= "\n|||" . $_->{response};
        $o->{responsetime} .= "|||" . $_->{responsetime};
    }
    return $o;
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

use Carp;
use Crypt::Blowfish;
use Digest::HMAC_SHA1 qw(hmac_sha1);
use Digest::SHA qw(sha1);
use mySociety::BaseN;

use mySociety::DBHandle ();
use mySociety::Random qw(random_bytes);

use constant TOKEN_LENGTH_B62 => 23;

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

again:
    my @salt = unpack('C4', random_bytes(4, 1));
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

    # 8 bytes of ciphertext plus 7 bytes of HMAC gives 15 bytes, 23 chars
    # base62.
    my $token = mySociety::BaseN::encodefast(62, $ciphertext . substr($hmac, 0, 7));

    # probably need more than these but this shows willing and therefore is
    # enough for now ;-)
    goto again if ($token =~ /[s5]h[i1]t|p[i1]ss|fuck|cunt|c[o0]ck/i);

    return $token;
}

=item check TOKEN

Check the validity of a TOKEN. Returns in list context the WHAT and ID that
were passed to make; or, if TOKEN is invalid, the empty list.

=cut
sub check ($) {
    my $token = shift;
    croak("TOKEN must be defined") unless (defined($token));
    return () if length($token) != TOKEN_LENGTH_B62;

    my $data = mySociety::BaseN::decodefast(62, $token);
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
use mySociety::Web qw(ent);

=item sentence PETITION [HTML] [SHORT]

Returns the main sentence of a petition. If HTML is set, encode HTML entities.
If SHORT is set, truncate the sentence to just what it's for.

=cut
sub sentence ($;$$) {
    my ($p, $html, $short) = @_;
    croak("PETITION may not be undef") unless (defined($p));
    croak("PETITION must be a hash of db fields") unless (ref($p) eq 'HASH');
    croak("Field 'content' missing from PETITION") unless (exists($p->{content}));
    my $petitionee = mySociety::Config::get('SITE_PETITIONED');
    if (mySociety::Config::get('SITE_TYPE') eq 'multiple') {
        $petitionee = $p->{body_name};
    }
    my $sentence = sprintf('We the undersigned petition %s to %s', $petitionee, $p->{content});
    if ($short) {
        $sentence = 'Petition to: ' . $p->{content};
    }
    $sentence = 'This petition cannot be shown.' unless Petitions::show_part($p, 'content');
    $sentence = ent($sentence) if ($html);
    $sentence .= '.' if $sentence !~ /\.$/ && !$short;
    return $sentence;
}

=item domain

Returns the base URL, adjusted if we're given a body.

=cut
sub domain ($) {
    my ($body) = @_;
    if (mySociety::Config::get('SITE_DOMAINS')) {
        return Petitions::Cobrand::custom_domain($body);
    }
    return mySociety::Config::get('BASE_URL');
}

=item url BODY REF

Given a BODY (can be undef) and a REF, construct a URL for this petition.

=cut
sub url ($$) {
    my ($body, $ref) = @_;
    return "/$body/$ref/" if mySociety::Config::get('SITE_TYPE') eq 'multiple' && !mySociety::Config::get('SITE_DOMAINS');
    return "/$ref/";
}

=item absolute_url PETITION

Given a petition object (returned by get() ), just calls the
above two to construct a full URL.

=cut
sub absolute_url($) {
    my $p = shift;
    my $url = domain($p->{body_ref});
    $url .= url($p->{body_ref}, $p->{ref});
    return $url;
}

=item from_name PETITION

Given a petition object, returns the body name if we're on a
multiple site, or the site-wide CONTACT_NAME if not.

=cut
sub from_name($) {
    my $p = shift;
    return $p->{body_name} if mySociety::Config::get('SITE_TYPE') eq 'multiple';
    return mySociety::Config::get('CONTACT_NAME');
}

=item admin_email PETITION

Given a petition object, returns the email to be used for admin contact about it.

=cut
sub admin_email($) {
    my $p = shift;
    return Petitions::Cobrand::admin_email($p);
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
    $detail = mySociety::HTMLUtil::ms_make_clickable($detail);
    $detail =~ s/\n\n+/<\/p> <p>/g;
    my $out = '';
    if ($detail || $p->{offline_link}) {
        my $head = Petitions::Cobrand::main_heading('More details from petition creator');
        my $class = Petitions::Cobrand::more_detail_class();
        $out = '<div id="more_detail" class="' . $class . '"><a name="detail"></a>' . $head;
        $out .= "<p>$detail</p>" if $detail;
        $out .= "<p class='leading'><a href='$p->{offline_link}'>Further information</a></p>"
            if $p->{offline_link};
        $out .= '</div>';
    }
    return $out;
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

=item pretty_date DATE [HTML]

=cut
sub pretty_date ($;$) {
    my ($date, $html) = @_;
    my ($Y, $m, $d) = split(/-/, $date);
    my @months = qw(x January February March April May June July August September October November December);   # XXX lazy
    my $monthyear = "$months[$m] $Y";
    $monthyear = ent($monthyear) if ($html);
    return "$d $monthyear";
}

=item pretty_deadline PETITION [HTML]

=cut
sub pretty_deadline ($;$) {
    my ($p, $html) = @_;
    croak("PETITION may not be undef") unless (defined($p));
    croak("PETITION must be a hash of db fields") unless (ref($p) eq 'HASH');
    return pretty_date($p->{deadline}, $html);
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
            $sender == MSG_ADMIN ? 'admin' : 'creator',
            (map { $recipients & $_ } (MSG_ADMIN, MSG_CREATOR, MSG_SIGNERS)),
            $template);
}


1;
