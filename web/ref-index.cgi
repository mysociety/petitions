#!/usr/bin/perl -w -I../perllib -I../../perllib
#
# ref-index.cgi:
# Main petition page.
#
# Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#

my $rcsid = ''; $rcsid .= '$Id: ref-index.cgi,v 1.52 2007-05-30 14:19:53 francis Exp $';

use strict;

# use Compress::Zlib;
use Digest::HMAC_SHA1 qw(hmac_sha1_hex);
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

# accept_loop
# Accept and handle FastCGI requests.
sub main () {
    my $q = shift;

    my $qp_ref = $q->ParamValidate(ref => qr/^[A-Za-z0-9-]{6,16}$/);
    my $qp_id = $q->ParamValidate(id => qr/^[0-9]+$/);
    my $ref = Petitions::DB::check_ref($qp_ref);
    if (!defined($ref)) {
        Petitions::Page::bad_ref_page($q, $qp_ref);
        return;
    }

    # Perhaps redirect to canonical ref if non-canonical was given.
    if ($qp_ref ne $ref && $q->request_method() =~ /^(GET|HEAD)$/) {
        my $url = "/$ref/";
        $url .= '?' . $q->query_string() if ($q->query_string());
        print $q->redirect($url);   # ugh -- will add ?ref=$ref
        return;
    }

    # We don't do this in a PostgreSQL function because they don't use indices always
    # (at least in PostgreSQL 7.4) which led to slow sequential scans.

    # XXX: lastupdate is only updated when the number changes on cron now
    # People notice names changing, but not the number, so should probably
    # add some sort of explanation that the number might be behind real-time
    my $lastmodified = dbh()->selectrow_array('select extract(epoch from lastupdate)
        from petition where ref = ?', {}, $ref);
    return if ($q->Maybe304($lastmodified));

    # We show the "you've signed" box if a signed=... parameter with a
    # recent-enough timestamp is passed.
    my $show_signed_box = 0;
    if (my $qp_signed = $q->param('signed')) {
        my ($t, $s) = ($qp_signed =~ /^([0-9a-f]+)\.([0-9a-f]+)$/);
        if ($t && $s) {
            my $s2 = substr(hmac_sha1_hex($t, Petitions::DB::secret()), 0, 6);
            if ($s eq $s2 && hex($t) > (time() - 1_000_000_000 - 60)) {
                $show_signed_box = 1;
            }
        }

        if (!$show_signed_box) {
            # Bogus/out-of-date signed parameter, so redirect to main petitions
            # page.
            print $q->redirect("/$ref/");
            return;
        }
    }

    my $p = Petitions::DB::get($ref, 0, 1);
    my $title = Petitions::sentence($p, 1, 1);
    # XXX: Should all the show_parts be done in the get() so I don't need to worry about remembering to do it?
    my $name = Petitions::show_part($p, 'name') ? ent($p->{name}) : '&lt;Name cannot be shown&gt;';
    my $detail = Petitions::show_part($p, 'detail') ? ent($p->{detail}) : 'More details cannot be shown';
    $detail = length($detail)>100 ? substr($detail, 0, 100) . '...' : $detail;
    my %params = (
        status => $p->{status},
        category => $p->{category},
        creator => $name,
        description => $detail
    );
    if ($p->{status} ne 'rejected') {
        $params{signers} = $p->{signers};
        $params{deadline} = $p->{deadline};
    }
    my $html = Petitions::Page::header($q, $title, %params);

    $html .= $q->h1($q->span({-class => 'ltr'}, 'E-Petitions'));
    $html .= $q->h2($q->span({-class => 'ltr'}, 'Sign a petition'));

    $html .= $q->p({ -id => 'finished' }, "This petition is now closed, as its deadline has passed.")
        if ($p->{status} eq 'finished');

    if ($show_signed_box) {
        $html .=
            $q->div({ -id =>'success' },
                $q->p(
                    "You are now signed up to this petition. Thank you."),
                $q->p("For news about the Prime Minister's work and agenda, and other features including films, interviews, a virtual tour and history of No.10, visit the ", $q->a({ -href => 'http://www.pm.gov.uk/' }, 'main Downing Street homepage')),
                $q->p("If you'd like to tell your friends about this petition, its permanent web address is:",
                    $q->strong($q->a({ -href => "/$ref/" },
                        ent(mySociety::Config::get('BASE_URL') . "/$ref/"
                    ))))
            );
    }

    # If the ref has been marked as not to be shown, do not give a hint at its existance
    if ($p->{status} eq 'rejected' && !Petitions::show_part($p, 'ref')) {
        Petitions::Page::bad_ref_page($q, $qp_ref);
        return;
    }

    $html .= Petitions::Page::display_box($q, $p, detail=>1);
    $html .= Petitions::Page::response_box($q, $p) if ($p->{response});
    if ($p->{status} eq 'live' && !$show_signed_box) {
        $html .= $q->h2($q->span({-class => 'ltr'}, 'Sign a petition'))
            if $p->{response};
        $html .= Petitions::Page::sign_box($q, $p);
    }
    $html .= Petitions::detail($p);
    if ($p->{status} ne 'rejected') {
        $html .= Petitions::Page::signatories_box($q, $p);
    } else {
        $html .= $q->start_div({-id => 'signatories'})
            . $q->h2($q->span({-class => 'ltr'}, 'Petition Rejected'));
        $html .= Petitions::Page::reject_box($q, $p);
        $html .= $q->end_div();
    }
    my $stat = 'View.' . $p->{ref};
    $stat .= '.signed' if ($show_signed_box);
    $html .= Petitions::Page::footer($q, $stat);

    utf8::encode($html);

    print $q->header(
                -content_length => length($html),
                -last_modified => HTTP::Date::time2str($lastmodified),
                -cache_control => 'max-age=1',
                -expires => '+1s'),
                $html;

    $html = '';
    undef $html;
}

# Start FastCGI
Petitions::Page::do_fastcgi(\&main);

exit(0);
