<?
// steps.php:
// Details of the steps involved
//
// Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: steps.php,v 1.1 2006-07-27 12:57:16 matthew Exp $

require_once '../phplib/pet.php';
$page_title = _('Create a new petition');
page_header($page_title, array());
petition_form_intro();
page_footer();

function petition_form_intro() {
?>
<h1><span dir="ltr">Step-by-step guide to making petitions</span></h1>
<ol>

<h3><span dir="ltr">Step 1: Create your petition</span></h3>

<p>You will be asked to give your name, organisation (if you represent one),
address and email address, title and text of your petition. You will also be
asked to give a short, one-word name for your petition. This will be used to
give your petition a unique URL (website address) that you can use to publicise
your petition.</p>

<p>You will be able to specify a start and finish date for your petition, and we
will host your petition for up to 12 months.</p>

<h3><span dir="ltr">Step 2: Submit your petition</span></h3>

<p>Once you have submitted your petition, you will receive an email asking
you to click a link to confirm your petition. Your proposed petition will then
be delivered to the Downing Street inbox.</p>

<h3><span dir="ltr">Step 3: Petition approval</span></h3>

<p>Officials at Downing Street will check your petition to make sure that it meets
the basic requirements set out in our acceptable use policy (link) and the
Civil Service code.</p>

<p>If for any reason we cannot accept petition, we will write to you to explain
why. You will be able to edit and resubmit your petition if you wish.</p>

<p>Once your petition is approved, we will email you to confirm a date for it to
appear on the website.</p>

<p>If we cannot approve your amended petition, we will write to you again to
explain our reason(s). </p>

<p>Any petitions that are rejected or not resubmitted will be published on this
website, along with the reason(s) why it was rejected. Any content that is
offensive or illegal will be left out. Every petition that is received will be
acknowledged on this website.</p>

<h3><span dir="ltr">Step 4: Petition live</span></h3>

<p>Once your petition is live, you will be able to publicise the URL (website
address) you chose when you created your petition, and anyone will be able to
come to the website and sign it.</p>

<p>They will be asked to give their name and address and an email address that we
can verify. The system is designed to identify duplicate names and addresses,
will not allow someone to sign a petition more than once. Anyone signing a
petition will be sent an email asking them to click a link to confirm that they
have signed the petition. Once they have done this, their name will be added to
the petition.</p>

<p>Your petition will show the total number of signatures received. It will also
display the names of signatories, unless they have opted not to be shown.</p>

<h3><span dir="ltr">Step 5: Petition close</span></h3>

<p>When the petition closes, officials at Downing Street will ensure you get a
response to the issues you raise. Depending on the nature of the petition, this
may be from the Prime Minister, or he may ask one of his Ministers or officials
to respond.

<p>We will email the petition organiser and everyone who has signed the
petition via this website giving details of the Government’s response.

<form accept-charset="utf-8" method="post" action="/new">
<p style="text-align: right">
<input type="submit" name="tostepmain" value="Create a petition"></p>
</form>
<? 
}

?>

