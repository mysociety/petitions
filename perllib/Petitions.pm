#!/usr/bin/perl
#
# Petitions.pm:
# Petition utilities.
#
# Copyright (c) 2006 Chris Lightfoot. All rights reserved.
# Email: chris@ex-parrot.com; WWW: http://www.ex-parrot.com/~chris/
#
# $Id: Petitions.pm,v 1.8 2006-07-21 13:42:38 chris Exp $
#

package Petitions::DB;

use strict;

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
    return scalar(dbh()->selectrow_array('select secret from secret'));
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

=item get REF

Return a hash of database fields to values for the petition with the given REF,
or undef if there is no such petition. Also make up a signers field giving the
number of people who've signed the petition so far.

=cut
sub get ($) {
    my $ref = shift;
    return undef unless ($ref);
    my $p = dbh()->selectrow_hashref('select * from petition where ref = ?', {}, $ref);
    $p ||= dbh()->selectrow_hashref('select * from petition where ref ilike ?', {}, $ref);
    return undef unless ($p);
    $p->{signers} = dbh()->selectrow_array('select count(id) from signer where petition_id = ?', {}, $p->{id});
    return $p;
}

package Petitions;

use strict;

use POSIX qw();

use mySociety::Util;
use mySociety::Web qw(ent);

my $petition_prefix = "We the undersigned petition the Prime Minister to";

=item sentence PETITION [HTML]

=cut
sub sentence ($;$) {
    my ($p, $html) = @_;
    croak("PETITION must be a hash of db fields") unless (ref($p) eq 'HASH');
    my $sentence = sprintf('%s %s', $petition_prefix, $p->{content});
    $sentence = ent($sentence) if ($html);
    return $sentence;
}

=item pretty_deadline PETITION [HTML]

=cut
sub pretty_deadline ($;$) {
    my ($p, $html) = @_;
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
