<?php
/*
 * logout.php:
 * Log user out.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: logout.php,v 1.1 2006-06-20 14:14:26 francis Exp $
 * 
 */

require_once '../phplib/pet.php';

require_once '../phplib/page.php';
require_once '../../phplib/person.php';

header('Cache-Control: no-cache');

if (person_if_signed_on(true)) {
    person_signoff();
    header("Location: /logout");
    exit;
}

page_header(_('Logged out'));
print '<p>' . _('You\'re now logged out.  Thanks for using PledgeBank!') . '</p>';
page_footer(array('nolocalsignup'=>true));

?>
