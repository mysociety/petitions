<?php
/*
 * cobrand.php:
 * Functions for different brandings of the petitions code.
 * 
 * Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org; WWW: http://www.mysociety.org
 * 
 */

function cobrand_creation_category_first() {
    global $site_name;
    if (in_array($site_name, array('tandridge', 'surreycc', 'woking'))) {
        return true;
    }
    return false;
}

function cobrand_creation_ask_for_address_type() {
    global $site_name;
    if (cobrand_creation_within_area_only()) return true;
    if ($site_name == 'tandridge') return true;
    return false;
}

function cobrand_creation_within_area_only() {
    global $site_name;
    if ($site_name == 'surreycc') return 'Surrey';
    if ($site_name == 'woking') return 'Woking';
    return '';
}

function cobrand_creator_must_be() {
    global $site_name;
    if ($site_name == 'surreycc') {
        return 'live, work or study at a Surrey registered address';
    }
    if ($site_name == 'woking') {
        return 'be a member of the Council or a registered Elector in the Borough of Woking';
    }
    if (cobrand_creation_within_area_only()) {
        if (cobrand_creation_ask_for_address_type()) {
            return 'be a council resident or work within the area of the council';
        } else {
            return 'be a council resident';
        }
    } else {
        return 'be a British citizen or resident';
    }
}

function cobrand_error_div_start() {
    global $site_name;
    if ($site_name == 'surreycc') {
        return '<div class="scc-error">';
    }
    return '<div id="errors">';
}

