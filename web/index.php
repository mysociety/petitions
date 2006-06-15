<?
// index.php:
// Main code for PledgeBank website.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.1 2006-06-15 14:31:01 francis Exp $

// Load configuration file
require_once "../phplib/pet.php";
require_once '../../phplib/utility.php';
require_once '../../phplib/person.php';

page_header(_('Front page'));
?>
<p>Hello!</p>
<?
page_footer();

