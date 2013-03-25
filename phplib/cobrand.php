<?php
/*
 * cobrand.php:
 * Functions for different brandings of the petitions code.
 * 
 * Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org; WWW: http://www.mysociety.org
 * 
 */

function cobrand_page_title($title) {
    global $site_name;
    if ($site_name != 'islington')
        return $title;
    return str_replace('petition', 'Petition', $title);
}

# return true if council prefers "We, the undersigned," to "We the undersigned"
function cobrand_we_the_undersigned_use_commas() {
    global $site_name;
    if ($site_name == 'suffolkcoastal')
        return true;
    return false;
}

function cobrand_create_button_title() {
    global $site_name;
    if ($site_name == 'suffolkcoastal')
        return ' title="Create a petition" ';
    return '';
}

function cobrand_view_button_title() {
    global $site_name;
    if ($site_name == 'suffolkcoastal')
        return ' title="View petitions" ';
    return '';
}

# The help sentence printed under the main content of a petition's input box.
function cobrand_creation_sentence_help() {
    global $site_group, $site_name;
    $out = '';
    if ($site_name != 'salford')
        $out .= '(';
    $out .= 'Please write a sentence';
    if ($site_group != 'surreycc' && $site_group != 'westminster' && $site_group != 'stevenage' && $site_name != 'bassetlaw' && $site_group != 'suffolkcoastal') {
        $out .= ', preferably starting with a verb,';
    }
    $out .= ' that describes what action you would like ';
    $out .= OPTION_SITE_PETITIONED;
    $out .= ' to take';
    if ($site_name != 'spelthorne' && $site_name != 'salford')
        $out .= '.';
    if ($site_name != 'salford')
        $out .= ')';
    return $out;
}

function cobrand_creation_default_deadline() {
    global $site_name;
    if ($site_name == 'elmbridge') return '90 days';
}

function cobrand_creation_address_help() {
    global $site_name;
    if ($site_name == 'spelthorne') {
        print '<br>(Please use the address where you live, work or study in Spelthorne)';
    }
    elseif ($site_name == 'rbwm') {
        print '<br>(Please use the address where you live, work or study within the Royal Borough)';
    }

}

function cobrand_creation_email_request() {
    global $site_name;
    if ($site_name == 'ipswich')
        return 'We need this to get in touch with you e.g. when your petition ends';
    if ($site_name == 'westminster')
        return 'We need your contact details so we can get in touch with you.<br/>
        Your details other than your name will not be published.';
    if ($site_name == 'runnymede')
        return 'We need your contact details so we can get in touch with you.<br/>
        Your details other than your name will not be published.';
}

function cobrand_creation_deadline_limit($body='') {
    global $site_name;
    if (!$body) $body = $site_name;

    if ($body == 'east-northamptonshire')
        return array('years' => 0, 'months' => 1);
    if ($body == 'tandridge' || $body == 'surreycc' || $body == 'rbwm' || $body == 'stevenage' || $body == 'suffolkcoastal' || $body == 'forest-heath')
        return array('years' => 0, 'months' => 6);
    if ($body == 'wellingborough')
        return array('years' => 0, 'months' => 4);
    if ($body == 'eastcambs' || $body == 'stedmundsbury' || $body == 'westminster')
        return array('years' => 0, 'months' => 3);
    if ($body == 'barrowbc' || $body == 'bassetlaw')
        return array('date' => '2011-12-01');
    return array('years' => 1, 'months' => 0);
}

function cobrand_creation_duration_help() {
    global $site_name;
    if ($site_name == 'islington') return '. The duration of your petition starts from the time it is approved.';
    return '';
}

function cobrand_creation_example_ref() {
    global $site_name;
    if ($site_name == 'spelthorne') return 'recycle';
    return 'badgers';
}

function cobrand_creation_short_name_label() {
    global $site_name;
    if ($site_name == 'bassetlaw'){
        return 'Choose a short name for your petition that\'s 6 to 16 letters long: <br/><small>Use only letters, numbers, or a hyphen &mdash; no spaces or punctuation.</small><br/>';
    }
    return 'Choose a short name for your petition (6 to 16 letters):';
}

function cobrand_creation_category_first() {
    global $site_group;
    if ($site_group == 'surreycc' || $site_group == 'nottinghamshire' || $site_group == 'eastcambs' || $site_group == 'salford') {
        return true;
    }
    return false;
}

function cobrand_creation_address_type_label() {
    global $site_name;
    if ($site_name == 'stevenage') return 'Connection with Stevenage';
    return 'Type of address';
}

function cobrand_creation_ask_for_address_type() { # by default: don't ask for address type unless it's within a specified area
    global $site_name;
    if ($site_name == 'barrowbc') return false; 
    if ($site_name == 'suffolkcoastal') return false; 
    if (cobrand_creation_within_area_only()) return true;
    if ($site_name == 'ipswich') return true;
    if ($site_name == 'newforest') return true;
    if ($site_name == 'stevenage') return true;
    if ($site_name == 'surreyheath') return true;
    if ($site_name == 'suffolkcoastal') return true;
    if ($site_name == 'tandridge') return true;
    return false;
}

# If creation should be limited to a particular area, this
# function should return a two-element array, consisting of
# the name of the area, and either an area ID that the
# creator must be within, or null if the creator can be in
# any area in the site database.
function cobrand_creation_within_area_only() {
    global $site_name;
    if ($site_name == 'ashfield-dc') return array('Ashfield', 2413);
    if ($site_name == 'barnet') return array('Barnet', 2489);
    if ($site_name == 'barrowbc') return array('Cumbria', 2220);
    if ($site_name == 'bassetlaw') return array('Bassetlaw', 2410);
    if ($site_name == 'blackburn') return array('the borough of Blackburn with Darwen', 2552);
    if ($site_name == 'eastcambs') return array('East Cambridgeshire', 2261);
    if ($site_name == 'east-northamptonshire') return array('East Northamptonshire', 2393);
    if ($site_name == 'elmbridge') return array('Elmbridge', 2455);
    if ($site_name == 'epsom-ewell') return array('Epsom &amp; Ewell', 2457);
    if ($site_name == 'forest-heath') return array('Forest Heath', 2444);
    if ($site_name == 'guildford') return array('Guildford', 2452);
    if ($site_name == 'hounslow') return array('Hounslow', 2483);
    if ($site_name == 'islington') return array('Islington', 2507); # actually Islington requested "County Council" -- maybe meant Greater London?
    if ($site_name == 'mansfield') return array('Mansfield', 2416);
    if ($site_name == 'melton') return array('Melton', 2374);
    if ($site_name == 'molevalley') return array('Mole Valley', 2454);
    if ($site_name == 'reigate-banstead') return array('Surrey', null);
    if ($site_name == 'runnymede') return array('Runnymede', 2451);
    if ($site_name == 'rushcliffe') return array('Rushcliffe', 2411);
    if ($site_name == 'rbwm') return array('the Royal Borough of Windsor and Maidenhead', 2622);
    if ($site_name == 'salford') return array('Salford', 2534);
    if ($site_name == 'sholland') return array('South Holland', 2381);
    if ($site_name == 'spelthorne') return array('Spelthorne', 2456);
    if ($site_name == 'stedmundsbury') return array('the borough of St Edmundsbury', 2443);
    if ($site_name == 'surreycc') return array('Surrey', null);
    if ($site_name == 'waverley') return array('Waverley', 2447);
    if ($site_name == 'westminster') return array('Westminster', 2504);
    if ($site_name == 'wellingborough') return array('Wellingborough', 2395);
    if ($site_name == 'woking') return array('Woking', 2449);
    if ($site_name == 'nottinghamshire') return array('Nottinghamshire', 2236);
    return '';
}

function cobrand_creator_must_be() {
    global $site_name;
    if ($site_name == 'suffolkcoastal')
        return ''; 
    $creator_type = '';
    if ($site_name == 'surreycc' || $site_name == 'reigate-banstead'){
        $creator_type = 'must live, work or study at a Surrey registered address';
    } elseif ($site_name == 'woking'){
        $creator_type = 'must live, work or study in the Borough of Woking';
    } elseif ($site_name == 'elmbridge'){
        $creator_type = 'must live, work or study within Elmbridge (including under 18s)';
    } elseif ($site_name == 'stevenage'){
        $creator_type = 'should live, work or study within Stevenage';
    } elseif ($area = cobrand_creation_within_area_only()) {
        $creator_type = 'must live, work or study within ' . $area[0];
    } else {
        $creator_type = 'must be a British citizen or resident';
    }
    return 'Please note that you ' . $creator_type . ' to create a petition.';
}

