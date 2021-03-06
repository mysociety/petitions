<?
// faq.php:
// FAQs
//
// Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: faq.php,v 1.23 2010-02-17 13:49:16 matthew Exp $

// Load configuration file
require_once "../phplib/pet.php";
require_once "../phplib/cobrand.php";

header('Cache-Control: max-age=300');
page_header('Frequently asked questions');

$contact_email = str_replace('@', '&#64;', OPTION_CONTACT_EMAIL);

$faq = '../templates/' . $site_group . '/faq.html';
if (file_exists($faq)) {
    include_once $faq;
} else {
    # Default of a council one.
    include_once '../templates/surreycc/faq.html';
}

page_footer('FAQ');

