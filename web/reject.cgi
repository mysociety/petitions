#!/usr/bin/perl -w -I../perllib -I../../perllib
#
# reject.cgi:
# Used because ref might be libellous, so refer by ID number instead
# Can be removed once admin interface is upgraded
#
# Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
# Email: matthew@mysociety.org; WWW: http://www.mysociety.org/
#

my $rcsid = ''; $rcsid .= '$Id: reject.cgi,v 1.8 2007-02-02 15:04:48 chris Exp $';

use strict;

# use HTTP::Date qw();
use utf8;

use mySociety::Config;
BEGIN {
    mySociety::Config::set_file("../conf/general");
}
use mySociety::DBHandle qw(dbh);
use mySociety::Web qw(ent);
use mySociety::WatchUpdate;

use Petitions;
use Petitions::Page;

my $W = new mySociety::WatchUpdate();

my $foad = 0;
$SIG{TERM} = sub { $foad = 1; };

# accept_loop
# Accept and handle FastCGI requests.
sub accept_loop () {
    while (!$foad && (my $q = new mySociety::Web())) {
        my $qp_id = $q->ParamValidate(id => qr/^[1-9]\d*$/);
        my $p = Petitions::DB::get($qp_id);
        if (!defined($p)) {
            Petitions::Page::bad_ref_page($q, '');
            next;
        }

        if ($p->{status} ne 'rejected') {
            print $q->redirect('/' . $p->{ref} . '/');
            next;
        }

        my $title = Petitions::sentence($p, 1);
        my $html =
            Petitions::Page::header($q, $title);
        $html .= $q->h1($q->span({-class => 'ltr'}, 'E-Petitions'));
        $html .= $q->h2($q->span({-class => 'ltr'}, 'Rejected petition'));
        $html .= Petitions::Page::display_box($q, $p);
        $html .= $q->start_div({-id => 'signatories'})
            . $q->h2($q->span({-class => 'ltr'}, 'Petition Rejected'));
        $html .= Petitions::Page::reject_box($q, $p);
        $html .= $q->end_div();
        $html .= Petitions::detail($p);
        my $stat = 'View.' . $p->{ref};
        $html .= Petitions::Page::footer($q, $stat);
        utf8::encode($html);
        print $q->header(
                    -content_length => length($html),
    #                -last_modified => HTTP::Date::time2str($lastmodified),
    #                -cache_control => 'max-age=1',
    #                -expires => '+1s'),
            ), $html;
        $W->exit_if_changed();
        dbh()->rollback();
    }
}

# as in ref-sign, handle process-management ourselves
mySociety::Util::manage_child_processes({
                web => [mySociety::Config::get("NUM_REJECT_PROCESSES", 10),
                        \&accept_loop]
            });

exit(0);
