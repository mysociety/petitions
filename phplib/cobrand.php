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
    if ($site_name == 'westminster' && $title == 'Introduction to e-petitions')
        return 'Create or view petitions';
    return $title;
}

# return true if council prefers "We, the undersigned," to "We the undersigned"
function cobrand_we_the_undersigned_use_commas() {
    global $site_name;
    return false;
}

function cobrand_create_button_title() {
    global $site_name;
    return '';
}

function cobrand_view_button_title() {
    global $site_name;
    return '';
}

# The help sentence printed under the main content of a petition's input box.
function cobrand_creation_sentence_help() {
    global $site_group, $site_name;
    $out = '';
    $out .= '(';
    $out .= 'Please write a sentence';
    if ($site_group != 'surreycc' && $site_group != 'westminster' && $site_group != 'stevenage') {
        $out .= ', preferably starting with a verb,';
    }
    $out .= ' that describes what action you would like ';
    $out .= OPTION_SITE_PETITIONED;
    $out .= ' to take';
    $out .= '.';
    $out .= ')';
    return $out;
}

function cobrand_creation_default_deadline() {
    global $site_name;
}

function cobrand_creation_address_help() {
    global $site_name;
    if ($site_name == 'rbwm') {
        print '<br>(Please use the address where you live, work or study within the Royal Borough)';
    }

}

function cobrand_creation_email_request() {
    global $site_name;
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

    if ($body == 'surreycc' || $body == 'rbwm' || $body == 'stevenage')
        return array('years' => 0, 'months' => 6);
    if ($body == 'westminster')
        return array('weeks' => 6);
    return array('years' => 1, 'months' => 0);
}

function cobrand_creation_duration_help() {
    global $site_name;
    return '';
}

function cobrand_creation_example_ref() {
    global $site_name;
    return 'badgers';
}

function cobrand_creation_short_name_label() {
    global $site_name;
    return 'Choose a short name for your petition (6 to 16 letters):';
}

