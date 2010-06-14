#!/usr/bin/perl -w -I../perllib -I../commonlib/perllib
#
# reject.cgi:
# Used because ref might be libellous, so refer by ID number instead
# Can be removed once admin interface is upgraded
#
# Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
# Email: matthew@mysociety.org; WWW: http://www.mysociety.org/
#

my $rcsid = ''; $rcsid .= '$Id: reject.cgi,v 1.16 2010-04-27 10:05:26 matthew Exp $';

use strict;

# use HTTP::Date qw();
use utf8;

use mySociety::Config;
BEGIN {
    mySociety::Config::set_file("../conf/general");
}
use mySociety::DBHandle qw(dbh);
use mySociety::Web qw(ent);

use Petitions;
use Petitions::Page;
use Cobrand;

# accept_loop
# Accept and handle FastCGI requests.
sub main () {
    my $q = shift;

    my $qp_id = $q->ParamValidate(id => qr/^[1-9]\d*$/);
    my $p = Petitions::DB::get($qp_id);
    if (!defined($p)) {
        Petitions::Page::bad_ref_page($q, '');
        return;
    }

    if ($p->{status} ne 'rejected') {
        my $url = '/' . $p->{ref} . '/';
        $url = '/' . $p->{body_ref} . $url if $p->{body_ref};
        print $q->redirect($url);
        return;
    }

    my $title = Petitions::sentence($p, 1, 1);
    my $html =
        Petitions::Page::header($q, $title);
    $html .= $q->h2({-class=>'page_title_border'}, 'Rejected petition');
    $html .= Petitions::Page::display_box($q, $p);
    $html .= Petitions::detail($p);
    $html .= $q->start_div({-id => 'signatories'})
        . Cobrand::main_heading('Petition Rejected');
    $html .= Petitions::Page::reject_box($q, $p);
    $html .= $q->end_div();
    my $stat = 'View.' . $p->{ref};
    $html .= Petitions::Page::footer($q, $stat);
    utf8::encode($html);
    print $q->header(
                -content_length => length($html),
#                -last_modified => HTTP::Date::time2str($lastmodified),
#                -cache_control => 'max-age=1',
#                -expires => '+1s'),
        ), $html;
    dbh()->rollback();
}

# Start FastCGI
Petitions::Page::do_fastcgi(\&main);

exit(0);
