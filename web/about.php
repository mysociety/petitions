<?
// about.php:
// About page for ePetitions
//
// Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: about.php,v 1.9 2006-10-05 22:58:44 matthew Exp $

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

<p>The e-petition system is run the principle that people should have
complete confidence that it is fair and transparent. There are basic
requirements that this service must meet&mdash;for example we cannot
host offensive, libellous or party political petitions. Our
<a href="/terms">terms and conditions</a> explains in full the
requirements that must be met. But the main aim is to make it possible
for the widest number of people to make their views heard.</p>

<p>No petition will be rejected unless it fails to meet these basic requirements. The system has been designed to make it impossible to delete or lose petitions.</p>

<p>Every petition that we receive will be recorded publicly on the
website, whether it is accepted or not. The
<a href="/steps">step-by-step guide</a>
explains in detail how the process for accepting petitions works,
and what will happen if we reject a petition. Further information
is available in our <a href="/faq">questions and answers section</a>.</p>

<p>If you have any questions about the service, you can email either
the Downing Street web team at
<a href="mailto:webmaster&#64;pmo.gov.uk">webmaster&#64;pmo.gov.uk</a>
or mySociety at
<a href="mailto:team&#64;mysociety.org">team&#64;mysociety.org</a>.</p>

<?  page_footer();