function cobrand_creation_check_heading() {
    global $site_name;
    if ($site_name == 'islington')
        return 'Petition Submitted';
    return 'Now check your email';
}

function cobrand_creation_top_submit_button() {
    global $site_name;
    if ($site_name == 'westminster' || $site_name == 'stevenage') return false;
    return true;
}

# Allows 'red asterisk' (or similar) to mark mandatory fields
# returns array of markers: 0 for optional input fields, 1 for mandatory, and a legend
# the optional marker forces same-width span just to make layout easy on sites that use these
function cobrand_input_field_mandatory_markers() { 
    global $site_name;
    if ($site_name == 'suffolkcoastal' || $site_name == 'bassetlaw')
        return array('<span class="mandatory">&nbsp;</span>', '<span class="mandatory">*</span>', '<span class="mandatory_legend">Fields marked <span class="mandatory">*</span> are mandatory.</span>');
    return array('','','');
}

function cobrand_creation_ask_for_address() {
    global $site_name;
    #if ($site_name == 'westminster') {
    #    return false;
    #}
    return true;
}

function cobrand_creation_do_address_lookup() {
    global $site_name;
    if ($site_name == 'islington') {
        return true;
    }
    return false;
}

function cobrand_perform_address_lookup($pc) {
    $f = @file_get_contents('http://webgis.islington.gov.uk/Website/WebServices/LLPGSearch/LLPGSearchService.asmx/LLPGSearch?searchTerms=' . urlencode(canonicalise_postcode($pc)));
    $out = array();
    if (!$f) {
        $out['errors'] = 'Sorry, the Islington address lookup is currently not working. Please try again later.';
    } elseif (preg_match('#<errorDescription>(.*?)</errorDescription>#', $f, $m)) {
        $out['errors'] = $m[1];
        if (preg_match('#^There were no addresses matched with these search terms#', $out['errors']))
            $out['errors'] = 'Sorry, that postcode does not appear to be within Islington';
    } else {
        preg_match_all('#<CATADDRESS>(.*?)</CATADDRESS>#', $f, $m);
        $out['data'] = $m[1];
    }
    return $out;
}

function cobrand_creation_postcode_optional() {
    global $site_name;
    if ($site_name == 'suffolkcoastal') {
        return true;
    }
    return false;    
}

function cobrand_creation_phone_number_optional() {
    global $site_name;
    if ($site_name == 'islington' || $site_name == 'suffolkcoastal') {
        return true;
    }
    return false;
}

function cobrand_creation_comments_label(){
    global $site_name;
    if ($site_name == 'westminster'){
      return "If you have any information about your petition you would like us
         to know that you do not wish to be public, please include them here:"; # them [sic]
    }
    return "If you have any special requests concerning your
        petition, or information about your petition you would like us 
        to know that you do not wish to be public, please include them here:";
}

function cobrand_creation_previous_button_first() {
    global $site_name;
    if ($site_name == 'ipswich') return true;
    return false;
}

function cobrand_creation_button_separator() {
    global $site_name;
    if ($site_name == 'salford') return ' &nbsp; ';
    return '<br />';
}

function cobrand_creation_extra_footer() {
    global $site_name;
    if ($site_name != 'runnymede') return;
?>
<p class="banner">
<a href="http://www.runnymede.gov.uk/portal/site/runnymede/menuitem.12d3579a97fd8623fa43a310af8ca028/">Terms and conditions</a>
| <a href="http://www.runnymede.gov.uk/portal/site/runnymede/menuitem.eac7b227d6b697ef53d2dd85af8ca028/">Step by step guide</a>
| <a href="http://www.runnymede.gov.uk/portal/site/runnymede/menuitem.9e3786f0e4a5a623fa43a310af8ca028/">Questions about petitions</a>
| <a href="http://www.runnymede.gov.uk/portal/site/runnymede/menuitem.40b9386d6ff92926fa43a310af8ca028/">Petitions Scheme</a>
</p>
<?
}

function cobrand_creation_input_class() {
    global $site_name;
    if ($site_name == 'salford') return array('input', 'largeField');
}

function cobrand_creation_submit_button_class() {
    global $site_name;
    if ($site_name == 'salford') return 'confirmButton';
}

function cobrand_creation_main_all_newlines() {
    global $site_name;
    if ($site_name == 'salford') return true;
    return false;
}

function cobrand_error_div_start() {
    global $site_name;
    if ($site_name == 'salford') return '<div class="error">';
    if ($site_name == 'surreycc') return '<div class="scc-error">';
    return '<div id="errors">';
}

function cobrand_postcode_label() {
    global $site_name;    
    if ($site_name == 'suffolkcoastal')
        return 'Postcode';
    return _('UK postcode');
}
  
function cobrand_overseas_dropdown() {
    global $site_group;
    if ($site_group == 'suffolkcoastal'){
        return '';
    }
    if ($site_group == 'surreycc') {
        return array(
            '-- Select --',
            'Armed Forces',
            'Non UK address'
        );
    }
    return array(
        '-- Select --',
        'Expatriate',
        'Armed Forces',
        'Anguilla',
        'Ascension Island',
        'Bermuda',
        'British Antarctic Territory',
        'British Indian Ocean Territory',
        'British Virgin Islands',
        'Cayman Islands',
        'Channel Islands',
        'Falkland Islands',
        'Gibraltar',
        'Isle of Man',
        'Montserrat',
        'Pitcairn Island',
        'St Helena',
        'S. Georgia and the S. Sandwich Islands',
        'Tristan da Cunha',
        'Turks and Caicos Islands',
    );
}

function cobrand_click_create_instuction() {
    global $site_name;
    if ($site_name == 'suffolkcoastal')
        return 'click <strong>Create</strong>';
    return '<strong>click "Create"</strong>'; 
}

# note: numbers here are category numbers, defined in cobrand_categories()
#       Duplicated in anticipation of different councils splitting these responsibilities differently.
function cobrand_category_okay($category_id) {
    global $site_name, $site_group;
    if ($site_group == 'surreycc') {
        $county_only = array(4, 6, 7, 10, 12, 13, 16);
        if ($site_name == 'tandridge' || $site_name == 'reigate-banstead' || $site_name == 'elmbridge' || $site_name == 'surreyheath')
            $county_only[] = 11; # Planning not okay
        if ($site_name != 'surreycc' && in_array($category_id, $county_only))
            return false;
        $district_only = array(1, 2, 3, 5, 8, 9, 15);
        if ($site_name == 'surreycc' && in_array($category_id, $district_only))
            return false;
    } elseif ($site_group == 'nottinghamshire') {
        if ($category_id == 6) return false;
        $county_only = array(4, 7, 10, 12, 13, 14, 17);
        if ($site_name != 'nottinghamshire' && in_array($category_id, $county_only))
            return false;
        $district_only = array(1, 3, 5, 8, 9, 11, 16);
        if ($site_name == 'nottinghamshire' && in_array($category_id, $district_only))
            return false;
    } elseif ($site_group == 'eastcambs') {
        if ($category_id == 11) return false; # Planning
    } elseif ($site_group == 'salford') {
        if ($category_id == 1 || $category_id == 11) return false; # Planning, or alcohol/gambling
    }
    return true;
}

