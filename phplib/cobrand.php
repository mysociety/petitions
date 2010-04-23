<?php
/*
 * cobrand.php:
 * Functions for different brandings of the petitions code.
 * 
 * Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org; WWW: http://www.mysociety.org
 * 
 */

function cobrand_creation_ask_for_address_type() {
    global $site_name;
    if ($site_name == 'tandridge') return true;
    return false;
}

function cobrand_creation_within_area_only() {
    return false;
}

function cobrand_creator_must_be() {
    if (cobrand_creation_within_area_only()) {
        if (cobrand_creation_ask_for_address_type()) {
            return 'council resident or work within the area of the council';
        } else {
            return 'council resident';
        }
    } else {
        return 'British citizen or resident';
    }
}
