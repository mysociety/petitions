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
    if (cobrand_creation_within_area_only()) return true;
    if ($site_name == 'tandridge') return true;
    return false;
}

function cobrand_creation_within_area_only() {
    global $site_name;
    if ($site_name == 'surreycc') return true;
    return false;
}

function cobrand_creator_must_be() {
    global $site_name;
    if ($site_name == 'surreycc') {
        return 'resident of, or have a business with a registered address in, Surrey County Council';
    }
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

function cobrand_category_okay($category_id) {
    global $site_name;
    if (in_array($site_name, array('tandridge')) && 
        in_array($category_id, array(3, 6, 7, 10, 11, 13, 14, 15, 18)))
        return false;
    if (in_array($site_name, array('surreycc')) &&
        in_array($category_id, array(1, 2, 4, 5, 8, 9, 17)))
        return false;
    return true;
}

function cobrand_categories() {
    global $site_name;
    if (in_array($site_name, array('tandridge', 'surreycc'))) {
        return array(
            1 => 'Building Regulations',
            2 => 'Council Tax Collection',
            3 => 'Education',
            4 => 'Elections',
            5 => 'Environmental Health',
            6 => 'Fire & Rescue',
            7 => 'Highways',
            8 => 'Housing',
            9 => 'Leisure & Recreation',
            10 => 'Libraries',
            11 => 'Passenger Transport',
            12 => 'Planning Applications',
            13 => 'Social Services',
            14 => 'Strategic Planning',
            15 => 'Transportation Planning',
            16 => 'Trading Standards',
            17 => 'Waste Collection',
            18 => 'Waste Disposal',
        );
    }

    global $global_petition_categories;
    return $global_petition_categories;
}

function cobrand_category($id) {
    $categories = cobrand_categories();
    return $categories[$id];
}
