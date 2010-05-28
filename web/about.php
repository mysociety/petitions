<?
// about.php:
// About page for ePetitions
//
// Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: about.php,v 1.15 2010-04-27 10:05:25 matthew Exp $

// Load configuration file
require_once "../phplib/pet.php";

header('Cache-Control: max-age=300');
page_header('About e-petitions');
?>

<h2 class="page_title_border">Introduction to e-petitions</h2>

<?
if (OPTION_SITE_NAME == 'number10') {
?>

<p>Downing Street is working in partnership with the non-partisan
mySociety to provide a service to allow citizens,
charities and campaign groups to set up petitions that are hosted on
the Downing Street website, enabling anyone to address and deliver a
petition directly to the Prime Minister.</p>

<p>mySociety is the project of a registered charity, and runs many of the UK's best-known
non-partisan political websites, like
<a href="http://www.hearfromyourmp.com/">HearFromYourMP</a> and
<a href="http://www.theyworkforyou.com/">TheyWorkForYou</a>. mySociety is
strictly neutral on party political issues, and the e-petition service is
within its remit to build websites which give people simple, tangible benefits
in the civic and community aspects of their lives. For more information about
mySociety and its work, <a href="http://www.mysociety.org/">visit the mySociety
website</a>.</p>

<p>The e-petition system has been designed to be transparent and trustworthy.
For legal and anti-spam reasons this site cannot host every petition submitted,
but the rule is to accept everything that meets the terms and conditions of use.</p>

<p>No petition will be rejected unless it violates these terms. And even when
petitions cannot be hosted No10 will still publish as much of rejected
petitions as is consistent with legal and anti-spam requirements, including
the reason why it could not be hosted.</p>

<p>If you have any questions about the service, please contact
<a href="mailto:number10&#64;petitions.pm.gov.uk">number10&#64;petitions.pm.gov.uk</a>.
</p>

<?
} else {
?>

<p>mySociety made the e-petitions site for Number 10.
We've taken that experience to create this site which lets you create
petitions for your local council.

<p>mySociety is the project of a registered charity, and runs many of the UK's best-known
non-partisan political websites, like
<a href="http://www.hearfromyourmp.com/">HearFromYourMP</a> and
<a href="http://www.theyworkforyou.com/">TheyWorkForYou</a>. mySociety is
strictly neutral on party political issues, and the e-petition service is
within its remit to build websites which give people simple, tangible benefits
in the civic and community aspects of their lives. For more information about
mySociety and its work, <a href="http://www.mysociety.org/">visit the mySociety
website</a>.</p>

<p>The e-petition system has been designed to be transparent and trustworthy.
For legal and anti-spam reasons this site cannot host every petition submitted,
but the rule is to accept everything that meets the terms and conditions of use.</p>

<?
}

page_footer('About');

