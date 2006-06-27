<?
// page.php:
// Header, footer and other layout parts for pages.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: page.php,v 1.5 2006-06-27 22:40:28 matthew Exp $

require_once '../../phplib/person.php';

/* page_header TITLE [PARAMS]
 * Print top part of HTML page, with the given TITLE. This prints up to the
 * start of the "content" <div>.  If PARAMS['nonav'] is true then the top 
 * title and navigation are not displayed, or if PARAMS['noprint'] is true
 * then they are not there if the page is printed. TITLE must be in HTML,
 * with codes already escape */
function page_header($title, $params = array()) {
    // The http-equiv in the HTML below doesn't always seem to override HTTP
    // header, so we say that we are UTF-8 in the HTTP header as well (Case
    // where this was required: On my laptop, Apache wasn't setting UTF-8 in
    // header by default as on live server, and FireFox was defaulting to
    // latin-1 -- Francis)
    header('Content-Type: text/html; charset=utf-8');

    # XXX: Are we going to show people they are logged in?
    $P = person_if_signed_on(true); /* Don't renew any login cookie. */

    // Warn that we are on a testing site
    global $devwarning;
    $devwarning = array();
    if (OPTION_PET_STAGING) {
        $devwarning[] = _('This is a test site for web developers only.');
        $devwarning[] = _('You probably want <a href="http://www.pm.gov.uk">the Prime Minister\'s official site</a>.');
    }
    global $pet_today;
    if ($pet_today != date('Y-m-d')) {
        $devwarning[] = _("Note: On this test site, the date is faked to be") . " $pet_today";
    }
    $devwarning = join('<br>', $devwarning);

    include "../templates/website/head.php";
}

/* page_footer PARAMS
 * Print bottom of HTML page. This closes the "content" <div>.  
 * If PARAMS['nonav'] is true then the footer navigation is not displayed. 
 * If PARAMS['nolocalsignup'] is true then no local signup form is showed.
 */
function page_footer($params = array()) {
    include "../templates/website/foot.php";
    header('Content-Length: ' . ob_get_length());
}


?>
