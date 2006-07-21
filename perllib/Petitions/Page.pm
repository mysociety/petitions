#!/usr/bin/perl
#
# Petitions/Page.pm:
# Various HTML stuff for the petitions site.
#
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#
# $Id: Page.pm,v 1.4 2006-07-21 11:00:57 chris Exp $
#

package Petitions::Page;

use strict;

use Carp;

use Petitions;
use mySociety::DBHandle qw(dbh);
use mySociety::Web qw(ent);

=item header Q TITLE [PARAM VALUE ...]

Return HTML for the top of the page, given the TITLE text and optional PARAMs.

=cut
sub header ($$%) {
    my ($q, $title, %params) = @_;
    
    my %permitted_params = map { $_ => 1 } qw();
    foreach (keys %params) {
        croak "bad parameter '$_'" if (!exists($permitted_params{$_}));
    }

    my $devwarning = '';;
    if (mySociety::Config::get('PET_STAGING')) {
        my @d = (
                'This is a test site for web developers only.'
                q(You probably want <a href="http://www.pm.gov.uk">the Prime Minister's official site</a>.)
            );
        my $today = Petitions::DB::today();
        push(@d, "Note: on this test site, the date is faked to be $today");
            if ($today ne POSIX::strftime('%Y-%m-%d', localtime()));
        $devwarning = join($q->br(), @d);
    }

    # ugh
    return <<EOF
<?xml version="1.0"?>
<!-- quirks mode -->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en" xml:lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8;" />
<meta name="keywords" content="number 10, petition, petitions, downing street, prime minister, pm" />
<meta name="description" content="Petitions to the Prime Minister, 10 Downing Street" />
<meta name="DCTERMS.created" scheme="DCTERMS.W3CDTF" content="2005-08-17" />
<meta name="eGMS.accessibility" scheme="WCAG" content="Double-A" />
<meta name="dc.creator" content="10 Downing Street, Web Team, webmaster@pmo.gov.uk" />
<meta name="dc.language" scheme="ISO 639-2/T" content="eng" />
<meta name="dc.publisher" content="Prime Minister's Office, 10 Downing Street, London, SW1A 2AA" />
<meta name="dc.identifier" content="@{[ ent($q->url()) ]}" />
<meta name="dc.subject" content="10 Downing Street" />
<meta name="dc.subject" content="Petitions" />
<meta name="dc.subject" content="Prime Minister" />
<meta name="dc.subject" content="Tony Blair" />
<meta name="dc.title" content="@{[ ent($title) ]}" />
<title>@{[ ent($title) ]}</title>
<!-- <script type="text/javascript" language="JavaScript1.1" src="http://www.number10.gov.uk/include/js/nedstat.js"></script> -->
<link href="http://www.number10.gov.uk/styles/basic_styles.css" rel="stylesheet" type="text/css" />
<link href="http://www.number10.gov.uk/styles/gallerycontent.css" rel="stylesheet" />
<style type="text/css" media="all">
                                        \@import url(http://www.number10.gov.uk/styles/nomensa.css);
                                        \@import url(/pet.css);
                                        #rel_links {margin-right: -146px;}
                                </style>
<link rel="stylesheet" type="text/css" href="http://www.number10.gov.uk/styles/print.css" media="print" />
<link rel="shorcut icon" href="favicon.ico" />
</head>
<body>
<h3 align="center" style="color: #cc0000; background-color: #ffffff; ">@{[ $devwarning ]}</h3>
<p class="rm"><a class="skip" href="#content">Skip to: Content</a><span class="skip"> |</span></p>
<div class="toolbarWhite">
<div class="toolbarLogo"><a href="http://www.direct.gov.uk" target="_blank"><img src="http://www.number10.gov.uk/files/images/directgov_logo_white.gif" width="384" height="14" border="0" alt="Link to Directgov - widest range of government information and services online" /></a><span class="skip">|</span></div>
<div class="toolbarLinkText">
<a href="http://www.direct.gov.uk" target="_blank" class="toolbarWhiteLink" title="Opens new browser window - Link to Directgov - widest range of government information and services online">Directgov</a><span class="skip">|</span>
<a href="http://www.number10.gov.uk/output/Page21.asp" target="_blank" class="toolbarWhiteLink" title="Opens in new window - Number 10 Newsroom">Gov news</a><span class="skip">|</span>
</div>
</div>
<div id="header">
<a href="http://www.number10.gov.uk/output/page1.asp" class="logo">
<img src="http://www.number10.gov.uk/files/images/crest.gif" width="125" height="78" alt="The crest for Number 10 Downing Street" title="" />
</a>
<div id="navigation">
<h2>Main menu</h2><ul><li class="primeminister"><span>&nbsp;</span><a href=http://www.number10.gov.uk/output/Page2.asp>prime minister</a><ul><li><a href=http://www.number10.gov.uk/output/Page3.asp>contact</a></li><li><a href=http://www.number10.gov.uk/output/Page8809.asp>the big issues</a></li><li><a href=http://www.number10.gov.uk/output/Page4.asp>biography</a></li><li><a href=http://www.number10.gov.uk/output/Page5.asp>speeches</a></li><li><a href=http://www.number10.gov.uk/output/Page12.asp>PM's office</a></li></ul></li><li class="government"><span>&nbsp;</span><a href=http://www.number10.gov.uk/output/Page18.asp>government</a><ul><li><a href=http://www.number10.gov.uk/output/Page19.asp>cabinet</a></li><li><a href=http://www.number10.gov.uk/output/Page29.asp>guide to legislation</a></li><li><a href=http://www.number10.gov.uk/output/Page30.asp>guide to government</a></li><li><a href=http://www.number10.gov.uk/output/Page31.asp>in your area</a></li><li><a href=http://www.number10.gov.uk/output/Page32.asp>links</a></li></ul></li><li class="news"><span>&nbsp;</span><a href=http://www.number10.gov.uk/output/Page20.asp>newsroom</a><ul><li><a href=http://www.number10.gov.uk/output/Page21.asp>latest news</a></li><li><a href=http://www.number10.gov.uk/output/Page34.asp>media centre</a></li><li><a href=http://www.number10.gov.uk/output/Page36.asp>email updates</a></li></ul></li><li class="downingstreet"><span>&nbsp;</span><a href=http://www.number10.gov.uk/output/Page22.asp>downing street</a><ul><li><a href=http://www.number10.gov.uk/output/Page39.asp>welcome</a></li><li><a href=http://www.number10.gov.uk/output/Page175.asp>history of the building</a></li><li><a href=http://www.number10.gov.uk/output/Page123.asp>PMs in history</a></li><li><a href=http://www.number10.gov.uk/output/Page41.asp>tour</a></li></ul></li><li class="broadcasts"><span>&nbsp;</span><a href=http://www.number10.gov.uk/output/Page24.asp>broadcasts</a><ul><li><a href=http://www.number10.gov.uk/output/Page306.asp>PM's Question Time</a></li><li><a href=http://www.number10.gov.uk/output/Page308.asp>PM's statements</a></li><li><a href=http://www.number10.gov.uk/output/Page3054.asp>films</a></li></ul></li></ul></div>
<span class="clear">&nbsp;</span>
</div>
<div id="helpbar">
<div>
<p class="help"><a href="http://www.number10.gov.uk/output/page6371.asp">Help</a></p>
<form name="kbs" method="get" action="http://search.number-10.gov.uk/kbroker/number10/number10/search.lsim">
<script type="text/javascript" language="JavaScript" src="http://www.number10.gov.uk/include/js/helper.js"></script>
<label for="qt">Search</label>
<input type="text" name="qt" id="qt" maxlength="1000" value="Enter search terms" onfocus="clearInstructions(this)" />&nbsp;<input name="go" id="go" type="submit" value="Go" />
<input type="hidden" name="sr" value="0" />
<input type="hidden" name="nh" value="10" />
<input type="hidden" name="cs" value="iso-8859-1" />
<input type="hidden" name="sc" value="number10" />
<input type="hidden" name="sm" value="0" />
<input type="hidden" name="mt" value="1" />
<input type="hidden" name="to" value="0" />
<input type="hidden" name="ha" value="368" />
</form>
<p>You are here: <a href="http://www.number10.gov.uk/output/Page1.asp">home</a>&nbsp;>&nbsp;<a href="/">petitions</a></p>
</div>
</div>
<div id="main">
<div id="wrap">
<div id="content">
EOF
}

=item footer Q [PARAMS]

=cut
sub footer ($%) {
    my ($q, %params) = @_;

    my %permitted_params = map { $_ => 1 } qw();
    foreach (keys %params) {
        croak "bad parameter '$_'" if (!exists($permitted_params{$_}));
    }

    return <<EOF
<img src="http://www.number10.gov.uk/files/images/clear.gif" width="1" height="1" alt="" class="emptyGif" />
<p align="center">
<a href="/">Home</a> |
<a href="/about">About</a> |
<a href="/faq">Q&amp;A</a> |
<a href="/new">Create</a> |
<a href="/privacy">Privacy Policy</a>
</p>
</div>
</div>
<div id="rel_links">
<img src="http://www.number10.gov.uk/files/images/clear.gif" width="1" height="1" alt="" class="emptyGif" />
</div>
</div>
<div id="ilinks">
<!--<h2>Important Links</h2><div class="downingstreet"><div class="header">&nbsp;</div><div><h3><a href="http://www.number10.gov.uk/output/page41.asp"><img src="http://www.number10.gov.uk/files/images/take_a_tour_of_N10_purple.jpg" alt="" />
                                        Take a tour of Number 10</a></h3><p>Have you seen our virtual tour of 10 Downing Street?</p></div><div class="footer">&nbsp;</div></div><div class="primeminister"><div class="header">Â| </div><div><h3><img width="80" alt="House of Parliament. Picture: Britain on View" height="60" border="0" src="http://www.number10.gov.uk/files/images/New Parliament 5 SMALL.jpg" /> <a href="http://www.number10.gov.uk/output/Page8809.asp">The Big Issues</a></h3><p>In-depth coverage of the main policy areas currently being addressed by the PM and the government</p></div><div class="footer"></div></div><img src="http://www.number10.gov.uk/files/images/clear.gif" width="1" height="1" alt="" class="emptyGif" />
-->
<img src="http://www.number10.gov.uk/files/images/clear.gif" width="1" height="1" alt="" class="emptyGif" />
</div>
<div id="footer">
<div><p>|
        <a href="http://www.number10.gov.uk/output/Page49.asp">copyright</a>&nbsp;|&nbsp;<a href="http://www.number10.gov.uk/output/Page7035.asp">freedom of information</a>&nbsp;|&nbsp;<a href="http://www.number10.gov.uk/output/Page50.asp">contact the webteam</a>&nbsp;|&nbsp;<a href="http://www.number10.gov.uk/output/Page51.asp">website feedback</a>&nbsp;|&nbsp;<a href="http://www.number10.gov.uk/output/Page52.asp">privacy policy</a>&nbsp;|&nbsp;<a href="http://www.number10.gov.uk/output/Page53.asp">advanced search</a>&nbsp;|&nbsp;<a href="http://www.number10.gov.uk/output/Page54.asp">sitemap</a>&nbsp;|&nbsp;<a href="http://www.number10.gov.uk/output/Page4049.asp">accessibility</a>&nbsp;|&nbsp;<a href="http://www.number10.gov.uk/output/Page6508.asp">rss feed</a>&nbsp;|&nbsp;</p></div>
</div>
</body>
</html>
EOF
}

=item error_page Q MESSAGE

=cut
sub error_page ($$) {
    my ($q, $message);
    my $html = header("Error")
            . $q->p($message)
            . footer();
    print $q->header(-content_length => length($html)), $html;
}

=item bad_ref_page Q REF

Emit a helpful error page for a bad or undefined petition reference REF.

=cut
sub bad_ref_page ($$) {
    my ($q, $ref) = @_;
    my $html =
        page_header("We couldn't find that petition");

    if (defined($ref)) {
        $html .= $q->p(qq(We couldn't find any petition with a reference like "@{[ ent($ref) ]}". Please try the following:));
    } else {
        $html .= $q->p(qq(We're not sure which petition you're looking for. Please try the following:));
    }

    $html .=
        $q->ul(
            $q->li([
            q(If you typed in the location, check it carefully and try typing it again.),
            q(Look for the pledge on <a href="/list">the list of all petitions</a>.)
            ])
        );    
    
    $html .= page_footer();
 
    print $q->header(-content_length => length($html)), $html;
}

=item signup_form Q PETITION

Return a signup form for the given PETITION (ref or hash of fields to values).

=cut
sub signup_form ($$) {
    my ($q, $p) = @_;
    if (!ref($p)) {
        my $ref = $p;
        $p = dbh()->selectrow_hashref('select * from petition where ref = ?',
                        {}, $ref);
        $p ||= dbh()->selectrow_hashref('select * from petition where ref ilike ?',
                        {}, $ref);
        croak "bad ref '$ref' in signup_form" unless ($p);
    }

    return
        $q->start_form(-method => 'POST', -action => "$p->{ref}/sign")
        . qq(<input type="hidden" name="add_signatory" value="1" />)
        . qq(<input type="hidden" name="ref" value="@{[ ent($p->{ref} ]}" />)
        . $q->h2('Sign up now')
        . $q->p($q->strong(
                "I, ",
                $q->textfield(
                    -name => 'name', -id => 'name', -size => 20,
                    -onblur => 'fadeout(this)', -onfocus => 'fadein(this)'
                ),
                " sign up to the petition"
            ))
        . $q->br()
        . $q->p(
            $q->strong('Your email:'),
                $q->textfield(-name => 'email', -size => 30),
                $q->br(),
            $q->strong('Confirm email:'),
                $q->textfield(-name => 'email2', -size => 30),
                $q->br(),
            $q->small('(we need this so we can tell you when the petition is completed and let the Government get in touch)')
        )
        . $q->p(
            $q->strong({ -style => 'float: left' }, 'Your address:&nbsp;'),
                $q->textarea(-name => 'address', -cols => 30, -rows => 4),
                $q->br(), $q->br(),
            $q->strong('Your postcode:'),
                $q->textfield(-name => 'postcode', -size => 10)
        )
        . $q->p(
            $q->submit(-name => 'submit', -value => 'Sign petition')
        )
        . $q->end_form();
}

1;
