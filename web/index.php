<?
// index.php:
// Main page for ePetitions website.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.46 2007-04-13 18:38:06 matthew Exp $

// Load configuration file
require_once "../phplib/pet.php";
require_once "../../phplib/conditional.php";

// Lame: send last-modified now to encourage squid to cache us.
cond_headers(time());
header('Cache-Control: max-age=5');
page_header('Introduction to e-petitions', array(
    'rss' => array(
        'Latest Petitions' => '/rss/list'
    )
));
?>

<h1><span dir="ltr">E-Petitions</span></h1>

<div id="content_clipboard">

<div id="petition_actions">
<ul>
<li id="action_create"><a href="/new"><img src="/images/clipboard-add.gif" alt="" class="noborder"
/><br />Create a Petition</a></li>
<li id="action_view"><a href="/list"><img src="/images/clipboard-write.gif" alt="" class="noborder"
/><br />View Petitions</a></li>
</ul>
</div>

<p>Petitions have long been sent to the Prime Minister by post or delivered to
the Number 10 door in person. You can now both create and sign petitions on
this website too, giving you the opportunity to reach a potentially wider audience
and to deliver your petition directly to Downing Street.</p>

<? pet_search_form(true); ?>

</div>
<div id="most_recent">
<h2><span class="ltr">Five most recent petitions</span></h2>
<p>We the undersigned petition the Prime Minister to&hellip;</p>
<ul>
<?
$recent = db_getAll("select ref, content from petition
    where status = 'live'
    order by laststatuschange desc limit 5");
$c = 1;
foreach ($recent as $petition) {
    print '<li';
    if ($c%2) print ' class="a"';
    print '><a href="/' . $petition['ref'] . '/">';
    print htmlspecialchars($petition['content']) . '</a></li>';
    $c++;
}
if (!count($recent)) {
    print '<li>None, you can <a href="/new">create a petition</a>.</li>';
}
?>
</ul>
<p align="right"><a href="/list/open?sort=date" title="More recent petitions">More</a></p>
</div>
<div id="most_popular">
<h2><span class="ltr">Five most popular open petitions</span></h2>
<p>We the undersigned petition the Prime Minister to&hellip;</p>
<ul>
<?
$recent = db_getAll("
    select ref, content, cached_signers
    from petition
    where status = 'live'
    order by cached_signers desc limit 5");
$c = 1;
foreach ($recent as $petition) {
    print '<li';
    if ($c%2) print ' class="a"';
    print '><a href="/' . $petition['ref'] . '/">';
    print htmlspecialchars($petition['content']) . '</a> <small>(';
    print $petition['cached_signers'] . ' signature';
    print ($petition['cached_signers'] == 1 ? '' : 's') . ')</small></li>';
    $c++;
}
if (!count($recent)) {
    print '<li>None, you can <a href="/new">create a petition</a>.</li>';
}
?>
</ul>
<p align="right"><a href="/list/open?sort=signers" title="More popular petitions">More</a></p>
</div>
<h2 style="clear: both"><span class="ltr">How it works</span></h2>

<!-- <p>This service has been set up in partnership with <a
href="http://www.mysociety.org/">mySociety</a>, a non-partisan charitable
project that runs various democracy focussed websites in the UK, such as
<a href="http://www.hearfromyourmp.com/">HearFromYourMP.com</a>.
</p>
-->

<p>You can view and sign any <a href="/list">current petitions</a>, and see the
Government response to any <a href="/list/closed">completed petitions</a>.
If you have signed a petition, you will be sent a
response from the Government by email once the petition is closed.
</p>

<p>All petitions that are submitted to this website will be accepted, as long as
they are in accordance with our <a href="/terms">terms and conditions</a>.
The aim is to enable as many people as possible to make their views known.
</p>

<p>To ensure transparency, any petition that cannot be accepted will be listed,
along with the reasons why. A list of <a href="/list/rejected">rejected petitions</a>
is available on this website.</p>

<?  page_footer('Home');

