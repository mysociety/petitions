<?
// about.php:
// About page for ePetitions
//
// Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: about.php,v 1.12 2007-02-09 19:24:15 matthew Exp $

// Load configuration file
require_once "../phplib/pet.php";

header('Cache-Control: max-age=300');
page_header(_('About No. 10 e-petitions'));
?>

<h1><span dir="ltr">Introduction to e-petitions</span></h1>

<p>Downing Street is working in partnership with the non-partisan
charitable project mySociety to provide a service to allow citizens,
charities and campaign groups to set up petitions that are hosted on
the Downing Street website, enabling anyone to address and deliver a
petition directly to the Prime Minister.</p>

<p>mySociety is a charitable project that runs many of the UK's best-known non-partisan political websites, like <a href="http://www.hearfromyourmp.com/">HearFromYourMP.com</a> and <a href="http://www.theyworkforyou.com/">TheyWorkForYou.com</a>.  mySociety is strictly neutral on party political issues, and the e-petition service is within its remit to build websites which give people simple, tangible benefits in the civic and community aspects of their lives. For more information about mySociety and its work, visit <a href="http://www.mysociety.org/">its website</a>.</p>

<p>The e-petition system has been designed to be transparent and trustworthy.
For legal and anti-spam reasons this site cannot host every petition submitted,
but the rule is to accept everything that meets the terms and conditions of use.</p>

<p>No petition will be rejected unless it violates these terms. And even when
petitions cannot be hosted No10 will still publish as much of rejected
petitions as is consistent with legal and anti-spam requirements, including
the reason why it could not be hosted.</p>

<p>If you have any questions about the service, you can email either
the Downing Street web team at
<a href="mailto:webmaster&#64;pmo.gov.uk">webmaster&#64;pmo.gov.uk</a>
or mySociety at
<a href="mailto:team&#64;mysociety.org">team&#64;mysociety.org</a>.</p>

<?  page_footer('About');

