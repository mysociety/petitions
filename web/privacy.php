<?
// privacy.php:
// Privacy Policy
//
// Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: privacy.php,v 1.7 2008-08-04 10:48:07 matthew Exp $

// Load configuration file
require_once "../phplib/pet.php";

page_header('E-petitions Privacy Policy');
?>

<h2 class="page_title_border">Privacy policy</h2>

<? if (OPTION_SITE_TYPE == 'pm') { ?>

<p>In addition to the <a href="http://www.number10.gov.uk/output/Page52.asp">normal
privacy policy</a> we have the following:</p>

<h3>E-petitions</h3>

<p>If you create an e-petition on the Downing Street website, you will be
required to provide us with basic personal information so that we can contact
you about your petition. The information will only be used for this purpose,
although we may need to pass your details to the relevant Government department
to enable them to respond to the issues you raise.</p>

<? } elseif (OPTION_SITE_TYPE == 'council') { ?>

<p>If you create an e-petition on this website, you will be
required to provide us with basic personal information so that we can contact
you about your petition. The information will only be used for this purpose,
although we may need to pass your details to the relevant department
to enable them to respond to the issues you raise.</p>

<? } ?>

<p>If you sign an e-petition on this website, you will be
required to provide us with basic personal information to enable us to verify
that "signatures" collected are genuine.  Your name (but no other details) will
be published on the petition on the website.</p>

<p>We will only use the information you provide us for this purpose, and,
unless you ask us not to, to write to you a maximum of two times about the
issues raised in the petition.  In the future we may introduce a facility to
enable the creator of the petition to send you a maximum of two messages as
well; they will not have access to any of your details.</p>

<p>mySociety, a charitable organisation, operates the e-petition service on our
behalf. mySociety's parent charity UKCOD is registered under the Data
Protection Act and, as our supplier, adheres to the terms of this privacy
policy. mySociety is not permitted to use the information that you provide us
for its own purposes.</p>

<?

page_footer('Privacy');

