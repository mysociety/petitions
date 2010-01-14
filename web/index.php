<?
// index.php:
// Main page for ePetitions website.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.58 2010-01-14 18:26:15 matthew Exp $

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

echo '<div id="content_clipboard">
<div id="petition_actions">
<ul>';

if (!OPTION_CREATION_DISABLED) {
    echo '<li id="action_create"><a href="/new"><img src="/images/clipboard-add.gif" alt="" class="noborder"
/><br />Create a Petition</a></li>';
}

echo '<li id="action_view"><a href="/list"><img src="/images/clipboard-write.gif" alt="" class="noborder"
/><br />View Petitions</a></li>
</ul>
</div>';

if (OPTION_SITE_NAME == 'number10') {
    echo '<p>Petitions have long been sent to the Prime Minister by post or delivered to
the Number 10 door in person. You can now both create and sign petitions on
this website too, giving you the opportunity to reach a potentially wider audience
and to deliver your petition directly to Downing Street.</p>';
} else {
    echo '<p><em>You can now both create and sign petitions to ' . str_replace('the ', 'your ', OPTION_SITE_PETITIONED) . ' on this website,
giving you the opportunity to reach a potentially wider audience and to deliver your petition
directly to ' . OPTION_SITE_PETITIONED . '.</em></p>';
}

pet_search_form(true);
if (OPTION_CREATION_DISABLED) {
    page_closed_message(true);
}

echo "</div>
<div id='most_recent'>
<h3 class='page_title_border'>Five most recent petitions</h3>
<p>We the undersigned petition " . OPTION_SITE_PETITIONED . " to&hellip;</p>
<ul>";

if (OPTION_SITE_TYPE == 'multiple') {
    $recent = db_getAll("select petition.ref, content, body.ref as body_ref
    from petition, body
    where status = 'live' and body_id = body.id
    order by laststatuschange desc limit 5");
    $most = db_getAll("
    select petition.ref, content, cached_signers, body.ref as body_ref
    from petition, body
    where status = 'live' and body_id = body.id
    order by cached_signers desc limit 5");
} else {
    $recent = db_getAll("select ref, content from petition
    where status = 'live'
    order by laststatuschange desc limit 5");
    $most = db_getAll("
    select ref, content, cached_signers
    from petition
    where status = 'live'
    order by cached_signers desc limit 5");
}
$c = 1;
foreach ($recent as $petition) {
    print '<li';
    if ($c%2) print ' class="a"';
    print '><a href="/';
    if (isset($petition['body_ref'])) print $petition['body_ref'] . '/';
    print $petition['ref'] . '/">';
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
<h3 class="page_title_border">Five most popular open petitions</h3>
<p>We the undersigned petition <?=OPTION_SITE_PETITIONED?> to&hellip;</p>
<ul>
<?
$c = 1;
foreach ($most as $petition) {
    print '<li';
    if ($c%2) print ' class="a"';
    print '><a href="/';
    if (isset($petition['body_ref'])) print $petition['body_ref'] . '/';
    print $petition['ref'] . '/">';
    print htmlspecialchars($petition['content']) . '</a> <small>(';
    print $petition['cached_signers'] . ' signature';
    print ($petition['cached_signers'] == 1 ? '' : 's') . ')</small></li>';
    $c++;
}
if (!count($most)) {
    print '<li>None, you can <a href="/new">create a petition</a>.</li>';
}
?>
</ul>
<p align="right"><a href="/list/open?sort=signers" title="More popular petitions">More</a></p>
</div>
<h3 class="page_title_border" style="clear: both">How it works</h3>

<!-- <p>This service has been set up in partnership with <a
href="http://www.mysociety.org/">mySociety</a>, a non-partisan charitable
project that runs various democracy focussed websites in the UK, such as
<a href="http://www.hearfromyourmp.com/">HearFromYourMP.com</a>.
</p>
-->

<p>You can view and sign any <a href="/list">current petitions</a>, and see
<?=OPTION_SITE_NAME=='number10'?'the Government':OPTION_SITE_PETITIONED."'s"?> response to any
<a href="/list/closed">completed petitions</a>.
If you have signed a petition that has reached more than 500 signatures
by the time it closes, you will be sent a response from
<?=OPTION_SITE_NAME=='number10'?'the Government':OPTION_SITE_PETITIONED?> by email.
</p>

<p>All petitions that are submitted to this website will be accepted, as long as
they are in accordance with our <a href="/terms">terms and conditions</a>.
The aim is to enable as many people as possible to make their views known.
</p>

<p>To ensure transparency, any petition that cannot be accepted will be listed,
along with the reasons why. A list of <a href="/list/rejected">rejected petitions</a>
is available on this website.</p>

<?  page_footer('Home');