function cobrand_category_wrong_action($category_id, $area='') {
    global $site_name, $site_group;
    if ($site_group == 'surreycc') {
        if ($site_name != 'surreycc') {
            if ($site_name == 'tandridge' && $category_id == 11) { # Planning
                return "You cannot create a petition about a planning
application. For further information on the Council's procedures and how you
can express your views, see the
<a href='http://www.tandridge.gov.uk/Planning/planninginteractive/default.htm'>planning
applications</a> section.";
            } elseif ($site_name == 'surreyheath' && $category_id == 11) { # Planning
                return "You cannot create a petition about a planning
application. For further information on the Council's procedures and how you
can express your views, see the
<a href='http://www.surreyheath.gov.uk/planning/default.htm'>planning
applications</a> section.";
            } elseif ($site_name == 'reigate-banstead' && $category_id == 11) { # Planning
                return "You cannot create a petition about a planning
application. For further information on the Council's procedures and how you
can express your views, see the
<a href='http://www.reigate-banstead.gov.uk/planning/'>planning
applications</a> section.";
            } elseif ($site_name == 'elmbridge' && $category_id == 11) { # Planning
                return "We are unable to accept an e-petition through this
facility in relation to a specific planning application as there is a
<a href='http://www.elmbridge.gov.uk/planning/online.htm'>separate
process for planning representations</a>.";
            } else {
                $url = 'http://petitions.surreycc.gov.uk/new?tostepmain=1&category=' . $category_id;
                return "You are petitioning about something which isn't the
responsibility of your district council, but instead of Surrey County Council.
<a href='$url'>Go to Surrey County Council's petition website to create a
petition in this category</a>."; 
            }
        }
        if ($area) {
            # $area is set if we're being called as a result of the form below
            if ($area == 'epsom-ewell')
                return 'http://www.epsom-ewell.gov.uk/EEBC/Council/E-petitions.htm';
            return 'http://petitions.' . $area . '.gov.uk/new?tostepmain=1&category=' . $category_id;
        } else {
            return '
            <input type="hidden" name="category" value="' . $category_id . '"> 
            You are petitioning about something which isn\'t the responsibility of Surrey Council Council,
            but instead of your district council. <label for="council_pick">Please
            pick your district council in order to be taken to their petition site:</label>
            <select name="council" id="council_pick">
            <option value="elmbridge">Elmbridge Borough Council</option>
            <option value="epsom-ewell">Epsom and Ewell Borough Council</option>
            <option value="guildford">Guildford Borough Council</option>
            <option value="molevalley">Mole Valley District Council</option>
            <option value="reigate-banstead">Reigate &amp; Banstead Borough Council</option> 
            <option value="runnymede">Runnymede Borough Council</option>
            <option value="spelthorne">Spelthorne Borough Council</option>
            <option value="surreyheath">Surrey Heath Borough Council</option> 
            <option value="tandridge">Tandridge District Council</option> 
            <option value="waverley">Waverley Borough Council</option> 
            <option value="woking">Woking Borough Council</option> 
            </select> 
            <input type="submit" name="toothercouncil" value="Go" class="button">
            '; 
        }
    }
    
    if ($site_group == 'nottinghamshire') {
        if ($site_name != 'nottinghamshire') {
           # check for specific council+category exceptions?
           $url = 'http://petitions.nottinghamshire.gov.uk/new?tostepmain=1&category=' . $category_id;
           return "You are petitioning about something which isn't the
responsibility of your district council, but instead of Nottinghamshire County Council.
<a href='$url'>Go to Nottinghamshire County Council's petition website to create a
petition in this category</a>."; 
        }
        if ($category_id == 6) { # Fire
            return 'The fire service is the responsibility of the
Nottinghamshire and City of Nottingham Fire Authority. For more information see
<a href="http://www.notts-fire.gov.uk/">www.notts-fire.gov.uk</a>.';
        }
        if ($area) {
            # $area is set if we're being called as a result of the form below
            # currently handling all mySociety-hosted Notts district councils the same:
            #if (in_array($area, array('ashfield-dc', 'bassetlaw', 'mansfield', 'rushcliffe')))
            #    return 'http://petitions.' . $area . '.gov.uk/new?tostepmain=1&category=' . $category_id;
            if ($area == 'ashfield-dc')
                return 'http://www.ashfield-dc.gov.uk/ccm/navigation/council--government-and-democracy/petition-scheme/';
            if ($area == 'bassetlaw')
                return 'http://www.bassetlaw.gov.uk/services/council__democracy/petitions.aspx';
            if ($area == 'mansfield')
                return 'http://www.mansfield.gov.uk/index.aspx?articleid=3672';
            if ($area == 'rushcliffe')
                return 'http://www.rushcliffe.gov.uk/doc.asp?cat=11391&doc=11229';
            if ($area == 'broxtowe')
                return 'http://www.broxtowe.gov.uk/index.aspx?articleid=8181';
            if ($area == 'gedling')
                return 'http://www.gedling.gov.uk/'; # no petitions page found
            if ($area == 'newark-sherwooddc')
                return 'http://www.newark-sherwooddc.gov.uk/pp/gold/viewGold.asp?IDType=Page&ID=21319';
            if ($area == 'nottingham')
                return 'http://www.nottinghamcity.gov.uk/index.aspx?articleid=12595'; # e-petitions page
        } else {
            return '
            <input type="hidden" name="category" value="' . $category_id . '"> 
            You are petitioning about something which isn\'t the responsibility of Nottinghamshire Council Council,
            but instead of your district council. <label for="council_pick">Please
            pick your district council in order to be taken to their site:</label>
            <select name="council" id="council_pick">
            <option value="ashfield-dc">Ashfield District Council</option>
            <option value="bassetlaw">Bassetlaw District Council</option>
            <option value="broxtowe">Broxtowe Borough Council</option>
            <option value="gedling">Gedling Borough Council</option>
            <option value="mansfield">Mansfield District Council</option>
            <option value="nottingham">Nottingham City Council</option>
            <option value="newark-sherwooddc">Newark and Sherwood District Council</option>
            <option value="rushcliffe">Rushcliffe Borough Council</option>
            </select> 
            <input type="submit" name="toothercouncil" value="Go" class="button">
            '; 
        }
    }

    if ($site_group == 'eastcambs') {
        if ($category_id == 11) { # Planning
            return "We are unable to accept an e-petition through this
facility in relation to a specific planning application as there is a
<a href='http://www.eastcambs.gov.uk/planning/planning-services'>separate
process for planning representations</a>.";
        }
    }

    if ($site_group == 'salford') {
        if ($category_id == 1) { # Alcohol/gambling/etc.
            return "We can not accept a petition about an alcohol, gambling or
sex establishment licensing decision; please contact the
<a href='http://www.salford.gov.uk/licensing.htm'>Licensing Team</a> for
further information.";
        } elseif ($category_id == 11) { # Planning
            return "We can not accept a petition on an individual planning
application, including about a development plan document or the community
infrastructure levy; please contact the
<a href='http://www.salford.gov.uk/planning-policy.htm'>Spatial Planning
Team</a> for further information.";
        }
    }

    return null;
}

# Could be run from cron (e.g. send-messages), so have ALL parameter
# to return everything from a particular group
function cobrand_categories($override_site_name = '') {
    global $site_name, $site_group;
    $sn = $site_name;
    if ($override_site_name) $sn = $override_site_name;
    if ($site_group == 'surreycc') {
        $cats = array(
            1 => 'Building Regulations',
            2 => 'Community safety',
            3 => 'Council Tax Collection',
            4 => 'Education',
            5 => 'Environmental Health',
            6 => 'Fire & Rescue',
            7 => 'Highways',
            8 => 'Housing',
            9 => 'Leisure and Recreation',
            10 => 'Libraries',
            11 => 'Planning Applications', # Both?
            12 => 'Social Services',
            13 => 'Transport and Travel',
            14 => 'Trading Standards', # Both?
            15 => 'Waste Collection',
            16 => 'Waste Disposal',
        );
        if ($sn == 'elmbridge') {
            $cats[17] = 'Parking';
            asort($cats);
        }
        if ($sn == 'runnymede') {
            $cats[18] = 'Recycling Service';
            $cats[19] = 'Refuse Service';
            unset($cats[15]);
            unset($cats[16]);
            asort($cats);
        }
        $cats[99] = 'Other'; # Both
        return $cats;
    }
    if ($site_group == 'nottinghamshire') {
        $cats = array(
            1 => 'Building Regulations',
            2 => 'Community Safety',
            3 => 'Council Tax Collection',
            4 => 'Education',
            5 => 'Environmental Health',
            6 => 'Fire & Rescue',
            7 => 'Highways',
            8 => 'Housing',
            9 => 'Leisure and Recreation',
            10 => 'Libraries',
            11 => 'Planning and Development Control',
            12 => 'Minerals and Waste planning',
            13 => 'Social Services',
            14 => 'Transport and Travel',
            15 => 'Trading Standards', # Both?
            16 => 'Waste Collection',
            17 => 'Waste Disposal',
        );
        $cats[99] = 'Other'; # Both
        return $cats;
    }
    if ($site_group == 'eastcambs') {
        return array(
            1 => 'Community Development/Grants',
            2 => 'Community Safety',
            3 => 'Car Parking',
            4 => 'Council Tax/Council Finances',
            5 => 'Council Land/Buildings',
            6 => 'Employment/Business Support/Economic Development',
            7 => 'Environmental Health/Pollution',
            8 => 'Highways',
            9 => 'Housing/Homelessness',
            10 => 'Leisure and Recreation',
            11 => 'Planning',
            12 => 'Public Conveniences',
            13 => 'Tourism',
            14 => 'Town Centres/Markets',
            15 => 'Transport',
            16 => 'Waste Collection/Recycling',
            99 => 'Other', # Both
        );
    }
    if ($site_group == 'ipswich') {
        return array(
            1 => 'Building Control',
            2 => 'Community Safety',
            3 => 'Council Tax',
            4 => 'Economic Development',
            5 => 'Environmental',
            6 => 'Finance',
            7 => 'Housing',
            8 => 'Information and Communication',
            9 => 'Licensing',
            10 => 'Legal and Democratic',
            11 => 'Leisure and Culture',
            12 => 'Planning',
            13 => 'Transport and Highways',
            14 => 'Waste Collection',
            15 => 'Waste Disposal',
            99 => 'Other', # Both
        );
    }
    if ($site_group == 'newforest') {
        return array(
            1 => 'Building Regulations',
            2 => 'Community Safety',
            3 => 'Council Tax',
            4 => 'Employment/Business Support',
            5 => 'Environmental Health',
            6 => 'Highways',
            7 => 'Housing',
            8 => 'Leisure and Recreation',
            9 => 'Planning',
            10 => 'Tourism',
            11 => 'Transport',
            12 => 'Waste Collection',
            13 => 'Waste Disposal',
            99 => 'Other', # Both
        );
    }
    if ($site_group == 'salford') {
        return array(
            1 => 'Alcohol, gambling or sex establishment licensing decision',
            2 => 'Building regulations',
            3 => 'Community safety',
            4 => 'Council tax collection',
            5 => 'Education',
            6 => 'Environment',
            7 => 'Highways and street lighting',
            8 => 'Housing',
            9 => 'Leisure and recreation',
            10 => 'Libraries',
            11 => 'Planning applications',
            12 => 'Planning services',
            13 => 'Social Services',
            14 => 'Transport and travel',
            15 => 'Trading standards',
            16 => 'Waste collection',
            17 => 'Waste disposal',
            99 => 'Other',
        );
    }

    global $global_petition_categories;
    return $global_petition_categories;
}

