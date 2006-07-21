#!/usr/bin/perl
#
# Petitions.pm:
# Petition utilities.
#
# Copyright (c) 2006 Chris Lightfoot. All rights reserved.
# Email: chris@ex-parrot.com; WWW: http://www.ex-parrot.com/~chris/
#
# $Id: Petitions.pm,v 1.5 2006-07-21 10:50:44 chris Exp $
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
                and ref ilike ?", {}, $ref)) {
        return $ref;
    } else {
        return undef;
    }
}

package Petitions;

use strict;

1;
