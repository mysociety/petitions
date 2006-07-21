#!/usr/bin/perl -w -I../perllib -I../../perllib
#
# ref-index.cgi:
# Main petition page.
#
# Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#

my $rcsid = ''; $rcsid .= '$Id: ref-index.cgi,v 1.1 2006-07-21 17:16:24 chris Exp $';

use strict;

use mySociety::Config;
BEGIN {
    mySociety::Config::set_file("../conf/general");
}
use mySociety::DBHandle qw(dbh);
use mySociety::Web qw(ent);

use Petitions;
use Petitions::Page;

my $foad = 0;
$SIG{TERM} = sub { $foad = 1; };
while (!$foad && (my $q = new mySociety::Web())) {
    our $qp_ref;
    $q->Import('p', ref => [qr/^[A-Za-z0-9-]{6,16}$/, undef]);
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

    my $p = Petitions::DB::get($ref);
    my $title = Petitions::sentence($p, 1);
    my $html =
        Petitions::Page::header($q, $title);

    $html .= $q->p({ -id => 'finished' }, "This petition is now closed, as its deadline has passed.")
        if (!$p->{open});

    $html .= Petitions::Page::display_box($q, $p);

    $html .= Petitions::Page::sign_box($q, $p)
        if ($p->{status} eq 'live');

    if ($p->{status} ne 'rejected') {
        $html .= Petitions::Page::signatories_box($q, $p);
    } else {
        $html .= Petitions::Page::reject_box($q, $p);
    }

    $html .= Petitions::Page::footer($q);

    print $q->header(-content_length => length($html)), $html;
}
