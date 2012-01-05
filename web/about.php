<?
// about.php:
// About page for ePetitions
//
// Copyright (c) 2011 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org

// Load configuration file
require_once "../phplib/pet.php";

header('Cache-Control: max-age=300');
page_header('About e-petitions');
$contact_email = str_replace('@', '&#64;', OPTION_CONTACT_EMAIL);

?>

<h2 class="page_title_border">Introduction to e-petitions</h2>

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

page_footer('About');

