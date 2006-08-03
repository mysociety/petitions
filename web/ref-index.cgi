#!/usr/bin/perl -w -I../perllib -I../../perllib
#
# ref-index.cgi:
# Main petition page.
#
# Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#

my $rcsid = ''; $rcsid .= '$Id: ref-index.cgi,v 1.5 2006-08-03 22:49:44 chris Exp $';

use strict;

use HTTP::Date qw();

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
while (!$foad && (my $q = new mySociety::Web())) {
    my $qp_ref = $q->ParamValidate(ref => qr/^[A-Za-z0-9-]{6,16}$/);
    my $ref = Petitions::DB::check_ref($qp_ref);
    if (!defined($ref)) {
        Petitions::Page::bad_ref_page($q, $qp_ref);
        next;
    }

    # Perhaps redirect to canonical ref if non-canonical was given.
    if ($qp_ref ne $ref && $q->request_method() =~ /^(GET|HEAD)$/) {
        my $url = "/$ref/";
        $url .= '?' . $q->query_string() if ($q->query_string());
        print $q->redirect($url);   # ugh -- will add ?ref=$ref
        next;
    }

    my $lastmodified = dbh()->selectrow_array('select extract(epoch from petition_last_change_time((select id from petition where ref = ?)))', {}, $ref);
    next if ($q->Maybe304($lastmodified));

    my $qp_signed = $q->param('signed');

    our $p;
    $p = Petitions::DB::get($ref) if (!$p || $p->{ref} ne $ref);
    my $title = Petitions::sentence($p, 1);
    my $html =
        Petitions::Page::header($q, $title);

    $html .= $q->h1($q->span({-class => 'ltr'}, 'E-Petitions'));
    $html .= $q->h2($q->span({-class => 'ltr'}, 'Sign a petition'));

    $html .= $q->p({ -id => 'finished' }, "This petition is now closed, as its deadline has passed.")
        if (!$p->{open});

    if ($qp_signed) {
        $html .=
            $q->div({ -style => 'font-size: 125%; border: 2px solid blue' },    # XXX design
                    "You're now signed up to this petition! If you'd like to
                    tell your friends about it, its permanent web address is,",
                    $q->br(),
                    $q->strong($q->a({ -href => "/$ref" },
                        ent(mySociety::Config::get('BASE_URL') . "/$ref"
                    ))));
                    
    }

    $html .= Petitions::Page::display_box($q, $p);

    $html .= Petitions::Page::sign_box($q, $p)
        if ($p->{status} eq 'live');

    if ($p->{status} ne 'rejected') {
        $html .= Petitions::Page::signatories_box($q, $p);
    } else {
        $html .= Petitions::Page::reject_box($q, $p);
    }

    $html .= Petitions::Page::footer($q);

    print $q->header(
                -content_length => length($html),
                -last_modified => HTTP::Date::time2str($lastmodified),
                -cache_control => 'max-age=10'),
                $html;
    $W->exit_if_changed();
}
