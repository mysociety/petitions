<?
// test.php:
// Part of test harness.  See ../bin/test-run.pl for where this is called.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: test.php,v 1.2 2006-06-20 14:14:26 francis Exp $

require_once "../phplib/pet.php";

if (get_http_var('error')) {
    // Deliberately cause error by looking something up in an array which is not
    // there.
    $some_array = array();
    $some_variable = $some_array['deliberate_error_to_test_error_handling'];
}

if (get_http_var('phpinfo')) {
    phpinfo();
}

?> Test page <?