function cobrand_overseas_dropdown() {
    global $site_group;
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

function cobrand_category_okay($category_id) {
    global $site_name, $site_group;
    if ($site_group != 'surreycc') return true;
    if (in_array($site_name, array('tandridge', 'woking')) && 
        in_array($category_id, array(4, 6, 7, 10, 12, 13, 16)))
        return false;
    if ($site_name == 'surreycc' &&
        in_array($category_id, array(1, 2, 3, 5, 8, 9, 15)))
        return false;
    return true;
}

function cobrand_category_wrong_action($category_id, $area='') {
    global $site_name, $site_group;
    if ($site_group == 'surreycc') {
        if ($site_name != 'surreycc') {
            $url = 'http://petitions.surreycc.gov.uk/new?tostepmain=1&category=' . $category_id;
            return 'You are petitioning about something which isn\'t the responsibility of your district council,
            but instead of Surrey County Council. <a href="' . $url . '">Go to Surrey County Council\'s petition website
            to create a petition in this category</a>.'; 
        }
        if ($area) {
            # $area is set if we're being called as a result of the form below
            if (in_array($area, array('tandridge')))
                return '/tandridge';
                #return 'http://petitions.' . $area . '.gov.uk/new?tostepmain=1&category=' . $category_id;
            if ($area == 'elmbridge')
                return 'http://www.elmbridge.gov.uk/Council/information/petition.htm';
            if ($area == 'epsom-ewell')
                return 'http://www.epsom-ewell.gov.uk/EEBC/Council/E-petitions.htm';
            if ($area == 'guildford')
                return 'http://www.surreycc.gov.uk/SCCWebsite/SCCWSPages.nsf/LookupWebPagesByUNID_RTF_INT/A4F9AD1334EF7EB480257744005476BA?opendocument';
            if ($area == 'molevalley')
                return 'http://www.molevalley.gov.uk/index.cfm?articleid=9694';
            if ($area == 'spelthorne')
                return 'http://www.spelthorne.gov.uk/epetitions.htm';
            if ($area == 'reigate-banstead')
                return 'http://www.reigate-banstead.gov.uk/council_and_democracy/local_democracy/petitions/';
            if ($area == 'runnymede')
                return 'http://www.runnymede.gov.uk/portal/site/runnymede/menuitem.bbcf55f3a4a758ceb14229a7af8ca028/';
            if ($area == 'surreyheath')
                return 'http://www.surreyheath.gov.uk/council/epetitions/';
            if ($area == 'waverley')
                return 'http://www.waverley.gov.uk/site/scripts/documents_info.php?documentID=955';
            if ($area == 'woking')
                return 'http://www.woking.gov.uk/council/about/epetitions';
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
    return null;
}

function cobrand_categories() {
    global $site_group;
    if ($site_group == 'surreycc') {
        return array(
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
            99 => 'Other', # Both
        );
    }

    global $global_petition_categories;
    return $global_petition_categories;
}

function cobrand_category($id) {
    $categories = cobrand_categories();
    return $categories[$id];
}

function cobrand_signature_threshold() {
    global $site_name;
    if ($site_name == 'number10') return 500;
    if ($site_name == 'surreycc') return 100;
    if ($site_name == 'woking') return 10;
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

function cobrand_admin_rejection_snippets() {
    global $site_group;
    $snippets = array(
'Please supply full name and address information.',
'Please address the excessive use of capital letters; they make your petition hard to read.',
'Your title should be a clear call for action, preferably starting with a verb, and not a name or statement.',
    );
    if ($site_group == 'surreycc') {
        return $snippets;
    }
    array_push($snippets,
'Comments about the petitions system should be sent to number10@petitions.pm.gov.uk.',
'Individual legal cases are a matter for direct communication with the Home Office.',
'This is a devolved matter and should be directed to the Scottish Executive / Welsh Assembly / Northern Ireland Executive as appropriate.',
'This is a matter for direct communication with Parliament.',
'The Cabinet Office is actively seeking nominations for honours from the public. Please go to http://www.direct.gov.uk/honours'
    );
    return $snippets;
}

function cobrand_admin_rejection_categories() {
    global $global_rejection_categories, $site_group;
    if ($site_group != 'surreycc') {
        return $global_rejection_categories;
    }
    $categories = $global_rejection_categories;
    unset($categories[65536]); # Links to websites
    return $categories;
}

function cobrand_admin_site_restriction() {
    global $site_group;
    if ($site_group != 'surreycc') return '';
    if (in_array(http_auth_user(), array('surreycc', 'tandridge')))
        return " AND body.ref='" . http_auth_user() . "' ";
    return '';
}

function cobrand_terms_and_conditions() {
    global $site_group;
    if ($site_group == 'surreycc') {
?>

<p>
The information in a petition must be submitted in good faith. In
order for the petition service to comply with the law,
you must not include: </p>

<ul>
<li>Party political material.
Please note, this does not mean it is not permissible to petition on
controversial issues. For example, this party political petition
would not be permitted: "We petition the council to change the Conservative Cabinet's policy on education",
but this non-party political version would be:
"We petition the council to change their policy on education".</li>
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
<li>petitions which ask for things outside the remit or powers of the council</li>
<li>statements that don't actually request any action - ideally start the title of your petition with a verb;</li>
<li>wording that is impossible to understand;</li>
<li>statements that amount to advertisements;</li>
<li>petitions which are intended to be humorous, or which
have no point about council policy (however witty these
are, it is not appropriate to use a publically-funded website
for purely frivolous purposes);</li>
<li>issues for which an e-petition is not the appropriate channel
(for example, correspondence about a personal issue);</li>
<li>Freedom of Information requests. This is not the right channel
for FOI requests; information about the appropriate procedure can be
found at <a href="http://www.ico.gov.uk/">http://www.ico.gov.uk/</a>.</li>
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
are, it is not appropriate to use a publically-funded website
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
    } else {
?>

<p>
The information in a petition must be submitted in good faith. In
order for the petition service to comply with the law and with
the Civil Service Code, you must not include: </p>

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
are, it is not appropriate to use a publically-funded website
for purely frivolous purposes);</li>
<li>issues for which an e-petition is not the appropriate channel
(for example, correspondence about a personal issue);</li>
<li>Freedom of Information requests. This is not the right channel
for FOI requests; information about the appropriate procedure can be
found at <a href="http://www.ico.gov.uk/">http://www.ico.gov.uk/</a>.</li>
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

<li>We cannot accept petitions which call upon <?=OPTION_SITE_PETITIONED?> to "recognise" or
"acknowledge" something, as they do not clearly call for a
recognisable action.</li>

</ul>

<?
        } else {
            print '<p>Petitions which are found to break these terms will be removed from the site.</p>';
        }

    }
}

function cobrand_rss_explanation_link() {
    global $site_name;
    if ($site_name == 'surreycc')
        return 'http://www.surreycc.gov.uk/sccwebsite/sccwspages.nsf/LookupWebPagesByTITLE_RTF/RSS+feeds?opendocument';
    return 'http://news.bbc.co.uk/1/hi/help/3223484.stm';
}

function cobrand_terms_elsewhere() {
    global $site_name;
    if ($site_name == 'surreycc')
        return 'http://www.surreycc.gov.uk/sccwebsite/sccwspages.nsf/LookupWebPagesByTITLE_RTF/Terms+and+conditions+for+petitions?opendocument';
    return null;
}

function cobrand_main_heading($text) {
    global $site_name;
    if ($site_name == 'surreycc')
        return "<h2>$text</h2>";
    elseif ($site_name == 'number10')
        return "<h3 class='page_title_border'>$text</h3>";
    return "<h3>$text</h3>";
}

function cobrand_allowed_responses() {
    global $site_name;
    if ($site_name == 'reigate-banstead')
        return 3;
    return 2;
}