function cobrand_category($id, $override_site_name='') {
    $categories = cobrand_categories($override_site_name);
    return $categories[$id];
}

# Could be run from cron (e.g. send-messages), so examine site_group
function cobrand_display_category() {
    global $site_group;
    if ($site_group == 'westminster' || $site_group == 'suffolkcoastal' || $site_group == 'stevenage') return false;
    return true;
}

function cobrand_signature_threshold() {
    global $site_name;
    if ($site_name == 'number10') return 500;
    if ($site_name == 'surreycc') return 100;
    if (in_array($site_name, array('woking', 'tandridge'))) return 10;
    if (in_array($site_name, array('surreyheath', 'waverley', 'runnymede'))) return 50;
    return 100;
}

# This function could be run from cron, so can't just use site_name
function cobrand_site_group() {
    if (strpos(OPTION_SITE_NAME, ',')) {
        $sites = explode(',', OPTION_SITE_NAME);
        $site_group = $sites[0];
    } else {
        $site_group = OPTION_SITE_NAME;
    }
    return $site_group;
}

function cobrand_admin_email($body) {
    if ($body == 'elmbridge') {
        $local = 'petitions';
        $domain = $body . '.gov.uk';
    } elseif (OPTION_SITE_TYPE == 'multiple') {
        $local = $body;
        $domain = OPTION_EMAIL_DOMAIN;
    } else {
        return OPTION_CONTACT_EMAIL;
    }
    return $local . '@' . $domain;
}

# Runs from cron, so examine site_group or petition body.
function cobrand_admin_email_finished($body) {
    global $site_group;
    if ($site_group == 'hounslow' || $site_group == 'islington' || $site_group == 'westminster' || $site_group == 'ipswich') return true;
    if ($body == 'elmbridge' || $body == 'woking') return true;
    return false;
}

function cobrand_admin_email_post_finished() {
    global $site_group;
    if ($site_group == 'westminster') return '1 month';
    return '';
}

# Returns number of days you have to resubmit a rejected petition,
# if different from normal. Runs from cron.
# For multi-council arrays, make sure 'other' is always the numerically greatest timeout (if not: specificy them all, explicitly)
function cobrand_rejected_petition_timeout() {
    global $site_group; # No $site_name available
    if ($site_group == 'westminster') return '8 days';
    if ($site_group == 'nottinghamshire') return array('bassetlaw' => '20 days', 'rushcliffe' => '15 days', 'other' => '29 days');
    if ($site_group == 'surreycc') return array('elmbridge' => '15 days', 'guildford' => '15 days', 'woking' => '10 days', 'other' => '29 days');
    // 29 days is 4 weeks, plus a day to allow a margin for the creator
    return '29 days';
}

function cobrand_admin_is_site_user() {
    $sites = explode(',', OPTION_SITE_NAME);
    $user = http_auth_user();
    if (preg_match('#@([^.]*)\.#', $user, $m))
        $user = $m[1];
    if (in_array($user, $sites))
        return $user;
    return false;
}

function cobrand_admin_title() {
    global $site_group;
    if ($site_group == 'surreycc' || $site_group == 'nottinghamshire') {
        if ($site = cobrand_admin_is_site_user())
            return ucfirst($site) . ' admin';
    }
    return OPTION_CONTACT_NAME . " admin";
}

function cobrand_admin_style() {
    global $site_name;
    if ($site_name == 'islington') return 'pet-admin-islington.css';
}

function cobrand_admin_rejection_snippets() {
    global $site_group;
    $snippets = array(
'Please supply full name and address information.',
'Please address the excessive use of capital letters; they make your petition hard to read.',
'Your title should be a clear call for action, preferably starting with a verb, and not a name or statement.',
    );
    if ($site_group != 'number10') {
        return $snippets;
    }
    array_push($snippets,
'Comments about the petitions system should be sent to ' . OPTION_CONTACT_EMAIL . '.',
'Individual legal cases are a matter for direct communication with the Home Office.',
'This is a devolved matter and should be directed to the Scottish Executive / Welsh Assembly / Northern Ireland Executive as appropriate.',
'This is a matter for direct communication with Parliament.',
'The Cabinet Office is actively seeking nominations for honours from the public. Please go to http://www.direct.gov.uk/honours'
    );
    return $snippets;
}

function cobrand_admin_rejection_categories() {
    global $global_rejection_categories, $site_group;
    $categories = $global_rejection_categories;
    $site_user = cobrand_admin_is_site_user();
    if ($site_group != 'eastcambs' && ($site_group != 'nottinghamshire' || ($site_user != 'bassetlaw' && $site_user != ''))) {
        unset($categories[131072]); # only Bassetlaw and East Cambs use "Currently being administered via another process"
    }
    if ($site_group == 'number10' || $site_group == 'ipswich') {
        return $categories;
    }
    unset($categories[65536]); # Links to websites
    return $categories;
}

function cobrand_admin_site_restriction() {
    global $site_group;
    if ($site_group != 'surreycc' && $site_group != 'nottinghamshire') return '';

    if ($site = cobrand_admin_is_site_user())
        return " AND body.ref='$site' ";
    return '';
}

function cobrand_admin_allow_html_response() {
    global $site_group;
    if ($site_group == 'number10') return true;
    return false;
}

# Admin, so only site_group available
function cobrand_admin_areas_of_interest() {
    global $site_group;

    if ($site_group == 'sbdc' || $site_group == 'sbdc1') {
        return json_decode(file_get_contents('http://mapit.mysociety.org/areas/LBO,MTD,LGD,DIS,UTA,COI'), true);
    }
    if ($site_group == 'lichfielddc') {
        return array(
            2434 => array( 'name' => 'Lichfield District Council', 'parent_area' => 2240 ),
            2240 => array( 'name' => 'Staffordshire County Council' ),
        );
    }

    if ($site_group == 'hounslow') {
        $wards = json_decode(file_get_contents('http://mapit.mysociety.org/area/2483/children'), true);
        $soas = json_decode(file_get_contents('http://mapit.mysociety.org/areas/Hounslow?type=OLF'), true);
        foreach ($soas as $k => $v) {
            $soas[$k]['parent_area'] = 2483;
        }
        $out = $wards + $soas;
        $out[2483] = array( 'name' => 'Hounslow Borough Council' );
        return $out;
    }

    if ($site_group == 'islington') {
        $out = json_decode(file_get_contents('http://mapit.mysociety.org/area/2507/children'), true);
        $out[2507] = array( 'name' => 'Islington Borough Council' );
        return $out;
    }

    if ($site_group != 'surreycc') return null;

    $user_to_area_id = array(
        'surreycc' => 2242,
        'elmbridge' => 2455,
        'epsom-ewell' => 2457,
        'guildford' => 2452,
        'molevalley' => 2454,
        'reigate-banstead' => 2453,
        'runnymede' => 2451,
        'spelthorne' => 2456,
        'surreyheath' => 2450,
        'tandridge' => 2448,
        'waverley' => 2447,
        'woking' => 2449,
    );
    $out = json_decode(file_get_contents("http://mapit.mysociety.org/areas/" . join(',', array_values($user_to_area_id))), true);
    foreach ($out as $k => $v) {
        if ($v['id'] == 2242) continue;
        $out[$k]['parent_area'] = 2242;
    }
    if ($user = cobrand_admin_is_site_user()) {
        $wards = json_decode(file_get_contents("http://mapit.mysociety.org/area/$user_to_area_id[$user]/children"), true);
        $out += $wards;
    }
    return $out;
}

