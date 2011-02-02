<?
// tandcs.php:
// Terms & Conditions
//
// Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: terms.php,v 1.20 2010-04-27 10:05:26 matthew Exp $

// Load configuration file
require_once "../phplib/pet.php";
require_once '../phplib/cobrand.php';

if ($url = cobrand_terms_elsewhere()) {
    header("Location: $url");
    exit;
}

page_header('E-petitions terms and conditions');
cobrand_extra_heading('Terms and conditions');

$page = '../templates/' . $site_group . '/terms.html';
if (file_exists($page)) {
    include_once $page;
} else {
    # Default of a council one.
    include_once '../templates/surreycc/terms.html';
}

page_footer('TandCs');

