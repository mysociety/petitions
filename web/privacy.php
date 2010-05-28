<?
// privacy.php:
// Privacy Policy
//
// Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: privacy.php,v 1.10 2010-01-14 18:26:15 matthew Exp $

// Load configuration file
require_once "../phplib/pet.php";

page_header('E-petitions Privacy Policy');
?>

<h2 class="page_title_border">Privacy policy</h2>

<? if (OPTION_SITE_NAME == 'number10') { ?>

<p>In addition to the <a href="http://www.number10.gov.uk/footer/privacy-policy">normal
privacy policy</a> we have the following:</p>

<h3>E-petitions</h3>

<p>If you create an e-petition on this website, you will be
required to provide us with basic personal information so that we can contact
you about your petition. The information will only be used for this purpose,
although we may need to pass your details to the relevant department
to enable them to respond to the issues you raise.</p>

<p>If you sign an e-petition on this website, you will be
required to provide us with basic personal information to enable us to verify
that "signatures" collected are genuine.  Your name (but no other details) will
be published on the petition on the website.</p>

<p>We will only use the information you provide us for this purpose, and,
unless you ask us not to, to write to you a maximum of two times about the
issues raised in the petition.  In the future we may introduce a facility to
enable the creator of the petition to send you a maximum of two messages as
well; they will not have access to any of your details.</p>

<p>mySociety, the project of a registered charity, operates the e-petition service on our
behalf. mySociety's parent charity UKCOD is registered under the Data
Protection Act and, as our supplier, adheres to the terms of this privacy
policy. mySociety is not permitted to use the information that you provide us
for its own purposes.</p>

<? } else { ?>

<p>If you create an e-petition on this website, you will be
required to provide us with basic personal information ??? so that we can contact
you about your petition. The information will only be used for this purpose,
although we may need to pass your details to the relevant department
to enable them to respond to the issues you raise. ???</p>

<p>If you sign an e-petition on this website, you will be
required to provide us with basic personal information to enable us to verify
that "signatures" collected are genuine.  Your name (but no other details) will
be published on the petition on the website.</p>

<p>We will only use the information you provide us for this purpose, and,
unless you ask us not to, to write to you a maximum of two times about the
issues raised in the petition. ???  In the future we may introduce a facility to
enable the creator of the petition to send you a maximum of two messages as
well; they will not have access to any of your details.</p>

<? }

page_footer('Privacy');