# Admin, so only site_group available
function cobrand_admin_show_map() {
    global $site_group;
    if (in_array($site_group, array(
        'hounslow', 'sbdc', 'surreycc',
    )))
        return true;
    if (get_http_var('test_map')) return true;
    return false;
}

function cobrand_admin_show_graphs() {
    global $site_group;
    if ($site_group == 'hounslow') return false;
    return true;
}

function cobrand_admin_wards_for_petition() {
    global $site_group;
    if ($site_group == 'hounslow' || $site_group == 'sbdc') {
        if ($site_group == 'hounslow') $id = 2483;
        if ($site_group == 'sbdc') $id = 2246;
        $out = json_decode(file_get_contents("http://mapit.mysociety.org/area/$id/children"), true);
        uasort($out, 'sort_by_name');
        $out = array( -1 => array( 'id' => -1, 'name' => 'All wards' ) ) + $out;
        return $out;
    }
    return false;
}

function cobrand_admin_responsible_option() {
    global $site_group;
    if ($site_group == 'hounslow') return true;
    if ($site_group == 'sbdc') return true;
    return false;
}

# Whether petitions can be archived or not (ie. response/closed from council
# point of view). This can be just for admins, or display differently on list
# pages as well.

function cobrand_archive_admin() {
    global $site_group;
    if ($site_group == 'hounslow') return true;
    if ($site_group == 'surreycc') return true;
    return false;
}

function cobrand_archive_front_end() {
    global $site_group;
    if ($site_group == 'hounslow') return true;
    return false;
}