function cobrand_creation_category_first() {
    global $site_group;
    if ($site_group == 'surreycc') {
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
    if (cobrand_creation_within_area_only()) return true;
    if ($site_name == 'stevenage') return true;
    if ($site_name == 'surreyheath') return true;
    return false;
}

# If creation should be limited to a particular area, this
# function should return a two-element array, consisting of
# the name of the area, and either an area ID that the
# creator must be within, or null if the creator can be in
# any area in the site database.
function cobrand_creation_within_area_only() {
    global $site_name;
    if ($site_name == 'hounslow') return array('Hounslow', 2483);
    if ($site_name == 'molevalley') return array('Mole Valley', 2454);
    if ($site_name == 'runnymede') return array('Runnymede', 2451);
    if ($site_name == 'rbwm') return array('the Royal Borough of Windsor and Maidenhead', 2622);
    if ($site_name == 'surreycc') return array('Surrey', null);
    if ($site_name == 'westminster') return array('Westminster', 2504);
    if ($site_name == 'woking') return array('Woking', 2449);
    return '';
}

function cobrand_creator_must_be() {
    global $site_name;
    $creator_type = '';
    if ($site_name == 'surreycc') {
        $creator_type = 'must live, work or study at a Surrey registered address';
    } elseif ($site_name == 'woking'){
        $creator_type = 'must live, work or study in the Borough of Woking';
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
    return 'Now check your email';
}

function cobrand_creation_top_submit_button() {
    global $site_name;
    if ($site_name == 'westminster' || $site_name == 'stevenage') return false;
    return true;
}

function cobrand_creation_detail_max_chars() {
    global $site_name;
    if ($site_name == 'hounslow') return 1500;
    return 1000;
}

# Allows 'red asterisk' (or similar) to mark mandatory fields
# returns array of markers: 0 for optional input fields, 1 for mandatory, and a legend
# the optional marker forces same-width span just to make layout easy on sites that use these
function cobrand_input_field_mandatory_markers() {
    global $site_name;
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
    return false;
}

function cobrand_perform_address_lookup($pc) {
    $out = array();
    return $out;
}

# pass validate_postcode through here to allow override of the commonlib (UK) routine
function cobrand_validate_postcode($postcode) {
    global $site_name;
    return validate_postcode($postcode); # from commonlib
}

function cobrand_creation_postcode_optional() {
    global $site_name;
    return false;
}

function cobrand_creation_phone_number_optional() {
    global $site_name;
    return false;
}

function cobrand_validate_phone_number($tel) {
    global $site_name;
    return preg_match('#01[2-9][^1]\d{6,7}|01[2-69]1\d{7}|011[3-8]\d{7}|02[03489]\d{8}|07[04-9]\d{8}|00#', $tel);
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
    if ($site_name == 'surreycc' || $site_name == 'rbwm') return true;
    return false;
}

function cobrand_creation_button_separator() {
    global $site_name;
    if ($site_name == 'surreycc' || $site_name == 'rbwm') return ' &nbsp; ';
    return '<br />';
}

function cobrand_creation_extra_footer() {
    global $site_name;
    if ($site_name != 'runnymede') return;
?>
<p class="banner">
<a href="https://www.runnymede.gov.uk/article/14687/Petitions" target="_blank">Petitions frequently asked questions</a>
</p>
<?
}

function cobrand_creation_input_class() {
    global $site_name;
}

function cobrand_creation_submit_button_class($previous = false) {
    global $site_name;
    if ($site_name == 'surreycc' && $previous) return 'button scc-btn-back';
}

function cobrand_creation_main_all_newlines() {
    global $site_name;
    return false;
}

function cobrand_error_div_start() {
    global $site_name;
    if ($site_name == 'surreycc') return '<div class="scc-error">';
    return '<div id="errors">';
}

function cobrand_postcode_label() {
    global $site_name;
    return _('UK postcode');
}

function cobrand_overseas_dropdown() {
    global $site_group;
    if ($site_group ==  'runnymede') {
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
    return '<strong>click "Create"</strong>';
}

# note: numbers here are category numbers, defined in cobrand_categories()
#       Duplicated in anticipation of different councils splitting these responsibilities differently.
function cobrand_category_okay($category_id) {
    global $site_name, $site_group;
    if ($site_group == 'surreycc') {
        $county_only = array(4, 6, 7, 10, 12, 13, 16);
        if ($site_name == 'surreyheath')
            $county_only[] = 11; # Planning not okay
        if ($site_name != 'surreycc' && in_array($category_id, $county_only))
            return false;
        $district_only = array(1, 2, 3, 5, 8, 9, 15);
        if ($site_name == 'surreycc' && in_array($category_id, $district_only))
            return false;
    }
    return true;
}

function cobrand_category_wrong_action($category_id, $area='') {
    global $site_name, $site_group;
    if ($site_group == 'surreycc') {
        if ($site_name != 'surreycc') {
            if ($site_name == 'surreyheath' && $category_id == 11) { # Planning
                return "You cannot create a petition about a planning
application. For further information on the Council's procedures and how you
can express your views, see the
<a href='http://www.surreyheath.gov.uk/planning/default.htm'>planning
applications</a> section.";
            } else {
                $url = 'http://petitions.surreycc.gov.uk/new?tostepmain=1&category=' . $category_id;
                return "You are petitioning about something which isn’t the
responsibility of your district council, but instead of Surrey County Council.
<a href='$url'>Go to Surrey County Council's petition website to create a
petition in this category</a>.";
            }
        }
        if ($area) {
            # $area is set if we're being called as a result of the form below
            if ($area == 'epsom-ewell')
                return 'https://democracy.epsom-ewell.gov.uk/mgEPetitionListDisplay.aspx?bcr=1';
            if ($area == 'waverley')
                return 'https://www.waverley.gov.uk/info/200033/councillors_and_meetings/955/petitions';
            if ($area == 'spelthorne')
                return 'https://democracy.spelthorne.gov.uk/mgEPetitionListDisplay.aspx?bcr=1';
            if ($area == 'elmbridge')
                return 'http://mygov.elmbridge.gov.uk/mgEPetitionListDisplay.aspx?bcr=1';
            if ($area == 'guildford')
                return 'https://www2.guildford.gov.uk/councilmeetings/mgEPetitionListDisplay.aspx?bcr=1';
            if ($area == 'reigate-banstead')
                return 'http://www.reigate-banstead.gov.uk/info/20384/petitions';
            if ($area == 'tandridge')
                return 'https://www.tandridge.gov.uk/Your-council/Voting-and-elections';
            return 'http://petitions.' . $area . '.gov.uk/new?tostepmain=1&category=' . $category_id;
        } else {
            return '
            <input type="hidden" name="category" value="' . $category_id . '">
            You are petitioning about something which isn’t the responsibility of Surrey Council Council,
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
    if ($site_group == 'westminster' || $site_group == 'stevenage') return false;
    return true;
}

function cobrand_signature_threshold() {
    global $site_name;
    if ($site_name == 'surreycc') return 100;
    if (in_array($site_name, array('woking'))) return 10;
    if (in_array($site_name, array('surreyheath', 'runnymede'))) return 50;
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
    if (OPTION_SITE_TYPE == 'multiple') {
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
    if ($site_group == 'hounslow' || $site_group == 'westminster') return true;
    if ($body == 'woking') return true;
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
    if ($site_group == 'surreycc') return array('woking' => '10 days', 'other' => '29 days');
    // 29 days is 4 weeks, plus a day to allow a margin for the creator
    return '29 days';
}

function cobrand_admin_is_site_user() {
    global $site_group;
    $sites = explode(',', OPTION_SITE_NAME);
    $user = http_auth_user();
    if ($site_group == 'surreycc' && $user == 'surreycpt') {
        $user = 'surreycc';
    }
    if (preg_match('#@([^.]*)\.#', $user, $m))
        $user = $m[1];
    if (in_array($user, $sites))
        return $user;
    return false;
}

function cobrand_admin_title() {
    global $site_group;
    if ($site_group == 'surreycc') {
        if ($site = cobrand_admin_is_site_user())
            return ucfirst($site) . ' admin';
    }
    return OPTION_CONTACT_NAME . " admin";
}

function cobrand_admin_style() {
    global $site_name;
}

function cobrand_admin_rejection_snippets() {
    global $site_group;
    $snippets = array(
'Please supply full name and address information.',
'Please address the excessive use of capital letters; they make your petition hard to read.',
'Your title should be a clear call for action, preferably starting with a verb, and not a name or statement.',
    );
    return $snippets;
}

function cobrand_admin_rejection_categories() {
    global $global_rejection_categories, $site_group;
    $categories = $global_rejection_categories;
    $site_user = cobrand_admin_is_site_user();
    unset($categories[131072]); # only Bassetlaw and East Cambs used "Currently being administered via another process"
    unset($categories[65536]); # Links to websites
    return $categories;
}

function cobrand_admin_site_restriction() {
    global $site_group;
    if ($site_group != 'surreycc') return '';

    if ($site = cobrand_admin_is_site_user())
        return " AND body.ref='$site' ";
    return '';
}

function cobrand_admin_allow_html_response() {
    global $site_group;
    if ($site_group == 'sbdc') {
        return true;
    }
    return false;
}

# Admin, so only site_group available
function cobrand_admin_areas_of_interest() {
    global $site_group;

    if ($site_group == 'sbdc' || $site_group == 'sbdc1') {
        return json_decode(file_get_contents('https://mapit.mysociety.org/areas/LBO,MTD,LGD,DIS,UTA,COI'), true);
    }

    if ($site_group == 'hounslow') {
        $wards = json_decode(file_get_contents('https://mapit.mysociety.org/area/2483/children'), true);
        $soas = json_decode(file_get_contents('https://mapit.mysociety.org/areas/Hounslow?type=OLF'), true);
        foreach ($soas as $k => $v) {
            $soas[$k]['parent_area'] = 2483;
        }
        $out = $wards + $soas;
        $out[2483] = array( 'name' => 'Hounslow Borough Council' );
        return $out;
    }

    if ($site_group != 'surreycc') return null;

    $user_to_area_id = array(
        'surreycc' => 2242,
        'molevalley' => 2454,
        'runnymede' => 2451,
        'surreyheath' => 2450,
        'woking' => 2449,
    );
    $out = json_decode(file_get_contents("https://mapit.mysociety.org/areas/" . join(',', array_values($user_to_area_id))), true);
    foreach ($out as $k => $v) {
        if ($v['id'] == 2242) continue;
        $out[$k]['parent_area'] = 2242;
    }
    if ($user = cobrand_admin_is_site_user()) {
        $wards = json_decode(file_get_contents("https://mapit.mysociety.org/area/$user_to_area_id[$user]/children"), true);
        $out += $wards;
    }
    return $out;
}

# Admin, so only site_group available
function cobrand_admin_show_map() {
    global $site_group;
    if (in_array($site_group, array(
        'hounslow', 'sbdc', 'surreycc', 'rbwm'
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
        $out = json_decode(file_get_contents("https://mapit.mysociety.org/area/$id/children"), true);
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

# cobrand_admin_show_body_in_petition i.e., display the Body petitioned when inspecting it
# Normally there's no need to show petition's body in admin because actually we deduce the body
# from the admin user, and restrict the petitions that are displayed accordingly.
# But Whypoll are the first that might need to see multiple bodies' petitions under a single login.
function cobrand_admin_show_body_in_petition() {
    global $site_group;
    return false;
}

function cobrand_convert_name_to_ref($name_or_ref) {
    global $site_group;
    $name = $name_or_ref;
    return $name;
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
    if ($site_group == 'surreycc' || $site_name == 'westminster') {

        $foi_link = 'http://www.ico.gov.uk/';
        $foi_text = $foi_link;
        $url_text = '';
        $party_political_example = 'For example, this party political petition
        would not be permitted: "We petition the council to change the
        Conservative Cabinet\'s policy on education", but this non-party
        political version would be: "We petition the council to change their
        policy on education".';

        if ($site_name == 'westminster') {
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
    return 'http://www.bbc.co.uk/news/10628494';
}

function cobrand_how_it_works_start() {
    global $site_name, $site_group;
    if ($site_name == 'westminster') {
        // Westminster don't want this bit of intro text at all
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
    if ($site_group == 'surreycc' && $site_name != 'surreycc') {
        echo '</p> <p>You can also view
        <a href="http://petitions.surreycc.gov.uk/">petitions to Surrey County
        Council</a> on their website.';
    }
    if ($site_name == 'rbwm') {
        echo '</p> <p>To see RBWM’s data processing Privacy Notice in relation
        to e-petitions, please go to the link below:</p>
        <p><a href="https://www3.rbwm.gov.uk/downloads/200409/data_protection">https://www3.rbwm.gov.uk/downloads/200409/data_protection</a>';
    }
}

function cobrand_extra_terms_link() {
    global $site_name;
}

function cobrand_terms_text() {
    global $site_name;
    if ($site_name == 'westminster') return 'Petitions Scheme';
    return 'terms and conditions';
}

# If a body hosts their own T&Cs page, this function returns its location
function cobrand_terms_elsewhere() {
    global $site_name;
    if ($site_name == 'hounslow')
        return 'https://www.hounslow.gov.uk/downloads/file/1226/terms_and_conditions_-_epetitions';
    if ($site_name == 'molevalley')
        return 'http://www.molevalley.gov.uk/index.cfm?articleid=11411';
    if ($site_name == 'runnymede')
        return 'https://www.runnymede.gov.uk/article/14687/Petitions';
    if ($site_name == 'stevenage')
        return 'http://www.stevenage.gov.uk/about-the-council/councillors-and-democracy/24184/';
    if ($site_name == 'surreycc')
        return 'https://www.surreycc.gov.uk/people-and-community/get-involved/shape-our-services/petitions/terms-and-conditions';
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
    return null;
}

function cobrand_steps_petition_close() {
    global $site_name;
    if ($site_name == 'woking') {
?>
<p>Once your petition has closed, usually provided there are
<?=cobrand_signature_threshold() ?> signatures or more, it will be passed to
the relevant officials at the council for a response.
We will be able to email the petition organiser and everyone who has signed the
petition, and responses will also be published on this website.</p>
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
}

function cobrand_view_petitions_category_filter() {
    global $site_name;
    if ($site_name == 'hounslow') return true;
    return false;
}

function cobrand_view_petitions_separator() {
    global $site_name;
    if ($site_name == 'surreycc') return "";
    return " &nbsp; ";
}

function cobrand_main_heading($text) {
    global $site_name;
    if ($site_name == 'surreycc' || $site_name == 'runnymede' || $site_name == 'surreyheath' || $site_name == 'stevenage')
        return "<h2>$text</h2>";
    return "<h3>$text</h3>";
}

function cobrand_create_heading($text) {
    return "<h2>$text</h2>";
}

# Currently used on creation and list pages to supply a
# main heading that one council asked for.
function cobrand_extra_heading($text) {
    global $site_name;
    if ($site_name == 'molevalley')
        print "<h1>$text</h1>";
}

function cobrand_allowed_responses() {
    global $site_name;
    if ($site_name == 'hounslow')
        return 12;
    if ($site_name == 'surrey' || $site_name == 'sbdc')
        return 2;
    return 8;
}

function cobrand_fill_form_instructions(){
    global $site_name;
    return 'Please fill in all the fields below.';
}

function cobrand_html_final_changes($s) {
    global $site_name;
    return $s;
}

# allow specific councils to completely override normal domain settings:
# this is rare (currently only applies if SITE_DOMAINS is true)
function cobrand_custom_domain($body) {
    return 'http://petitions.' . $body . '.gov.uk';
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

# this section show body currently selected (if any) and offer select list to change it
function cobrand_show_body_selector($body_ref) {
    global $site_name;
}

function cobrand_petition_actions_class() {
    global $site_name;
    if ($site_name == 'surreycc') {
        return "col-md-6 col-sm-12 col-xs-12";
    }
    return "relative_width_45";
}

function cobrand_most_recent_class() {
    global $site_name;
    if ($site_name == 'surreycc') {
        return "col-md-6 col-sm-12 col-xs-12";
    }
    return "relative_width_45";
}

function cobrand_most_popular_class() {
    global $site_name;
    if ($site_name == 'surreycc') {
        return "col-md-6 col-sm-12 col-xs-12";
    }
    return "relative_width_45";
}

function cobrand_front_how_class() {
    global $site_name;
    if ($site_name == 'surreycc') {
        return "col-md-6 col-sm-12 col-xs-12";
    }
    return "";
}
