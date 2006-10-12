#!/usr/bin/perl -w -I../perllib -I../../perllib
#
# ref-index.cgi:
# Main petition page.
#
# Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#

my $rcsid = ''; $rcsid .= '$Id: ref-index.cgi,v 1.14 2006-10-12 00:02:44 matthew Exp $';

use strict;

use Compress::Zlib;
use HTTP::Date qw();
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
while (!$foad && (my $q = new mySociety::Web())) {
    my $qp_ref = $q->ParamValidate(ref => qr/^[A-Za-z0-9-]{6,16}$/);
    #my $qp_id = $q->ParamValidate(id => qr/^[0-9]+$/);
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

    my $p = Petitions::DB::get($ref);
    my $title = Petitions::sentence($p, 1);
    my $html =
        Petitions::Page::header($q, $title);

    $html .= $q->h1($q->span({-class => 'ltr'}, 'E-Petitions'));
    $html .= $q->h2($q->span({-class => 'ltr'}, 'Sign a petition'));

    $html .= $q->p({ -id => 'finished' }, "This petition is now closed, as its deadline has passed.")
        if ($p->{status} eq 'finished');

# XXX: Link to government response somewhere in here...

    if ($qp_signed) {
        $html .=
            $q->p({ -id =>'success' },
                    "Thank you, you're now signed up to this petition! If you'd like to
                    tell your friends about it, its permanent web address is:",
                    $q->br(),
                    $q->strong($q->a({ -href => "/$ref" },
                        ent(mySociety::Config::get('BASE_URL') . "/$ref"
                    ))));
                    # XXX: *** Send to friend ***
                    
    }

    # XXX For now, as ref might be libellous, pretend it doesn't exist
    # Just remove this if when admin interface upgraded
    if ($p->{status} eq 'rejected') {
        Petitions::Page::bad_ref_page($q, $qp_ref);
        next;
    }

    if ($p->{status} ne 'rejected') {
        $html .= Petitions::Page::display_box($q, $p);
        $html .= Petitions::Page::sign_box($q, $p)
            if ($p->{status} eq 'live');
        $html .= Petitions::Page::signatories_box($q, $p);
    } else {
        $html .= Petitions::Page::reject_box($q, $p);
    }

    my $detail = ent($p->{detail});
    $detail =~ s/\n\n+/<\/p> <p>/g;
    if ($detail) {
        $html .= <<EOF;
<div style="float: right; width:45%">
<h2><span dir="ltr">More details from petition creator</span></h2>
<p>$detail</p></div>
EOF
    }

    $html .= Petitions::Page::footer($q, 'View_' . $p->{ref});

    utf8::encode($html);

    # Perhaps send gzipped content.
    my $ce = undef;
    my $ae = $q->http('Accept-Encoding');
    if ($ae) {
        my %encodings  = map { s/;.*$//; $_ => 1 } split(/,\s*/, $ae);
        if ($encodings{'*'}
            || $encodings{'gzip'}
            || $encodings{'x-gzip'}) {
            $html = Compress::Zlib::memGzip($html);
            $ce = 'gzip';
        }
    }

    print $q->header(
                -content_length => length($html),
                -last_modified => HTTP::Date::time2str($lastmodified),
                ($ce
                    ? (-content_encoding => $ce)
                    : () ),
                -cache_control => 'max-age=1',
                -expires => '+1s'),
                $html;
    $W->exit_if_changed();
}