# A bit of a yucky function, containing slightly varying guidelines
# for displaying at last stage of petition creation process.
function cobrand_petition_guidelines() {
    global $site_group, $site_name;

    if ($site_name == 'stevenage') {
?>

<p>In order for the Council to deal with your petition you must not 
include anything which could be considered to be vexatious, abusive or 
otherwise inappropriate.</p>

<p>We reserve the right to reject petitions that are similar to and/or 
overlap with an existing petition or petitions or which ask for things 
outside the remit or powers of Stevenage Borough Council.</p>
 
<p>For further details please see the Stevenage Borough Council
<a href="http://www.stevenage.gov.uk/councilanddemocracy/petitions/petitionscheme">Petition Scheme</a>.</p>

<?
        return;
    }

    echo '<h3 class="page_title_border">Petition Guidelines</h3>';
    if ($site_name == 'tandridge') {
?>

<p>The petition must refer to a matter that is relevant to the functions of a district council. Petitions submitted to the council must include: </p>

<ul>
<li>The title or subject of the petition. </li>
<li>A clear and concise statement covering the subject of the petition. </li>
<li>It should state what action the petitioner wishes the council to take. The petition will be returned to you to edit if it is unclear what action is being sought.</li>
<li>The petition author's contact address (this will not be placed on the website); </li>
<li>A duration for the petition ie the deadline you want people to sign by (maximum of six months).</li>
<li>10 signatures or more for a petition to be referred to the committee or council meeting (if less than 10 signatures, please see <a href="http://www.tandridge.gov.uk/faq/faq.htm?mode=20&amp;pk_faq=478">What if my petitions does not have 10 signatures).</a></li>
</ul>

<h4>What is not allowed?</h4>
<p>A petition will not be accepted where: </p>

<ul>
<li>It is considered to be vexatious, abusive or otherwise inappropriate. </li>
<li>It does not follow the guidelines set out above.</li>
<li>It refers to a development plan, or specific planning matter, including planning applicants.</li>
<li>It refers to a decision for which there is an existing right of appeal. </li>
<li>It is a duplicate or near duplicate of a similar petition received or submitted within the previous 12 months. </li>
<li>It refers to a specific licensing application.</li>
</ul>

<p>The information in a petition must be submitted in good faith. For the petition service to comply with the law, you must not include: </p>
<ul>
<li>Party political material. This does not mean it is not permissible to petition on controversial issues. For example, this party political petition would not be permitted: &quot;we petition the council to change the conservative administration's policy on housing&quot;, but this non-party political version would be: &quot;we petition the council to change its policy on housing&quot;. </li>
<li>Potentially libellous, false, or defamatory statements. </li>
<li>Information which may be protected by an injunction or court order (for example, the identities of children in custody disputes).</li>
<li>Material which is potentially confidential, commercially sensitive, or which may cause personal distress or loss.</li>
<li>Any commercial endorsement, promotion of any product, service or publication.</li>
<li>The names of individual officials of public bodies, unless they are part of the senior management of those organisations.</li>
<li>The names of family members of elected representatives or officials of public bodies.</li>
<li>The names of individuals, or information where they may be identified, in relation to criminal accusations.</li>
<li>Language which is offensive, intemperate, or provocative. This not only includes swear words and insults, but any language which people could reasonably take offence to. </li>
</ul>

<p>Further information on the Council's procedures and how you can express your views are available here: </p>
<ul>
<li><a href="http://www.tandridge.gov.uk/Planning/planninginteractive/default.htm" title="Planning online">Planning applications</a></li>
<li><a href="http://www.tandridge.gov.uk/YourCouncil/consultation.htm" title="Consultation">Consultation</a></li>
</ul>

<h4>Why might we reject your petition?</h4>
<p>Petitions which do not follow the guidelines above cannot be accepted. In these cases, you will be informed in writing of the reason(s) your petition has been refused. If this happens, we will give you the option of altering and resubmitting the petition so it can be accepted.</p>
<p>If you decide not to resubmit your petition, or if the second one is also rejected, we will list your petition and the reason(s) for not accepting it on this website. We will publish the full text of your petition, unless the content is illegal or offensive. </p>
<p>We reserve the right to reject: </p>

<ul>
<li>Petitions similar to and/or overlap with an existing petition or petitions.</li>
<li>Petitions which ask for things outside the remit or powers of the council. </li>
<li>Statements that don't request any action. We cannot accept petitions which call upon the council to &quot;recognise&quot; or &quot;acknowledge&quot; something, as they do not call for a recognisable action. </li>
<li>Wording that needs amending, or is impossible to understand. Please don't use capital letters excessively as they can make petitions hard to read. </li>
<li>Statements that amount to advertisements.</li>
<li>Petitions intended to be humorous, or which have no point about council policy.</li>
<li>Issues for which an e-petition is not the appropriate channel (for example, correspondence about a personal issue).</li>
<li>Freedom of Information requests. This is not the right channel for FOI requests - <a href="http://www.tandridge.gov.uk/YourCouncil/DataProtectionFreedomofInformation/freedom_of_information.htm" title="Freedom of information ">Freedom of information.</a></li>
</ul>

<p><a href="/terms">Full terms and conditions</a></p>

<?
    } elseif ($site_group == 'surreycc' || $site_name == 'westminster' || $site_name == 'suffolkcoastal' || $site_name == 'ipswich') {

        $foi_link = 'http://www.ico.gov.uk/';
        $foi_text = $foi_link;
        $url_text = '';
        $party_political_example = 'For example, this party political petition
        would not be permitted: "We petition the council to change the
        Conservative Cabinet\'s policy on education", but this non-party
        political version would be: "We petition the council to change their
        policy on education".';

        if ($site_name == 'ipswich') {
            $foi_link = 'http://www.ipswich.gov.uk/site/scripts/documents_info.php?categoryID=722&documentID=248';
            $foi_text = 'our FOI procedure pages';
            $party_political_example = 'For example, this party political
            petition would not be permitted: "We petition Ipswich Borough
            Council to change the Conservative/Liberal Democrat Executive\'
            policy on free swimming", but this non-party political version
            would be: "We petition Ipswich Borough Council to change their
            policy on free swimming".';
            $url_text = '<li>URLs or web links (we cannot vet the content of
            external sites, and therefore cannot link to them from this
            e-Petitions system);</li>';
        } elseif ($site_name == 'reigate-banstead') {
            $foi_link = 'http://www.reigate-banstead.gov.uk/council_and_democracy/about_the_council/access_to_information/freedom_of_information_act_2000/';
            $foi_text = 'Freedom Of Information Act 2000';
        } elseif ($site_name == 'suffolkcoastal') {
            $foi_text = 'the Information Commissioner&rsquo;s website';
        } elseif ($site_name == 'westminster') {
            $foi_link = 'http://www.westminster.gov.uk/services/councilgovernmentanddemocracy/dataprotectionandfreedomofinformation/foi/';
            $foi_text = 'our Freedom of Information section';
            $party_political_example = '';
        }
?>

<p>
The information in a petition must be submitted in good faith. In
order for the petition service to comply with the law,
you must not include: </p>

<ul>
<li>Party political material.
Please note, this does not mean it is not permissible to petition on controversial issues.  
<?=$party_political_example?>
</li>
<li>potentially libellous, false, or defamatory statements;</li>
<li>information which may be protected by an injunction or court order (for
example, the identities of children in custody disputes);</li>
<li>material which is potentially confidential, commercially sensitive, or which
may cause personal distress or loss;</li>
<li>any commercial endorsement, promotion of any product, service or publication;</li>
<?=$url_text?>
<li>the names of individual officials of public bodies, unless they
are part of the senior management of those organisations;</li>
<li>the names of family members of elected representatives or
officials of public bodies;</li>
<li>the names of individuals, or information where they may be
identified, in relation to criminal accusations;</li>
<li>language which is offensive, intemperate, or provocative. This not
only includes obvious swear words and insults, but any language to which
people reading it could reasonably take offence (we believe it is
possible to petition for anything, no matter how radical, politely).</li>
</ul>

<p>We reserve the right to reject:</p>
<ul>
<li>petitions that are similar to and/or overlap with an existing petition or petitions;</li>
<li>petitions which ask for things outside the remit or powers of the council</li>
<li>statements that don't actually request any action - ideally start the title of your petition with a verb;</li>
<li>wording that is impossible to understand;</li>
<li>statements that amount to advertisements;</li>
<li>petitions which are intended to be humorous, or which
have no point about council policy (however witty these
are, it is not appropriate to use a publicly-funded website
for purely frivolous purposes);</li>
<li>issues for which an e-petition is not the appropriate channel
(for example, correspondence about a personal issue);</li>
<li>Freedom of Information requests. This is not the right channel
for FOI requests; information about the appropriate procedure can be
found at <a href="<?=$foi_link?>" target="_blank"><?=$foi_text?> <small>(new window)</small></a>.</li>
</ul>

<p>We will strive to ensure that petitions that do not meet our
criteria are not accepted, but where a petition is accepted which
contains misleading information we reserve the right to post an
interim response to highlight this point to anyone visiting to 
sign the petition.</p>

<h2>Common causes for rejection</h2>

<p>In order to help you avoid common problems, we've produced this list:</p>

<ul>

<li>Please don't use 'shouting' capital letters excessively as they
can make petitions fall foul of our 'impossible to read' criteria.</li>

<li>We cannot accept petitions which call upon the council to "recognise" or
"acknowledge" something, as they do not clearly call for a
recognisable action.</li>

</ul>

<?
    } elseif ($site_group == 'number10') {
?>

<p>
The information in a petition must be submitted in good faith. In
order for the petition service to comply with the law and with
the Civil Service Code, you must not include: </p>

<ul>
<li>Party political material. This website is a
Government site. Party political content cannot be published, under the
<a href="http://www.civilservice.gov.uk/civilservicecode">normal rules governing the Civil Service</a>.
Please note, this does not mean it is not permissible to petition on
controversial issues. For example, this party political petition
would not be permitted: "We petition the PM to change his party's policy on education",
but this non-party political version would be:
"We petition the PM to change the government's policy on education".</li>
<li>potentially libellous, false, or defamatory statements;</li>
<li>information which may be protected by an injunction or court order (for
example, the identities of children in custody disputes);</li>
<li>material which is potentially confidential, commercially sensitive, or which
may cause personal distress or loss;</li>
<li>any commercial endorsement, promotion of any product, service or publication;</li>
<li>URLs or web links (we cannot vet the content of external sites, and
therefore cannot link to them from this site);</li>
<li>the names of individual officials of public bodies, unless they
are part of the senior management of those organisations;</li>
<li>the names of family members of elected representatives or
officials of public bodies;</li>
<li>the names of individuals, or information where they may be
identified, in relation to criminal accusations;</li>
<li>language which is offensive, intemperate, or provocative. This not
only includes obvious swear words and insults, but any language to which
people reading it could reasonably take offence (we believe it is
possible to petition for anything, no matter how radical, politely).</li>
</ul>

<p>We reserve the right to reject:</p>
<ul>
<li>petitions that are similar to and/or overlap with an existing petition or petitions;</li>
<li>petitions which ask for things outside the remit or powers of the Prime Minister and Government</li>
<li>statements that don't actually request any action - ideally start the title of your petition with a verb;</li>
<li>wording that is impossible to understand;</li>
<li>statements that amount to advertisements;</li>
<li>petitions which are intended to be humorous, or which
have no point about government policy (however witty these
are, it is not appropriate to use a publicly-funded website
for purely frivolous purposes);</li>
<li>issues for which an e-petition is not the appropriate channel
(for example, correspondence about a personal issue);</li>
<li>Freedom of Information requests. This is not the right channel
for FOI requests; information about the appropriate procedure can be
found at <a href="http://www.ico.gov.uk/">http://www.ico.gov.uk/</a>.</li>
<li>nominations for Honours. These have been accepted in the past but
this is not the appropriate channel; accordingly, from 6 March 2008 we
are rejecting such petitions and directing petitioners to
<a href="http://www.direct.gov.uk/honours">http://www.direct.gov.uk/honours</a> where
nominations for Honours can be made directly to the appropriate department.</li>
</ul>

<p>We will strive to ensure that petitions that do not meet our
criteria are not accepted, but where a petition is accepted which
contains misleading information we reserve the right to post an
interim response to highlight this point to anyone visiting to 
sign the petition.</p>

<h3>Common causes for rejection</h3>

<p>Running the petition site, we see a lot of people having petitions
rejected for a handful of very similar reasons. In order to help you
avoid common problems, we've produced this list:</p>

<ul>
<li>We don't accept petitions on individual legal cases such as
deportations because we can never ascertain whether the individual
involved has given permission for their details to be made publicly
known. We advise petitioners to take their concerns on such matters
directly to the Home Office.</li>

<li>Please don't use 'shouting' capital letters excessively as they
can make petitions fall foul of our 'impossible to read' criteria.</li>

<li>We receive a lot of petitions on devolved matters. If your
petition relates to the powers devolved to parts of the UK, such as
the Welsh Assembly or Scottish Parliament, you should approach those
bodies directly as these things are outside the remit of the Prime
Minister. This also applies to matters relating to London, such as
the Underground, which should be directed to the Greater London
Assembly and the Mayor's Office.</li>

<li>We also receive petitions about decisions that are clearly private
sector decisions, such as whether to re-introduce a brand of breakfast
cereal. These are also outside the remit of the Prime Minister.</li>

<li>We cannot accept petitions which call upon <?=OPTION_SITE_PETITIONED?> to "recognise" or
"acknowledge" something, as they do not clearly call for a
recognisable action.</li>

</ul>

<?
    } elseif ($site_name == 'salford') {
?>

<p>
The information in a petition must be submitted in good faith. In
order for the petition service to comply with the law,
you must not include:</p>

<ul>
<li>Party political material.
Please note, this does not mean it is not permissible to petition on
controversial issues. For example, this party political petition
would not be permitted: "We petition <?=OPTION_SITE_PETITIONED ?> to change the Labour executive's policy on education",
but this non-party political version would be:
"We petition <?=OPTION_SITE_PETITIONED ?> to change their policy on education".</li>
<li>potentially libellous, false, or defamatory statements;</li>
<li>information which may be protected by an injunction or court order (for
example, the identities of children in custody disputes);</li>
<li>material which is potentially confidential, commercially sensitive, or which
may cause personal distress or loss;</li>
<li>any commercial endorsement, promotion of any product, service or publication;</li>
<li>the names of individual officials of public bodies, unless they
are part of the senior management of those organisations;</li>
<li>the names of family members of elected representatives or
officials of public bodies;</li>
<li>the names of individuals, or information where they may be
identified, in relation to criminal accusations;</li>
<li>language which is offensive, intemperate, or provocative. This not
only includes obvious swear words and insults, but any language to which
people reading it could reasonably take offence (we believe it is
possible to petition for anything, no matter how radical, politely).</li>
</ul>

<p>We reserve the right to reject:</p>
<ul>
<li>petitions that are similar to and/or overlap with an existing petition or petitions;</li>
<li>petitions which ask for things outside the remit or powers of <?=OPTION_SITE_PETITIONED ?>;</li>
<li>statements that don't actually request any action - ideally start the title of your petition with a verb;</li>
<li>wording that is impossible to understand;</li>
<li>statements that amount to advertisements;</li>
<li>petitions which are intended to be humorous, or which
have no point about government policy (however witty these
are, it is not appropriate to use a publicly-funded website
for purely frivolous purposes);</li>
<li>issues for which an e-petition is not the appropriate channel
(for example, correspondence about a personal issue);</li>
<li>Freedom of Information requests. This is not the right channel
for FOI requests; information about the appropriate procedure can be
found at <a href="http://www.ico.gov.uk/" target="_blank">http://www.ico.gov.uk/ <small>(new window)</small></a>;</li>
<li>any matter relating to a planning decision, including about a development
plan document or the community infrastructure levy. This is not the right
channel, contact the <a href="http://www.salford.gov.uk/planning-policy.htm">Spatial Planning Team</a>
for further information;</li>
<li>any matter relating to an alcohol, gambling or sex establishment licensing
decision. This is not the right channel, contact the
<a href="http://www.salford.gov.uk/licensing.htm">Licensing Team</a>
for further information.</li>
</ul>

<p>We will strive to ensure that petitions that do not meet our
criteria are not accepted, but where a petition is accepted which
contains misleading information we reserve the right to post an
interim response to highlight this point to anyone visiting to 
sign the petition.</p>

<h3>Common causes for rejection</h3>

<p>In order to help you avoid common problems, we've produced this list:</p>

<ul>
<li>We don't accept petitions on individual legal cases such as
deportations because we can never ascertain whether the individual
involved has given permission for their details to be made publicly
known. We advise petitioners to take their concerns on such matters
directly to the Home Office.</li>

<li>Please don't use 'shouting' capital letters excessively as they
can make petitions fall foul of our 'impossible to read' criteria.</li>

<li>We cannot accept petitions which call upon <?=OPTION_SITE_PETITIONED?> to "recognise" or
"acknowledge" something, as they do not clearly call for a
recognisable action.</li>

</ul>

<?
    } else {
?>

<p>
The information in a petition must be submitted in good faith. In
order for the petition service to comply with the law,
you must not include:</p>

<ul>
<li>Party political material.
Please note, this does not mean it is not permissible to petition on
controversial issues. For example, this party political petition
would not be permitted: "We petition <?=OPTION_SITE_PETITIONED ?> to change the Labour executive's policy on education",
but this non-party political version would be:
"We petition <?=OPTION_SITE_PETITIONED ?> to change their policy on education".</li>
<li>potentially libellous, false, or defamatory statements;</li>
<li>information which may be protected by an injunction or court order (for
example, the identities of children in custody disputes);</li>
<li>material which is potentially confidential, commercially sensitive, or which
may cause personal distress or loss;</li>
<li>any commercial endorsement, promotion of any product, service or publication;</li>
<li>the names of individual officials of public bodies, unless they
are part of the senior management of those organisations;</li>
<li>the names of family members of elected representatives or
officials of public bodies;</li>
<li>the names of individuals, or information where they may be
identified, in relation to criminal accusations;</li>
<li>language which is offensive, intemperate, or provocative. This not
only includes obvious swear words and insults, but any language to which
people reading it could reasonably take offence (we believe it is
possible to petition for anything, no matter how radical, politely).</li>
</ul>

<?
        if (OPTION_SITE_APPROVAL) {
?>

<p>We reserve the right to reject:</p>
<ul>
<li>petitions that are similar to and/or overlap with an existing petition or petitions;</li>
<li>petitions which ask for things outside the remit or powers of <?=OPTION_SITE_PETITIONED ?>;</li>
<li>statements that don't actually request any action - ideally start the title of your petition with a verb;</li>
<li>wording that is impossible to understand;</li>
<li>statements that amount to advertisements;</li>
<li>petitions which are intended to be humorous, or which
have no point about government policy (however witty these
are, it is not appropriate to use a publicly-funded website
for purely frivolous purposes);</li>
<li>issues for which an e-petition is not the appropriate channel
(for example, correspondence about a personal issue);</li>

<? if ($site_name == 'bassetlaw') { ?>
	<li>issues which are currently being administered via another process</li>
<? } ?>

<li>Freedom of Information requests. This is not the right channel
for FOI requests; information about the appropriate procedure can be
found at <a href="http://www.ico.gov.uk/" target="_blank">http://www.ico.gov.uk/ <small>(new window)</small></a>.</li>
</ul>

<p>We will strive to ensure that petitions that do not meet our
criteria are not accepted, but where a petition is accepted which
contains misleading information we reserve the right to post an
interim response to highlight this point to anyone visiting to 
sign the petition.</p>

<h3>Common causes for rejection</h3>

<p>In order to help you avoid common problems, we've produced this list:</p>

<ul>
<li>We don't accept petitions on individual legal cases such as
deportations because we can never ascertain whether the individual
involved has given permission for their details to be made publicly
known. We advise petitioners to take their concerns on such matters
directly to the Home Office.</li>

<li>Please don't use 'shouting' capital letters excessively as they
can make petitions fall foul of our 'impossible to read' criteria.</li>

<li>We cannot accept petitions which call upon <?=OPTION_SITE_PETITIONED?> to "recognise" or
"acknowledge" something, as they do not clearly call for a
recognisable action.</li>

</ul>

<?
        } else {
            print '<p>Petitions which are found to break these terms will be removed from the site.</p>';
        }

    }
