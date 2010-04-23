<?php
/*
 * cobrand.php:
 * Functions for different brandings of the petitions code.
 * 
 * Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org; WWW: http://www.mysociety.org
 * 
 */

function cobrand_creation_address_type() {
    global $site_name;
    if ($site_name == 'tandridge') return true;
    return false;
}

function cobrand_creator_must_be() {
    if (OPTION_SITE_TYPE == 'one' && !preg_match('#council#i', OPTION_SITE_PETITIONED)) {
        return 'British citizen or resident';
    } elseif (cobrand_creation_address_type()) {
        return 'council resident or work within the area of the council';
    } else {
        return 'council resident';
    }
}
