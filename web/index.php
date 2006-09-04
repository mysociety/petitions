<?
// index.php:
// Main code for PledgeBank website.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.13 2006-09-04 10:56:37 francis Exp $

// Load configuration file
require_once "../phplib/pet.php";

header('Cache-Control: max-age=5');
page_header('Introduction to e-petitions');
?>

<h1><span dir="ltr">E-Petitions</span></h1>

<div id="content_clipboard">

<ul id="petition_actions">
<!--<li style="float: left;"><a href="/new"><img src="/images/clipboard-add.gif" alt="" class="noborder"
><br />Create a Petition</a></li>-->
<li style="float: left;"><img src="/images/clipboard-add.gif" alt="" class="noborder"
><br />Create a Petition <small>(Closed during Beta Test, see right for details)</small></li>
<li style="float: right;"><a href="/list"><img src="/images/clipboard-write.gif" alt="" class="noborder"
><br />View Petitions</a></li>
</ul>

<p>Petitions have long been sent to the Prime Minister by post or delivered to
the Number 10 door in person. You can now both create and sign petitions on
this website, giving you the opportunity to reach a potentially wider audience
and to deliver your petition directly to Downing Street.</p>

<blockquote>"Being able to start a petition online where millions can read it is just great!"</blockquote>
<p align="right">&mdash; David Jones, UK</p>

</div>

<div style="clear: both; float: left; width: 45%;">
<h2><span class="ltr">Five most recent petitions</span></h2>
<p>We the undersigned petition the Prime Minister to&hellip;</p>
<ul>
<?
$recent = db_getAll("select ref, content from petition
    where status = 'live'
    order by creationtime desc limit 5");
# XXX: Creation time is order of creation, not order of going live...
foreach ($recent as $petition) {
    print '<li><a href="/' . $petition['ref'] . '">';
    print htmlspecialchars($petition['content']) . '</a></li>';
}
?>
</ul>
</div>

<div style="float: right; width: 45%;">
<h2><span class="ltr">Five most popular petitions</span></h2>
<p>We the undersigned petition the Prime Minister to&hellip;</p>
<ul>
<?
$recent = db_getAll("
    select ref, content,
        (select count(id) from signer
            where showname
            and petition_id = petition.id
            and emailsent = 'confirmed') as signers
    from petition
    where status = 'live'
    order by signers desc limit 5");
# XXX: Creation time is order of creation, not order of going live...
foreach ($recent as $petition) {
    print '<li><a href="/' . $petition['ref'] . '">';
    print htmlspecialchars($petition['content']) . '</a> <small>(';
    print $petition['signers'] . ' signature';
    print ($petition['signers'] == 1 ? '' : 's') . ')</small></li>';
}
?>
</ul>
<? ?>
</div>

<h2 style="clear: both"><span class="ltr">How it works</span></h2>

<!-- <p>This service has been set up in partnership with <a
href="http://www.mysociety.org/">mySociety</a>, a non-partisan charitable
project that runs various democracy focussed websites in the UK, such as
<a href="http://www.hearfromyourmp.com/">HearFromYourMP.com</a>.
</p>
-->

<p>You can view and sign any <a href="/list">current petitions</a>, and see the
Government response to any <a href="/list/finished">completed petitions</a>.
If you have signed a petition, you will be sent a
response from the Government by email once the petition is closed.
</p>

<p>To ensure transparency, any petition that cannot be accepted will be listed,
along with the reasons why. A list of <a href="/list/rejected">rejected petitions</a>
is available on this website.</p>

<p>All petitions that are submitted to this website will be accepted, as long as
they meet the basic conditions set out in our <a href="/acceptance">acceptance policy</a>.
The aim is to enable as many people as possible to make their views known.
</p>

<?  page_footer();