?>
<p>Petitioners may freely disagree with <?=OPTION_SITE_PETITIONED?> or
call for changes of policy. There will be no attempt to exclude critical views
and decisions will not be made on a party political basis.</p>
<?
}

# If a body has their own explanation of RSS, this function returns it;
# otherwise the BBC RSS help page.
function cobrand_rss_explanation_link() {
    global $site_name;
    if ($site_name == 'surreycc')
        return 'http://www.surreycc.gov.uk/sccwebsite/sccwspages.nsf/LookupWebPagesByTITLE_RTF/RSS+feeds?opendocument';
    if ($site_name == 'ipswich')
        return 'http://www.ipswich.gov.uk/site/scripts/rss_about.php';
    if ($site_name == 'sholland')
        return 'http://www.sholland.gov.uk/rss/default.htm';
    return 'http://www.bbc.co.uk/news/10628494';
}

function cobrand_how_it_works_start() {
    global $site_name, $site_group;
    if ($site_name == 'number10') {
?>
<p>You can view and sign any <a href="/list">current petitions</a>, and see the
Government response to any <a href="/list/closed">completed petitions</a>. If
you have signed a petition that has reached more than
<?=cobrand_signature_threshold() ?> signatures by the time it closes, you will
be sent a response from the Government by email.
</p>
<?
    } else {
?>
<p>You can view and sign any <a href="/list">current petitions</a>, and see our
response to any <a href="/list/closed">completed petitions</a>.
</p>
<?
    }
}

function cobrand_how_it_works_extra() {
    global $site_name, $site_group;
    if ($site_name == 'number10') {
        echo 'A list of <a href="/list/rejected">rejected petitions</a> is available on this website.';
    }
    if ($site_name == 'islington') {
        echo '</p> <p>If you experience any problems with the e-petitions
        system, please <a href="http://www.islington.gov.uk/Contact/">contact
        us</a>.';
    }
    if ($site_group == 'surreycc' && $site_name != 'surreycc') {
        echo '</p> <p>You can also view
        <a href="http://petitions.surreycc.gov.uk/">petitions to Surrey County
        Council</a> on their website.';
    }
}

function cobrand_extra_terms_link() {
    global $site_name;
    if ($site_name == 'east-northamptonshire')
        echo '<a href="http://www.east-northamptonshire.gov.uk/petitions">petitions scheme</a> and ';
}

function cobrand_terms_text() {
    global $site_name;
    if ($site_name == 'westminster') return 'Petitions Scheme';
    return 'terms and conditions';
}

