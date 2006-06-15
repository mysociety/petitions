<?
// new.php:
// New pledges.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: new.php,v 1.1 2006-06-15 14:31:01 francis Exp $

require_once '../phplib/pet.php';

$page_title = _('Create a new petition');
ob_start();
if (get_http_var('tostep1') || get_http_var('tostep2') || get_http_var('tostep3') || get_http_var('topreview') || get_http_var('tocreate') || get_http_var('donetargetwarning')) {
    pledge_form_submitted();
} else {
    pledge_form_one();
}
$contents = ob_get_contents();
ob_end_clean();
page_header($page_title, array());
print $contents;
page_footer(array('nolocalsignup'=>true));

function pledge_form_one($data = array(), $errors = array()) {
    global $lang, $langs;
# <!-- <p><big><strong>Before people can create pledges we should have a stiff warning page, with few, select, bold words about what makes for good &amp; bad pledges (we want to try to get people to keep their target numbers down).</strong></big></p> -->
    if (sizeof($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', array_values($errors));
        print '</li></ul></div>';
    } else {
?>
<div id="tips">
<h2><?=_('Advice for making petitions') ?></h2>
<ol>

<li> <?=_("<strong>Get ready to sell your petition, hard</strong> ... ") ?></li>

<li> <?=_("<strong>Think about how your petition reads.</strong> ...") ?></li>

</ol>
</div>
<?
    }

    global $pb_time;
    $P = person_if_signed_on();
    if (!is_null($P)) {
        if (!array_key_exists('email', $data))
            $data['email'] = $P->email();
        if (!array_key_exists('name', $data))
            $data['name'] = $P->name_or_blank();
    }

    global $petition_prefix;
?>

<form accept-charset="utf-8" class="pledge" name="pledge" method="post" action="/new">
<h2><?=_('New petition &#8211; Step 1 of 4') ?></h2>
<h3><?=_('Your petition')?></h3>

<p><strong><?=$petition_prefix ?>...</strong> 
<br><textarea name="content" rows="5" cols="60" <? if (array_key_exists('content', $errors)) print ' class="error"' ?> ><? if (isset($data['content'])) print htmlspecialchars($data['content']) ?></textarea>

<p><?=_('People must sign up before') ?> <input<? if (array_key_exists('rawdeadline', $errors)) print ' class="error"' ?> title="<?=_('Deadline date') ?>" type="text" id="rawdeadline" name="rawdeadline" onfocus="fadein(this)" onblur="fadeout(this)" value="<? if (isset($data['rawdeadline'])) print htmlspecialchars($data['rawdeadline']) ?>"> <small>(<?=_('e.g.') ?> "<?
    print date('jS F Y', $pb_time+60*60*24*28); // 28 days
?>")</small></p>

<p><?=_('Short description (title) of your petition:') ?> 
<br><input<? if (array_key_exists('title', $errors)) print ' class="error"' ?> onkeyup="checklength(this)" type="text" size="60" maxlength="60" id="title" name="title" value="<? if (isset($data['title'])) print htmlspecialchars($data['title']) ?>"> 
</p>

<p><?=_('Choose a short name for your petition (6 to 16 letters):') ?> 
<input<? if (array_key_exists('ref', $errors) || array_key_exists('ref2', $errors)) print ' class="error"' ?> onkeyup="checklength(this)" type="text" size="16" maxlength="16" id="ref" name="ref" value="<? if (isset($data['ref'])) print htmlspecialchars($data['ref']) ?>"> 
<br><small><?=_('This gives your petition an easy web address. e.g. petition.owl/tidyupthepark') ?></small>
</p>

<h3><?=_('About You') ?></h3>
<p style="margin-bottom: 1em;"><strong><?=_('Your name:') ?></strong> <input<? if (array_key_exists('name', $errors)) print ' class="error"' ?> onblur="fadeout(this)" onfocus="fadein(this)" type="text" size="20" name="name" id="name" value="<? if (isset($data['name'])) print htmlspecialchars($data['name']) ?>">
<strong><?=_('Email:') ?></strong> <input<? if (array_key_exists('email', $errors)) print ' class="error"' ?> type="text" size="30" name="email" value="<? if (isset($data['email'])) print htmlspecialchars($data['email']) ?>">
<br><small><?=_('(we need your email so we can get in touch with you when your petition completes, and so on)') ?></small>

<? if (sizeof($data)) {
    print '<input type="hidden" name="data" value="' . base64_encode(serialize($data)) . '">';
} ?>
<p style="text-align: center">
<?=_("Did you read the tips at the top of the page? They'll help you make a successful petition") ?> 
<input type="submit" name="tostep2" value="<?=_('Next') ?> &gt;&gt;&gt;"></p>
</form>
<? 
}

function pledge_form_target_warning($data, $errors) {

	if (sizeof($errors)) {
		print '<div id="errors"><ul><li>';
		print join ('</li><li>', array_values($errors));
		print '</li></ul></div>';
    }

    print '<p>' . _('Your pledge looks like this so far:') . '</p>';
    $isodate = $data['parseddate']['iso'];
    $row = $data; unset($row['parseddate']); $row['date'] = $isodate;
    $partial_pledge = new Pledge($row);
    $partial_pledge->render_box(array('showdetails' => true));

    $must_gather = $data['target'] * 0.025; # 2.5%, as in pb_pledge_prominence_calculated in db
    if ($must_gather < 2)
        $must_gather = 2;
    $percent_successful_above_warn = percent_success_above(OPTION_PB_TARGET_WARNING);

    
?>

<form accept-charset="utf-8" id="pledgeaction" name="pledge" method="post" action="/new">

<?  print h2(_('Please lower your target!'));

    printf(p(_("Recruiting more than %d people to a pledge is much harder than
    most people think. <strong>Only %0.0f%%</strong> of people who have set
    pledge targets at %d or above have succeeded - why risk it? Does
    your pledge really need so many people to be worthwhile?")),
    OPTION_PB_TARGET_WARNING, $percent_successful_above_warn, OPTION_PB_TARGET_WARNING);

    printf(p(_("In order to encourage people to aim at achievable targets, we don't
    automatically show pledges with less than 2.5%% of signers on the
    PledgeBank site. In the case of your pledge target, that would mean
    you would need to sign up %d signers before your pledge would show up
    on our 'All Pledges' page, in our search listings, or before it would
    be automatically mailed to local PledgeBank alert subscribers (if it
    is a local pledge).")), $must_gather);

    print(p(_("We've <strong>reduced your target to 10</strong> for now. Please
    use this box if you want to change back to another target.  There is 
    <a href=\"/faq#targets\">more advice</a> about choosing a target in the
    FAQ.")));

?>
<p><?=_('<strong>My target</strong> is ') ?>
<input<? if (array_key_exists('target', $errors)) print ' class="error"' ?> onchange="pluralize(this.value)" title="<?=_('Target number of people') ?>" size="5" type="text" id="target" name="target" value="10">
<strong><?=$data['type']?></strong></p>

<p><?=_('Remember, a small but successful pledge can be the perfect preparation
for a larger and more ambitious one. You can mail your subscribers, and ask
them to help you with something bigger.') ?></p>

<p style="text-align: right;">
<input type="hidden" name="data" value="<?=base64_encode(serialize($data)) ?>">

<input class="topbutton" type="submit" name="donetargetwarning" value="<?=_('Next') ?> &gt;&gt;&gt;">
<br><input type="submit" name="tostep1" value="&lt;&lt; <?=_('Back to step 1') ?>">
</p>

</form>

<?
}


function pledge_form_two($data, $errors = array()) {
    $local = (array_key_exists('local', $data)) && $data['local'] == '1';
    $notlocal = (array_key_exists('local', $data)) && $data['local'] == '0';
    $isodate = $data['parseddate']['iso'];
    if (!isset($data['comparison']))
        $comparison = "atleast";
    else
        $comparison = $data['comparison'];

    /* The 'country' parameter may give a (country, state) pair. */
    $country = null;
    $state = null;
    if (isset($data['country'])) {
        $a = array();
        if (preg_match('/^([A-Z]{2}),(.+)$/', $data['country'], $a))
            list($x, $country, $state) = $a;
        else
            $country = $data['country'];
    }

    $place = null;
    if (array_key_exists('place', $data))
        $place = $data['place'];
    if ($country && $country == 'Global')
        $place = null;
    else {
        # Check gaze has this country
        $countries_with_gazetteer = gaze_get_find_places_countries();
        gaze_check_error($countries_with_gazetteer);
        if (!in_array($country, $countries_with_gazetteer)) {
            $place = null;
        }
    }
    $places = null;
    if ($place) {
        # Look up nearby places
        $places = pb_gaze_find_places($country, $state, $place, 10, 0);
        if (array_key_exists('gaze_place', $errors)) {
            if (count($places) > 0) {
                # message printed in pb_view_gaze_place_choice
            } else {
                $errors['place'] = sprintf(_("Unfortunately, we couldn't find anywhere with a name like '%s'.  Please try a different spelling, or another nearby village, town or city."),
                htmlspecialchars($place));
            }
            unset($errors['gaze_place']); # remove NOTICE
        } 
    }

    if (sizeof($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', $errors);
        print '</li></ul></div>';
    } else {
?>

<p><?=_('Your pledge looks like this so far:') ?></p>
<?  }
    $row = $data; unset($row['parseddate']); $row['date'] = $isodate;
    $partial_pledge = new Pledge($row);
    $partial_pledge->render_box(array('showdetails' => true));
?>

<form accept-charset="utf-8" id="pledgeaction" name="pledge" method="post" action="/new">
<h2><?=_('New Pledge &#8211; Step 2 of 4') ?></h2>

<input type="hidden" name="comparison" value="atleast">
<? /* <p>Should the pledge stop accepting new subscribers when it
is fulfilled?
<input type="radio" name="comparison" value="exactly"<?=($comparison == 'exactly') ? ' checked' : '' ?>> Yes
<input type="radio" name="comparison" value="atleast"<?=($comparison == 'atleast') ? ' checked' : '' ?>> No
</p> */?>

<p><?=_('Which country does your pledge apply to?') ?>
<? pb_view_gaze_country_choice($country, $state, $errors); ?>
</p>

<p id="local_line"><?=_('Within that country, is your pledge specific to a local area or specific place?') ?>
        <?=_('If so, we will help people who live nearby find your pledge.') ?>
<br><input <? if (array_key_exists('local', $errors)) print ' class="error"' ?> onclick="update_place_local(this, true)" type="radio" id="local1" name="local" value="1"<?=($local?' checked':'') ?>> <label onclick="this.form.elements['local1'].click()" for="local1"><?=_('Yes') ?></label>
<input <? if (array_key_exists('local', $errors)) print ' class="error"' ?> onclick="update_place_local(this, true)" type="radio" id="local0" name="local" value="0"<?=($notlocal?' checked':'') ?>> <label onclick="this.form.elements['local0'].click()" for="local0"><?=_('No') ?></label>
</p>

<p id="ifyes_line"><?
print _('If yes, choose where.');

// State is in the gaze_place list for selection, but stored with country later on
// :( See code commented "split out state" elsewhere in this file
$gaze_with_state = $data['gaze_place'];
if ($state)
    $gaze_with_state .= ", " . $state;
pb_view_gaze_place_choice($place, $gaze_with_state, $places, $errors, array_key_exists('postcode', $data) ? $data['postcode'] : null); 
?>

<p style="text-align: right;">
<input type="hidden" name="data" value="<?=base64_encode(serialize($data)) ?>">
<input class="topbutton" type="submit" name="tostep3" value="<?=_('Next') ?> &gt;&gt;&gt;">
<br><input type="submit" name="tostep1" value="&lt;&lt; <?=_('Back to step 1') ?>">
</p>

</form>
<?
}

function pledge_form_three($data, $errors = array()) {
    $v = 'all';
    if (isset($data['visibility'])) {
        $v = $data['visibility']; if ($v!='pin') $v = 'all';
    }
    $isodate = $data['parseddate']['iso'];

    if (sizeof($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', $errors);
        print '</li></ul></div>';
    } else {
?>

<p><?=_('Your pledge looks like this so far:') ?></p>
<?  }
    $row = $data; unset($row['parseddate']); $row['date'] = $isodate;
    $partial_pledge = new Pledge($row);
    $partial_pledge->render_box(array('showdetails' => true));
?>

<form accept-charset="utf-8" id="pledgeaction" name="pledge" method="post" action="/new">
<h2><?=_('New Pledge &#8211; Step 3 of 4') ?></h2>
<p><?=_('Which category does your pledge best fit into?') ?>
<select name="category">
<option value="-1"><?=_('(choose one)') ?></option>
<?
    /* XXX should do multiple categories, but ignore that for now. */
    $s = db_query('select id, parent_category_id, name from category
        where parent_category_id is null
        order by id');
    while ($a = db_fetch_row($s)) {
        list($id, $parent_id, $name) = $a;
        $out[_($name)] = sprintf("<option value=\"%s\"%s>%s%s</option>",
                    $id,
                    (array_key_exists('category', $data) && $id == $data['category'] ? ' selected' : ''),
                    (is_null($parent_id) ? '' : '&nbsp;-&nbsp;'),
                    htmlspecialchars(_($name)));
    }
    uksort($out, 'strcoll');
    foreach ($out as $n => $s) {
        print $s;
    }

?>
</select>
<br><small><?=_('(this will be used in future to help more people find your pledge)') ?></small>
</p>

<? if (microsites_private_allowed()) { ?>
<p><?=_('Who do you want to be able to see your pledge?') ?>
<br><input onclick="grey_pin(true)" type="radio" name="visibility" value="all"<?=($v=='all'?' checked':'') ?>> <?=_('Anyone') ?>
<input onclick="grey_pin(false)" type="radio" name="visibility" value="pin"<?=($v=='pin'?' checked':'') ?>> <?=_('Only people to whom I give this PIN:') ?>
<input <? if (array_key_exists('pin', $errors)) print ' class="error"' ?> type="text" id="pin" name="pin" value="">
</p>
<? } else { ?>
<input type="hidden" name="visibility" value="all">
<? } ?>

<p style="text-align: right;">
<input type="hidden" name="data" value="<?=base64_encode(serialize($data)) ?>">
<input class="topbutton" type="submit" name="topreview" value="<?=_('Preview') ?> &gt;&gt;&gt;">
<br><input type="submit" name="tostep2" value="&lt;&lt; <?=_('Back to step 2') ?>">
</p>

</form>

<?
}

function pledge_form_submitted() {
    global $lang, $microsite;
    $errors = array();
    $data = array();
    foreach (array_keys($_POST) as $field) {
        if ($field == 'ref' || $field == 'data' || $field == 'email')
            $data[$field] = get_http_var($field);
        else
            $data[$field] = get_http_var($field, true);
    }
    if (array_key_exists('title', $data))
        $data['lang'] = $lang;
    
    if (array_key_exists('data', $data)) {
        $alldata = unserialize(base64_decode($data['data']));
        if (!$alldata) $errors[] = _('Transferring the data from previous page failed :(');
        unset($data['data']);
        $data = array_merge($alldata, $data);
    }

    # Step 1 fixes
    if ($data['name']==_('<Enter your name>')) $data['name'] = '';
    $data['deadline'] = parse_date($data['rawdeadline']);

    # Step 1, main pledge details
    if (get_http_var('tostep1')) {
        pledge_form_one($data, $errors);
        return;
    }
    $errors = step1_error_check($data);
    if (sizeof($errors)) {
        pledge_form_one($data, $errors);
        return;
    }

    # Target warning
    if (get_http_var('donetargetwarning')) {
        $data['skiptargetwarning'] = 1;
    }
    if ($data['target'] > OPTION_PB_TARGET_WARNING && !array_key_exists('skiptargetwarning',$data)) {
        pledge_form_target_warning($data, $errors);
        return;
    }
    $errors = target_warning_error_check($data);
    if (sizeof($errors)) {
        pledge_form_target_warning($data, $errors);
        return;
    }
    
    # Step 2, location
    if (get_http_var('tostep2') || get_http_var('donetargetwarning')) {
        pledge_form_two($data, $errors);
        return;
    }
    $errors = step2_error_check($data);
    if (sizeof($errors)) {
        pledge_form_two($data, $errors);
        return;
    }
    if ($data['local'] == 1 && $data['country'] != 'Global' &&
             ((array_key_exists('prev_country', $data) && $data['prev_country'] != $data['country']) ||
              (array_key_exists('prev_place', $data) && $data['prev_place'] != $data['place']))
         ) {
        $data['gaze_place'] = ''; 
        $errors['gaze_place'] = 'NOTICE';
        pledge_form_two($data, $errors);
        return;
    }

    # Step 3, category, privacy
    if (get_http_var('tostep3')) {
        pledge_form_three($data, $errors);
        return;
    }
    if ($data['visibility'] != 'pin') { 
        $data['visibility'] = 'all'; 
        $data['pin'] = ''; 
    }
    $errors = step3_error_check($data);
    if (sizeof($errors)) {
        pledge_form_three($data, $errors);
        return;
    }

    # Step 4, preview
    if (get_http_var('topreview')) {
        preview_pledge($data, $errors);
        return;
    }
    $errors = preview_error_check($data);
    if (sizeof($errors)) {
        preview_pledge($data, $errors);
        return;
    }
 
    /* User must have an account to do this. */
    $data['reason_web'] = _('Before creating your new pledge, we need to check that your email is working.');
    $data['template'] = 'pledge-confirm';
    $P = person_signon($data, $data['email'], $data['name']);

    create_new_pledge($P, $data);
}

function step1_error_check($data) {
    global $pb_today;

    $errors = array();
    if (!$data['target']) $errors['target'] = _('Please enter a target');
    elseif (!ctype_digit($data['target']) || $data['target'] < 1) $errors['target'] = _('The target must be a positive number');

    $disallowed_refs = array('contact', 'translate', 'posters', 'graphs');
    if (!$data['ref']) $errors['ref'] = _('Please enter a short name for your pledge');
    elseif (strlen($data['ref'])<6) $errors['ref'] = _('The short name must be at least six characters long');
    elseif (strlen($data['ref'])>16) $errors['ref'] = _('The short name can be at most 20 characters long');
    elseif (in_array(strtolower($data['ref']), $disallowed_refs)) $errors['ref'] = _('That short name is not allowed.');
    elseif (preg_match('/[^a-z0-9-]/i',$data['ref'])) $errors['ref2'] = _('The short name must only contain letters, numbers, or a hyphen.  Spaces are not allowed.');
    elseif (!preg_match('/[a-z]/i',$data['ref'])) $errors['ref2'] = _('The short name must contain at least one letter.');

    $dupe = db_getOne('SELECT id FROM pledges WHERE ref ILIKE ?', array($data['ref']));
    if ($dupe) $errors['ref'] = _('That short name is already taken!');
    if (!$data['title']) $errors['title'] = _('Please enter a pledge');

    $pb_today_arr = explode('-', $pb_today);
    $deadline_limit_years = 5; # in years
    $deadline_limit = date('Y-m-d', mktime(12, 0, 0, $pb_today_arr[1], $pb_today_arr[2], $pb_today_arr[0] + $deadline_limit_years));
    if (!$data['date'] || !$data['parseddate']) $errors['date'] = _('Please enter a deadline');
    if ($data['parseddate']['iso'] < $pb_today) $errors['date'] = _('The deadline must be in the future');
    if ($data['parseddate']['error']) $errors['date'] = _('Please enter a valid date');
    if ($deadline_limit < $data['parseddate']['iso'])
        $errors['date'] = sprintf(_('Please change your deadline so it is less than %d years into the
        future. You must change the deadline in order to proceed with creating
        your pledge. If you want a longer deadline, please create your pledge
        with a short deadline, and drop us an email to <a href="mailto:team@pledgebank.com">team@pledgebank.com</a>
        asking for an alteration.'), $deadline_limit_years);

    if (!$data['name']) $errors['name'] = _('Please enter your name');
    if (!$data['email']) $errors['email'] = _('Please enter your email address');
    if (!validate_email($data['email'])) $errors['email'] = _('Please enter a valid email address');

    $mystreetmessage = htmlspecialchars(_('Please change <MY STREET> to the name of your street'));
    if (stristr($data['title'], "<MY STREET>")) $errors['title'] = $mystreetmessage;
    if (stristr($data['type'], "<MY STREET>")) $errors['type'] = $mystreetmessage;
    if (stristr($data['identity'], "<MY STREET>")) $errors['identity'] = $mystreetmessage;

    global $langs;
    if (!array_key_exists($data['lang'], $langs)) {
        $errors['lang'] = _('Unknown language code:') . ' ' . htmlspecialchars($data['lang']);
    }

    return $errors;
}

function target_warning_error_check($data) {
    global $pb_today;

    $errors = array();
    if (!$data['target'] || !ctype_digit($data['target']))
        $errors['target'] = _('Please enter a target');
/*  Higher target warning disabled, as was just annoying
    elseif ($data['target'] > OPTION_PB_TARGET_CAP) {
        $errors['target'] = sprintf(_('We have imposed a cap of
            %d people maximum on each pledge. This is not a
            hard limit, just a way of encouraging people to aim at smaller and more
            achievable targets. If you want a target higher than 
            %d people, we\'d be glad to set it up for you.
            Just drop us a quick email to <a
            href="mailto:team@pledgebank.com">team@pledgebank.com</a> letting us
            know who you are and what you are aiming to do.'), OPTION_PB_TARGET_CAP, OPTION_PB_TARGET_CAP);
    } */

    return $errors;
}
 
function step2_error_check(&$data) {
    global $countries_code_to_name, $countries_statecode_to_name;

    $errors = array();
    if ($data['comparison'] != 'atleast' && $data['comparison'] != 'exactly')
        $errors[] = _('Please select either "at least" or "exactly" number of people');
    if (!array_key_exists('country', $data) || !$data['country']) 
        $errors['country'] = _('Please choose which country your pledge applies to');
    elseif ($data['country'] != 'Global') {
        $a = array();
        $country = $data['country'];
        $state = null;
        if (preg_match('/^([A-Z]{2}),(.+)$/', $data['country'], $a))
            list($x, $country, $state) = $a;

        /* Validate country and/or state. */
        if (!array_key_exists($country, $countries_code_to_name))
            # TRANS: Ideally "none" will be translated here, but I can't see it as an entry in this .po file. How is this resolved? (Tim Morley, 2005-11-23)
	    # It's the entry "None &mdash; applies anywhere"; I guess this message could be reworded to mirror that, but as it says it directly afterward... (Matthew Somerville, http://www.mysociety.org/pipermail/mysociety-i18n/2005-November/000104.html)
	    $errors['country'] = _('Please choose a country, or "none" if your pledge applies anywhere');
        else if ($state && !array_key_exists($state, $countries_statecode_to_name[$country]))
            $errors['country'] = _('Please choose a valid state within that country, or the country name itself');
        else {
            # Check gaze has this country
            $countries_with_gazetteer = gaze_get_find_places_countries();
            gaze_check_error($countries_with_gazetteer);
            if (!in_array($country, $countries_with_gazetteer)) {
                $data['local'] = 0;
            }

            /* Can only check local stuff if a valid country is selected. */
            if (!array_key_exists('local', $data) || ($data['local'] != '1' && $data['local'] != '0'))
                $errors['local'] = _('Please choose whether the pledge is local or not');
            else if ($data['local']) {
                // Find exact matches
                if ($data['place']) {
                    $places = pb_gaze_find_places($country, $state, $data['place'], 10, 0);
                    $have_exact = have_exact_gaze_match($places, $data['place']);
                    if ($have_exact) {
                        list($desc, $radio_name) = pb_get_gaze_place_details($have_exact);
                        $data['gaze_place'] = $radio_name;
                        $data['prev_place'] = $data['place'];
                        $data['prev_country'] = $data['country'];
                        # print "new: have exact $desc $radio_name\n";
                    }
                }

                if ($data['postcode'] && $data['place'])
                    $errors['nohighlight'] = _("Please enter either a postcode or a place name, but not both");
                else if ($data['postcode']) {
                    if (!validate_postcode($data['postcode']) && !validate_partial_postcode($data['postcode']))
                        $errors['postcode'] = _('Please enter a valid postcode or first part of a postcode; for example, OX1 3DR or WC1.');
                    else if (mapit_get_error(mapit_get_location($data['postcode'], 1)))
                        $errors['postcode'] = _("We couldn't recognise that postcode or part of a postcode; please re-check it");
                    else
                        $data['postcode'] = canonicalise_partial_postcode($data['postcode']);
                } else if (($data['place'] && 
                            array_key_exists('prev_place', $data) && $data['prev_place'] == $data['place'] && 
                            array_key_exists('prev_country', $data) && $data['prev_country'] == $data['country'] && 
                            !$data['gaze_place'])
                           || !preg_match('/^-?(0|[1-9]\d*)((?:\.|,)\d*|)\|-?(0|[1-9]\d*)((?:\.|,)\d*|)\|.+$/', $data['gaze_place'])) { # XXX: Too inflexible for other locales?
                    if (!$data['place'])
                        $errors['place'] = _("Please enter a place name");
                    else 
                        $errors['gaze_place'] = "NOTICE"; # here to make it an error, overriden in form display code
                } else if (!$data['postcode'] && !$data['place']) {
                    $errors['place'] = ($data['country'] == 'GB'
                                        ? _("For a local pledge, please type a postcode or place name")
                                        : _("Please type a place name for your local pledge"));
                } else {
                    // Have gaze_place
                    if (array_key_exists($data['country'], $countries_statecode_to_name)) {
                        // Split out state in case where they picked US from dropdown, but place with state from gaze
                        $a = array();
                        if (preg_match('/^(.+), ([^,]+)$/', $data['gaze_place'], $a)) {
                            list($x, $data['gaze_place'], $state) = $a;
                            if ($data['prev_country'] == $data['country'])
                                $data['prev_country'] .= ",$state";
                            $data['country'] .= ",$state";
                        }
                    }
                }
                if ($data['postcode'] && $data['country'] != 'GB')
                    $errors['postcode'] = _("You can only enter a postcode if your pledge applies to the UK");
            }
        }
    }
    return $errors;
}

function step3_error_check(&$data) {
    $errors = array();
    if ($data['visibility'] == 'pin' && !$data['pin']) 
        $errors['pin'] = _('Please enter a pin');
    return $errors;
}

function preview_error_check($data) {
    $errors = array();
    return $errors;
}

function preview_pledge($data, $errors) {
    global $countries_code_to_name, $countries_statecode_to_name;

    $v = 'all';
    if (isset($data['visibility'])) {
        $v = $data['visibility']; if ($v!='pin') $v = 'all';
    }
    $local = (isset($data['local'])) ? $data['local'] : '0';
    $isodate = $data['parseddate']['iso'];
    if (!isset($data['comparison']))
        $comparison = "atleast";
    else
        $comparison = $data['comparison'];

    if (sizeof($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', array_values($errors));
        print '</li></ul></div>';
    } #    $png_flyers1_url = new_url("../flyers/{$ref}_A7_flyers1.png", false);

    print '<p>';
    printf(_('Your pledge, with short name <em>%s</em>, will look like this:'), $data['ref']);
    print '</p>';
    ?>

<form accept-charset="utf-8" id="pledgeaction" name="pledge" method="post" action="/new">
<input type="hidden" name="data" value="<?=base64_encode(serialize($data)) ?>">
<?  print h2(_('New Pledge &#8211; Step 4 of 4'));
    print p(sprintf(_('
Now please read your pledge (on the left) and check the details thoroughly.
<strong>Read carefully</strong> - we can\'t ethically let you %schange the wording%s of your pledge once people have
started to sign up to it.    
'), '<a href="/faq#editpledge" id="changethewording" onclick="return toggleNewModifyFAQ()">', '</a>')
);
?>

<div id="modifyfaq">
<?=h3(_("Why can't I modify my pledge after I've made it?"))?>

<?=p(_("People who sign up to a pledge are signing up to the specific wording of
the pledge. If you change the wording, then their signatures would no
longer be valid."))?>

</div>

<p style="text-align: right;">
<input type="submit" name="tostep1" value="&lt;&lt; <?=_('Change pledge text') ?>">
<br><input type="submit" name="tostep2" value="&lt;&lt; <?=_('Change location') ?>">
<br><input type="submit" name="tostep3" value="&lt;&lt; <?=_('Change category/privacy') ?>">
</p>

<?
    print '<p>' . _('When you\'re happy with your pledge, <strong>click "Create"</strong> to confirm that you wish PledgeBank.com to display the pledge at the top of this page in your name, and that you agree to the terms and conditions below.');
?>
<p style="text-align: right;">
<input type="submit" name="tocreate" value="<?=_('Create') ?> &gt;&gt;&gt;">
</p>
<?
    print h3(_('The Dull Terms and Conditions'));
    print "<p>";
    if ($v == 'pin' || !microsites_syndication_warning()) { ?>
<!-- no special terms for private pledge, or certain microsites -->
<?  } else {
        print _('By creating your pledge you also consent to the syndication of your pledge to other sites &mdash; this means that other people will be able to display your pledge and your name');
        if ($data['country'] == "GB" && $local) {
            print _(', and use (but not display) your postcode to locate your pledge in the right geographic area');
        }
        print '. ';
        print _('The purpose of this is simply to  your pledge
greater publicity and a greater chance of succeeding.');
        print ' ';
    }
    print _("Rest assured that we won't ever give or sell anyone your email address."); ?>
</p>

</form>
    <?
    $row = $data; unset($row['parseddate']); $row['date'] = $isodate;
    $partial_pledge = new Pledge($row);
    $partial_pledge->render_box(array('showdetails' => true));
    ?>

    <div id="otherdetails">
    <?=h3(_("Other Details"))?>
    <ul>
    <li><?=_('Category') ?>: <strong><?=
        $data['category'] == -1
            ? _('None')
            : htmlspecialchars(_(db_getOne('select name from category where id = ?', $data['category']))) // XXX show enclosing cat?
    ?></strong></li>
    <li><?=_('Privacy status') ?>: <strong><?
    if ($v=='all') print _('Public');
    if ($v=='pin') print _('Pledge can only be seen by people who I give the PIN to');
    ?></strong></li>
    </ul>
    </div>

<?
    
}

# Someone has submitted a new pledge
function create_new_pledge($P, $data) {
    $isodate = $data['parseddate']['iso'];
    if ($data['visibility'] == 'all')
        $data['pin'] = null;

    /* Guard against double-insertion. */
    db_query('lock table pledges in share mode');
        /* Can't just use SELECT ... FOR UPDATE since that wouldn't prevent an
         * insert on the table. */
    if (is_null(db_getOne('select id from pledges where ref = ?', $data['ref']))) {
        $data['id'] = db_getOne("select nextval('pledges_id_seq')");

        /* Optionally add a pledge location. */
        $location_id = null;
        if ($data['postcode']) {
            $location = mapit_get_location($data['postcode'], 1);
            if (mapit_get_error($location))
                /* This error should never happen, as earlier postcode validation in form will stop it */
                err('Invalid postcode while creating pledge; please check and try again.');
            $data['postcode'] = canonicalise_partial_postcode($data['postcode']);
            $location_id = db_getOne("select nextval('location_id_seq')");
            db_query("insert into location (id, country, method, input, latitude, longitude, description) values (?, 'GB', 'MaPit', ?, ?, ?, ?)", array($location_id, $data['postcode'], $location['wgs84_lat'], $location['wgs84_lon'], $data['postcode']));
        } else if ($data['gaze_place']) {
            list($lat, $lon, $desc) = explode('|', $data['gaze_place'], 3);
            $location_id = db_getOne("select nextval('location_id_seq')");
            $a = array();
            $country = $data['country'];
            $state = null;
            if (preg_match('/^([A-Z]{2}),(.+)$/', $country, $a))
                list($x, $country, $state) = $a;
            db_query("insert into location (id, country, state, method, input, latitude, longitude, description) values (?, ?, ?, 'Gaze', ?, ?, ?, ?)", array($location_id, $country, $state, $data['place'], $lat, $lon, $desc));
        } else if ($data['country'] <> 'Global') {
            $location_id = db_getOne("select nextval('location_id_seq')");
            $a = array();
            $country = $data['country'];
            $state = null;
            if (preg_match('/^([A-Z]{2}),(.+)$/', $country, $a))
                list($x, $country, $state) = $a;
            db_query("insert into location (id, country, state) values (?, ?, ?)", array($location_id, $country, $state));
        }
        $prominence = 'calculated';
        if (microsites_new_pledges_frontpage() || $data['lang']=='eo') {
            $prominence = 'frontpage';
        }
        db_query('
                insert into pledges (
                    id, title, target,
                    type, signup, date, datetext,
                    person_id, name, ref, 
                    creationtime,
                    detail,
                    comparison,
                    lang, location_id, microsite,
                    pin, identity, 
                    prominence
                ) values (
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, 
                    pb_current_timestamp(),
                    ?,
                    ?,
                    ?, ?, ?,
                    ?, ?,
                    ?
                )', array(
                    $data['id'], $data['title'], $data['target'],
                    $data['type'], $data['signup'], $isodate, $data['date'],
                    $P->id(), $data['name'], $data['ref'], 
                    $data['detail'],
                    $data['comparison'],
                    $data['lang'], $location_id, $data['microsite'],
                    $data['pin'] ? sha1($data['pin']) : null, $data['identity'],
                    $prominence
                ));

        if ($data['category'] != -1)
            db_query('
                insert into pledge_category (pledge_id, category_id)
                values (?, ?)',
                array($data['id'], $data['category']));

    }

    db_commit();
    $p = new Pledge($data['ref']); // Reselect full data set from DB

    // Comment alerts are no longer sent specially to pledge creators, so we
    // add them in for alerts explicitly here.
    alert_signup($P->id(), "comments/ref", array('pledge_id' => $p->id()));
    db_commit();

    global $page_title, $page_params;
    $page_title = _('Pledge Created');
    $page_params['noprint'] = true;

    $url = htmlspecialchars(pb_domain_url() . urlencode($p->data['ref']));
?>
    <p class="noprint loudmessage"><?=_('Thank you for creating your pledge.') ?></p>
    <p class="noprint loudmessage" align="center"><? printf(_('It is now live at %s<br>and people can sign up to it there.'), '<a href="'.$url.'">'.$url.'</a>') ?></p>
<?  if (microsites_new_pledges_frontpage()) { ?>
    <p class="noprint loudmessage" align="center"><?=_('Your pledge will <strong>not succeed</strong> unless people find out about it.  So get out there and tell your friends and colleagues about your pledge.') ?></p>
<?  } else { ?>
    <p class="noprint loudmessage" align="center"><?=_('Your pledge will <strong>not be publicised</strong> elsewhere on the site until a few people have signed it.  So get out there and tell your friends and neighbours about your pledge.') ?></p>
<?   } ?>
<?  post_confirm_advertise($p);
}

?>

