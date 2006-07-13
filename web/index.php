<?
// index.php:
// Main code for PledgeBank website.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.5 2006-07-13 14:15:44 matthew Exp $

// Load configuration file
require_once "../phplib/pet.php";

page_header(_('Introduction to e-petitions'));
?>

<h1><span dir="ltr">Introduction to e-petitions</span></h1>

<p>Petitions have long been sent to the Prime Minister by post or delivered to
the Number 10 door in person. You can now both create and sign petitions on
this website, giving you the opportunity to reach a potentially wider audience
and to deliver your petition directly to Downing Street.
</p>

<p>This service has been set up in partnership with <a
href="http://www.mysociety.org/">mySociety</a>, a non-partisan charitable
project that runs various democracy focussed websites in the UK, such as
<a href="http://www.hearfromyourmp.com/">HearFromYourMP.com</a>.
</p>

<p>All petitions that are submitted to this website will be accepted, as long as
they meet the basic conditions set out in our acceptance policy. The aim is to
enable as many people as possible to make their views known.
</p>

<p>You can view and sign any current petitions, and see the Government response to
any completed petitions. If you have signed a petition, you will be sent a
response from the Government by email once the petition is closed.
</p>

<p>To ensure transparency, any petition that cannot be opened for signatures on
this site will be published, on the <a href="/list/rejected">rejected petitions</a>
page, along with an explanation of why it could not be hosted.  
</p>

<ul>
<li><a href="/new">Create new petition</a></li>
<li><a href="/list">View current petitions</a></li>

<?  page_footer();