# If a body hosts their own T&Cs page, this function returns its location
function cobrand_terms_elsewhere() {
    global $site_name;
    if ($site_name == 'eastcambs')
        return 'http://www.eastcambs.gov.uk/council-and-democracy/petition-procedure';
    if ($site_name == 'east-northamptonshire')
        return 'http://www.east-northamptonshire.gov.uk/site/scripts/documents_info.aspx?documentID=928&pageNumber=10';
    if ($site_name == 'elmbridge')
        return 'http://www.elmbridge.gov.uk/Council/committees/petitionsscheme.htm';
    if ($site_name == 'epsom-ewell')
        return 'http://www.epsom-ewell.gov.uk/EEBC/Council/Terms+and+Conditions+for+Petitions.htm';
    if ($site_name == 'forest-heath')
        return 'http://www.forest-heath.gov.uk/info/100004/council_and_democracy/286/petitions/2';
    if ($site_name == 'hounslow')
        return 'http://www.hounslow.gov.uk/petitions_scheme_dec11.pdf';
    if ($site_name == 'ipswich')
        return 'http://www.ipswich.gov.uk/site/scripts/documents_info.php?documentID=1145';
    if ($site_name == 'lichfielddc')
        return 'http://www.lichfielddc.gov.uk/petitionterms';
    if ($site_name == 'melton')
        return 'http://www.melton.gov.uk/council_and_democracy/petitions.aspx';
    if ($site_name == 'molevalley')
        return 'http://www.molevalley.gov.uk/index.cfm?articleid=11411';
    if ($site_name == 'reigate-banstead')
        return 'http://www.reigate-banstead.gov.uk/council_and_democracy/local_democracy/petitions/tcpetitions/index.asp';
    if ($site_name == 'runnymede')
        return 'http://www.runnymede.gov.uk/portal/site/runnymede/menuitem.12d3579a97fd8623fa43a310af8ca028/';
    if ($site_name == 'rushcliffe')
        return 'http://www.rushcliffe.gov.uk/councilanddemocracy/haveyoursay/petitions/'; # best guess
    if ($site_name == 'spelthorne')
        return 'http://www.spelthorne.gov.uk/petitions_terms';
    if ($site_name == 'stevenage')
        return 'http://www.stevenage.gov.uk/councilanddemocracy/petitions/petitionscheme/epetitions';
    if ($site_name == 'suffolkcoastal')
        return 'http://www.suffolkcoastal.gov.uk/yourcouncil/haveyoursay/petitions/petitionsterms/';
    if ($site_name == 'surreycc')
        return 'http://www.surreycc.gov.uk/sccwebsite/sccwspages.nsf/LookupWebPagesByTITLE_RTF/Terms+and+conditions+for+petitions?opendocument';
    if ($site_name == 'tandridge')
        return 'http://www.tandridge.gov.uk/YourCouncil/CouncillorsMeetings/petitions/terms.htm';
    if ($site_name == 'waverley')
        return 'http://www.waverley.gov.uk/petitionsterms';
    if ($site_name == 'westminster')
        return 'http://www.westminster.gov.uk/services/councilgovernmentanddemocracy/westminster-petitions/the-city-councils-petition-scheme/';
    if ($site_name == 'woking')
        return 'http://www.woking.gov.uk/council/about/petitions/termsandconditions';
    return null;
}

function cobrand_steps_elsewhere() {
    global $site_name;
    if ($site_name == 'surreycc')
        return 'http://www.surreycc.gov.uk/sccwebsite/sccwspages.nsf/LookupWebPagesByTITLE_RTF/Step+by+step+guide+to+e-petitions?opendocument';
    if ($site_name == 'reigate-banstead')
        return 'http://www.reigate-banstead.gov.uk/council_and_democracy/local_democracy/petitions/stepbystep/index.asp';
    if ($site_name == 'spelthorne')
        return 'http://www.spelthorne.gov.uk/petitions_guide';
    if ($site_name == 'suffolkcoastal')
        return 'http://www.suffolkcoastal.gov.uk/yourcouncil/haveyoursay/petitions/petitionisstepbystep/';
    return null;
}

function cobrand_steps_petition_close() {
    global $site_name;
    if ($site_name == 'number10') {
?>
<p>When a serious petition closes, usually provided there are <?=cobrand_signature_threshold() ?> signatures or more,
officials at Downing Street will ensure you get a response to the issues you
raise. Depending on the nature of the petition, this may be from the Prime
Minister, or he may ask one of his Ministers or officials to respond.

<p>We will email the petition organiser and everyone who has signed the
petition via this website giving details of the Government’s response.
<?
    } elseif ($site_name == 'woking') {
?>
<p>Once your petition has closed, usually provided there are
<?=cobrand_signature_threshold() ?> signatures or more, it will be passed to
the relevant officials at the council for a response.
We will be able to email the petition organiser and everyone who has signed the
petition, and responses will also be published on this website.</p>
<?
    } elseif ($site_name == 'salford') {
?>
<p>When the petition closes we will publish a response; this will be emailed to
everyone who has signed the e-petition. The response will also be published on
this website.</p>
<?
    } else {
?>
<p>If the council responds, it will be emailed to everyone who has
signed the e-petition. The response will also be published on this website.</p>
<?
    }
}

function cobrand_privacy_policy_elsewhere() { /* council changed mind: but it's here now, for when someone needs it! */
    global $site_name;
    return null;
}

function cobrand_view_petitions_heading() {
    global $site_name;
    if ($site_name == 'ipswich') return 'Petitions';
}

function cobrand_view_petitions_category_filter() {
    global $site_name;
    if ($site_name == 'hounslow') return true;
    return false;
}

function cobrand_main_heading($text) {
    global $site_name;
    if ($site_name == 'surreycc' || $site_name == 'suffolkcoastal' || $site_name == 'runnymede' || $site_name == 'surreyheath')
        return "<h2>$text</h2>";
    return "<h3>$text</h3>";
}

function cobrand_create_heading($text) {
    global $site_name;
    if ($site_name == 'reigate-banstead' || $site_name == 'salford')
        return "<h3>$text</h3>";
    return "<h2>$text</h2>";
}

# Currently used on creation and list pages to supply a
# main heading that one council asked for.
function cobrand_extra_heading($text) {
    global $site_name;
    if ($site_name == 'tandridge' || $site_name == 'molevalley' || $site_name == 'lichfielddc')
        print "<h1>$text</h1>";
}

function cobrand_allowed_responses() {
    global $site_name;
    if ($site_name == 'hounslow')
        return 12;
    if ($site_name == 'surrey' || $site_name == 'tandridge' || $site_name == 'number10')
        return 2;
    return 8;
}

function cobrand_fill_form_instructions(){
    global $site_name;
    if ($site_name == 'east-northamptonshire') {
        return '<p>Please make sure you have read the 
            <a href="http://www.east-northamptonshire.gov.uk/petitions">petitions&nbsp;scheme</a> and 
            <a href="/terms">terms&nbsp;and&nbsp;conditions</a> before you create a petition. 
            Your petition will need at least 50 signatures for the council to take any action.</p>
            Please fill in all the fields below.';
    }
    if ($site_name == 'bassetlaw'){
        return 'Please complete all the sections below.';
    }
    return 'Please fill in all the fields below.';
}

function cobrand_html_final_changes($s) {
    global $site_name;
    if ($site_name == 'ipswich') {
        $s = str_replace('e-petition', 'e-Petition', $s);
    } elseif ($site_name == 'lichfielddc') {
        $s = preg_replace('#<input([^>]*?type=[\'"]text)#', '<input class="field"\1', $s);
    } elseif ($site_name == 'spelthorne') {
        $s = str_ireplace('email', 'e-mail', $s);
    }
    return $s;
}

# allow specific councils to completely override normal domain settings: 
# this is rare (currently only applies if SITE_DOMAINS is true)
function cobrand_custom_domain($body) {
  if ($body == 'bassetlaw') {
      return 'http://bassetlaw.petitions.mysociety.org'; 
  } else {
      return 'http://petitions.' . $body . '.gov.uk';
  }
}

# returns OPTION_CREATION_DISABLED value
# if this is a multi-body site, can't just return OPTION_CREATION_DISABLED but must inspect it first
#   (Note: OPTION_CREATION_DISABLED may be pure HTML (for a single site), but for multi-body 
#          sites it's a list of sitenames that gets returned as a short HTML notice here)
function cobrand_creation_disabled() {
    global $site_name;
    if (OPTION_CREATION_DISABLED) {
        if (OPTION_SITE_TYPE == 'multiple') { # this is a multi-body installation, only disable if site_name is explicitly mentioned
            $disabled_bodies = preg_split("/[\s,]+/", OPTION_CREATION_DISABLED);
            if (in_array($site_name, $disabled_bodies)) {
		if ($site_name == 'surreycc') {
			return <<<HTML
<div style='background-color:#C9E0D8;padding:0.1em 1em;margin:0 0 1em 0;'>
	<p>
		<strong>New petitions are currently not being accepted.</strong>
		In line with legislation around the use of council resources for publicity prior to the
		County Council elections on Thursday 2 May 2013, the ePetition system will be temporarily suspended.
		Surrey County Council will welcome receiving your petitions again from 3 May 2013.
	</p>
</div>
HTML;
		} else {
                	return "<p>Submission of new petitions is closed.</p>"; # default message: customise here if needed
		}
            } else {
                return false;
            }
        }
    } 
    return OPTION_CREATION_DISABLED;
}

