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
require_once '../phplib/cobrand.php';

if ($url = cobrand_privacy_policy_elsewhere()) {
    header("Location: $url");
    exit;
}

page_header('Privacy policy');

$file = '../templates/' . $site_group . '/privacy.html';
if (file_exists($file)) {
    include_once $file;
} else { ?>

<h2>Privacy policy</h2>

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
unless you ask us not to, to write to you about the
issues raised in the petition. In the future we may introduce a facility to
enable the creator of the petition to send you a small number of messages as
well; they will not have access to any of your details.</p>

<? }

page_footer('Privacy');

