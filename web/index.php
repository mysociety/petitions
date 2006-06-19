<?
// index.php:
// Main code for PledgeBank website.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.2 2006-06-19 16:40:31 francis Exp $

// Load configuration file
require_once "../phplib/pet.php";
require_once '../../phplib/utility.php';
require_once '../../phplib/person.php';

page_header(_('Introduction to e-petitions'));
?>

<h1>Introduction to e-petitions</h1>

<p>Petitions have long been sent to the Prime Minister by post or delivered to
the Number 10 door in person. You can now both create and sign petitions on
this website, giving you the opportunity to reach a potentially wider audience
and to deliver your petition directly to Downing Street.
</p>

<p>This service has been set up in partnership with mySociety, a non-partisan
charitable project that runs various democracy focussed websites in the UK,
such as HearFromYourMP.com .
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
this site will be published, on the rejected petitions (link) page, along with
an explanation of why it could not be hosted.  
</p>

<p><a href="/new">Create new petition</a>

<?  page_footer();

