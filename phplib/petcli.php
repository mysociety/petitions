<?php
/*
 * petcli.php:
 * Include file for PHP CLI scripts for ePetitions system.
 * Use pet.php instead for web pages.
 * 
 * Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org; WWW: http://www.mysociety.org
 *
 * $Id: petcli.php,v 1.1 2006-09-13 17:43:39 francis Exp $
 * 
 */

require_once "../conf/general";
require_once '../../phplib/phpcli.php';
require_once '../../phplib/db.php';

/* Date which petition application believes it is */
$pet_today = db_getOne('select ms_current_date()');
$pet_timestamp = substr(db_getOne('select ms_current_timestamp()'), 0, 19);
$pet_time = strtotime($pet_timestamp);

