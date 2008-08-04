<?
// tandcs.php:
// Terms & Conditions
//
// Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: terms.php,v 1.17 2008-08-04 10:48:07 matthew Exp $

// Load configuration file
require_once "../phplib/pet.php";

page_header('E-petitions Terms and Conditions');
?>

<h2 class="page_title_border">Terms and Conditions</h2>

<p>The purpose of the
e-petition service is to enable as many people as possible to make
their views known. All petitions will be accepted and published on this
website, providing they meet the criteria below.</p>

<?
if (OPTION_SITE_TYPE == 'pm') {
    echo '<p>Petitions have long been sent to the Prime Minister by post, or
delivered to the Number 10 door in person. E-petitions are welcome in
the same way.</p>';
}
?>

<p>Petitioners may freely disagree with the <?=OPTION_SITE_TYPE=='pm'?'Government':'council'?> or call for
changes of policy. There will be no attempt to exclude critical views
and decisions to accept or reject will not be made on a party political basis.</p>

<p>However, to protect this service from abuse, petitions must satisfy
some basic conditions.</p>

<p>To submit a petition, you must use the online form to provide:</p>

<ul>
<li>the title or subject of the petition;</li>
<li>a clear and concise statement covering the subject of the
petition. It should state what action the petitioner wishes the <?=OPTION_SITE_TYPE=='pm'
?'PM or the Government':'council' ?> to take. The petition will be returned to you to edit
if it is unclear what action is being sought;</li>
<li>the petition author's contact address (in case we need to
contact you about the petition. This
will not be placed on the website);</li>
<li>a duration for the petition.</li>
</ul>

<? terms_and_conditions(); ?>

<p>Petitions that do not follow these guidelines cannot be accepted. In
these cases, you will be informed in writing of the reason(s) your
petition has been refused. If this happens, we will give you the
option of altering and resubmitting the petition so it can be
accepted.</p>

<p>If you decide not to resubmit your petition, or if the second
iteration is also rejected, we will list your petition and the
reason(s) for not accepting it on this website. We will publish the
full text of your petition, unless the content is illegal or offensive.
</p>

<p>
Once accepted, petitions will be made available on this
website for anyone to sign.  Anyone signing the petition must provide
their name, address and a verifiable email address. No personal
details other than their name will be published on the site.
Information about any individual will not be used for any other
purpose than in relation to the petition, unless they choose to sign
up for other services offered on this website. You can read more on
this in our <a href="/privacy">privacy policy</a>.
</p>

<p>
It will usually take up to five working days from the time a petition
proposal is received for it to appear on the website, although during
busy periods it may take longer. For more
information on the process, read our <a href="/steps">step-by-step guide</a>.
</p>

<p>
Your petition will be available on this website until the specified
closing date. If, however, during this time it becomes clear that your
petition is not being run in accordance with the terms, we reserve the
right to withdraw it. If this happens, we will contact you first to
allow you to address the concerns we raise and we will only remove the
petition as a last resort. 
</p>

<p>Please note that to keep the system manageable, and justify use of
resources, we can usually only respond to serious petitions of 200
signatures or more.</p>

<?

page_footer('TandCs');

