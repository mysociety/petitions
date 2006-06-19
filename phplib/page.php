<?
// page.php:
// Header, footer and other layout parts for pages.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: page.php,v 1.2 2006-06-19 16:40:31 francis Exp $

require_once '../../phplib/person.php';
require_once '../../phplib/db.php';

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

    $P = person_if_signed_on(true); /* Don't renew any login cookie. */

?><!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?
    if ($title) {
        print $title . " - ";
        /* XXX @import url('...') uses single-quotes to hide the style-sheet
         * from Mac IE. Ugly, but it works. */
?> Petitions</title>
</head>
<body>
<?
    }

    // Warn that we are on a testing site
    $devwarning = array();
    if (OPTION_PET_STAGING) {
        $devwarning[] = _('This is a test site for developers only.');
    }
    global $pet_today;
    if ($pet_today != date('Y-m-d')) {
        $devwarning[] = _("Note: On this test site, the date is faked to be") . " $pet_today";
    }
    if (count($devwarning) > 0) {
        ?><p class="noprint" align="center" style="color: #cc0000; background-color: #ffffff; margin-top: 0;"><?
        print join('<br>', $devwarning);
        ?></p><?
    }
}

/* page_footer PARAMS
 * Print bottom of HTML page. This closes the "content" <div>.  
 * If PARAMS['nonav'] is true then the footer navigation is not displayed. 
 * If PARAMS['nolocalsignup'] is true then no local signup form is showed.
 */
function page_footer($params = array()) {
?>
</body></html>
<?  
    header('Content-Length: ' . ob_get_length());
}


?>
