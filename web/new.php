<?
//
// new.php:
// New petitions form; also handles resubmission of rejected-once petitions.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: new.php,v 1.102 2010-05-06 12:30:59 matthew Exp $

require_once '../phplib/pet.php';
require_once '../phplib/fns.php';
require_once '../phplib/petition.php';
require_once '../phplib/token.php';
require_once '../phplib/cobrand.php';
require_once '../commonlib/phplib/datetime.php';
require_once '../commonlib/phplib/mapit.php';

$page_title = 'Create a petition';
ob_start();

if (cobrand_creation_category_first()) {
    $steps = array('', 'category', 'main', 'you', 'preview');
} elseif (OPTION_SITE_TYPE == 'multiple') {
    # XXX I think this could be main for domains too, it only needs to be this order
    # for if the body is being derived from the postcode...
    if (OPTION_SITE_DOMAINS) {
        $steps = array('', 'you', 'main', 'preview');
    } else {
        $steps = array('', 'main', 'you', 'preview');
    }
} elseif (OPTION_SITE_TYPE == 'one') {
    $steps = array('', 'main', 'you', 'preview');
}

if (get_http_var('toothercouncil')) {
    if ($url = cobrand_category_wrong_action(intval(get_http_var('category')), get_http_var('council'))) {
        header("Location: $url");
        exit;
    }
} elseif (get_http_var('tostepmain')
    || get_http_var('tostepyou')
    || get_http_var('tosteppreview')
    || get_http_var('tostepcategory')
    || get_http_var('tocreate')) {
    petition_form_submitted($steps);
} else {
    $token = get_http_var('token');
    if ($token && OPTION_SITE_APPROVAL) {
        $data = array('token' => $token);
        check_edited_petition($data);
        $fn = 'petition_form_' . $steps[1];
        $fn($steps, 1, $data);
    } elseif (cobrand_creation_disabled()) {
        page_closed_message();
    } else {
        $fn = 'petition_form_' . $steps[1];
        $fn($steps, 1);
    }
}
$contents = ob_get_contents();
ob_end_clean();
page_header($page_title, array());
cobrand_extra_heading($page_title);
print $contents;
cobrand_creation_extra_footer();
page_footer('Create');

/* check_edited_petition DATA
 * If a token is present in DATA, indicating that we are re-editing a rejected
 * petition, check the token and values in the database, and fill out DATA with
 * any missing values. Aborts if the token is invalid or if the petition has
 * already been resubmitted. Returns true if this is a rejected petition being
 * re-edited, or false otherwise. */
function check_edited_petition(&$data) {
    if (!array_key_exists('token', $data))
        return false;

    list($what, $id) = token_check($data['token']);
    if (!isset($what) || $what != 'e')
        /* Should never happen so just bail. */
        err("The supplied token is invalid");

    $petition = db_getRow('select * from petition where id = ?', $id);

    if ($petition['status'] != 'rejectedonce')
        err("Sorry, you cannot edit a petition in status \"${petition['status']}\"", E_USER_NOTICE);

    /* Fill out data with data from database. */
    $petition['pet_content'] = $petition['content'];
    foreach (array_keys($petition) as $field) {
        if (!array_key_exists($field, $data))
            $data[$field] = $petition[$field];
    }

    /* User may not edit the email field. */
    $data['email'] = $petition['email'];

    return true;
}

function petition_form_submitted($steps) {
    $errors = array();
    $data = array();

    if (!array_key_exists('token', $data) && get_http_var('token'))
        $data['token'] = get_http_var('token');

    if (isset($_GET['category'])) {
        $data['category'] = intval($_GET['category']);
    }

    foreach (array_keys($_POST) as $field) {
        $data[$field] = get_http_var($field);
    }

    if (array_key_exists('data', $data)) {
        $alldata = unserialize(base64_decode($data['data']));
        if (!$alldata) $errors[] = _('Transferring the data from previous page failed :(');
        unset($data['data']);
        $data = array_merge($alldata, $data);
    }

    $isedited = check_edited_petition($data);
    if (cobrand_creation_disabled() && !$isedited) {
        page_closed_message();
        return;
    }

    foreach ($steps as $i => $step) {
        if (!$step) continue;
        $fn = 'petition_submitted_' . $step;
        $errors = $fn($data);
        if (get_http_var('tostep' . $step)) {
            $fn = 'petition_form_' . $step;
            $fn($steps, $i, $data, $errors);
            return;
        }
        $fn = 'step_' . $step . '_error_check';
        $errors = $fn($data);
        if (sizeof($errors)) {
            $fn = 'petition_form_' . $step;
            $fn($steps, $i, $data, $errors);
            return;
        }
    }
    petition_create($data);
}

function petition_submitted_category(&$data) {
    return array(); # Dummy function for loop, doesn't do anything
}

/*
 * Functions to tidy up incoming data
 */

function petition_submitted_main(&$data) {
    global $pet_time;
    if (!array_key_exists('rawdeadline', $data)) $data['rawdeadline'] = '';
    $rawdeadline = $data['rawdeadline'];
    if (preg_match('#^\s*\d+\s*$#', $rawdeadline)) {
        $rawdeadline = $rawdeadline . ' months';
    }
    $data['deadline_details'] = datetime_parse_local_date($rawdeadline, $pet_time, 'en', 'GB');
    $data['deadline'] = $data['deadline_details']['iso'];
    if (OPTION_SITE_TYPE == 'one') {
        $data['body'] = null;
    }
    return array();
}

function petition_submitted_you(&$data) {
    if (array_key_exists('name', $data) && $data['name']==_('<Enter your name>'))
        $data['name'] = '';
    if (array_key_exists('overseas', $data) && $data['overseas']=='-- Select --')
        $data['overseas'] = '';
    if (!array_key_exists('address', $data) || $data['address'] == '-- Select --')
        $data['address'] = '';

    $errors = array();
    if (cobrand_creation_do_address_lookup() && array_key_exists('postcode', $data)) {
        if (!$data['postcode'] || !cobrand_validate_postcode($data['postcode'])) {
            $errors['postcode'] = _('Please enter a valid postcode');
        } else {
            $out = cobrand_perform_address_lookup($data['postcode']);
            if (array_key_exists('errors', $out))
                $errors['postcode'] = $out['errors'];
            if (array_key_exists('data', $out))
                $data['address_lookup'] = $out['data'];
        }
    }
    return $errors;
}

function petition_submitted_preview(&$data) {
    if (!array_key_exists('comments', $data))
        $data['comments'] = '';
    return array();
}

/*
 * Various HTML utilities for these forms
 */

function startform() {
    print '<form accept-charset="utf-8" method="post" action="/new" name="newpetition">';
}

function nextprevbuttons($steps, $i) {
    if (cobrand_creation_previous_button_first()) {
        print '<p class="leading">';
        if ($i > 1) {
            submit_button('tostep' . $steps[$i-1], 'Previous', true);
            if ($i < count($steps)) print ' ';
        }
        if ($i < count($steps)) {
            submit_button('tostep' . $steps[$i+1], 'Next');
        }
        print '</p>';
        return;
    }

    print '<p class="leading">';
    if ($i < count($steps)) {
        submit_button('tostep' . $steps[$i+1], 'Next');
        if ($i > 1) print cobrand_creation_button_separator();
    }
    if ($i > 1) {
        submit_button('tostep' . $steps[$i-1], 'Previous', true);
    }
    print '</p>';
}

function endform($data = null) {
    if (!is_null($data))
        printf('<input type="hidden" name="data" value="%s" />',
            htmlspecialchars(base64_encode(serialize($data))));
    print '</form>';
}

function errorlist($errors) {
    if (sizeof($errors))
        print cobrand_error_div_start() . '<p>Please check the following and try again:</p><ul><li>'
                . join('</li><li>',
                    array_map('htmlspecialchars', array_values($errors)))
                . '</li></ul></div>';
}

function formfield_class($name, $errors) {
    $class = cobrand_creation_input_class();
    if (array_key_exists($name, $errors)) {
        if ($class)
            $class[] = 'error';
        else
            $class = array('error');
    }
    if ($class) $class = ' class="' . join(' ', $class) . '"';
    return $class;
}

function textarea($name, $val, $cols, $rows, $required, $errors) {
    $class = formfield_class($name, $errors);
    printf('<textarea id="%s" name="%s" cols="%d" rows="%d"%s%s>%s</textarea>',
            htmlspecialchars($name), htmlspecialchars($name),
            $cols, $rows,
            $required ? ' aria-required="true"' : '',
            $class ? $class : '',
            htmlspecialchars(is_null($val) ? '' : $val));
}

function textfield($name, $val, $size, $errors, $after = '') {
    $class = formfield_class($name, $errors);
    printf('<input type="text" name="%s" id="%s" size="%d" value="%s"%s%s%s />',
            htmlspecialchars($name), htmlspecialchars($name),
            $size,
            htmlspecialchars(is_null($val) ? '' : $val),
            $name=='organisation' ? '' : ' aria-required="true"',
            $name=='email2' ? ' autocomplete="off"' : '',
            $class ? $class : '');
    if ($after)
        print ' <small>' . $after . '</small>';
}

function submit_button($name, $value, $previous = false) {
    $c = cobrand_creation_submit_button_class($previous);
    $class = $c ? $c : 'button';
    printf('<input type="submit" name="%s" value="%s" class="%s" />', $name, $value, $class);
}

/* petition_search_first
 * Make people search before creating a petition */
function petition_search_first() { ?>
<p><big>Welcome to the petition creation page.</big></p>

<p>There are several thousand petitions on this site.
Before creating a new petition, please use this box to check whether a petition already exists which calls for the same or similar action.
If so, please add your name to that petition.</p>

<p><strong>We will not accept petitions making similar or overlapping points.</strong></p>

<p>Once you've done your search, you can continue to create a new petition, or to sign an existing one.</p>

<?
    pet_search_form();
}

function petition_form_steps() {
    if (cobrand_creation_category_first())
        return 4;
    return 3;
}

/* petition_form_category
 * If we need to ask for category to route people appropriately */
function petition_form_category($steps, $step, $data = array(), $errors = array()) {
    startform();
    print cobrand_create_heading('New petition &#8211; Part ' . $step . ' of ' . petition_form_steps() . ' &#8211; Petition category');
    if (array_key_exists('category_wrong', $errors)) {
        print cobrand_error_div_start();
?>
<p><?=$errors['category_wrong'] ?></p>
</div>
<?
    } else {
        errorlist($errors);
    }
?>

<p><?= cobrand_creator_must_be() ?></p>

<p>First you must pick the relevant category for your petition. This is because the council
is only responsible for certain matters, and we need to make sure you are taken to the
appropriate place.</p>

<p><label for="category">Category:</label>
<select name="category" id="category"<?
    $class = formfield_class('category', $errors);
    if ($class) print $class;
?>>
<option value="">-- Select a category --</option><?
    foreach (cobrand_categories() as $id => $category) {
        if (!$id) continue;
        print '<option';
        if (array_key_exists('category', $data) && $id == $data['category'])
            print ' selected="selected"'; # I hate XHTML
        print ' value="' . $id . '">' . $category . '</option>';
    }
?>
</select></p>
<?
    nextprevbuttons($steps, $step);
    endform($data);
}

/* petition_form_main [DATA [ERRORS]]
 * Display the first stage of the petitions form. */
function petition_form_main($steps, $step, $data = array(), $errors = array()) {
    global $petition_prefix, $site_name;
    if (OPTION_SITE_NAME == 'number10') {
        echo 'There are 5 stages to the petition process:';
        echo petition_breadcrumbs(0);
        echo '<p><a href="/steps">More detailed description of these steps</a></p>';
    }
    foreach (array('pet_content', 'detail', 'rawdeadline', 'ref') as $x)
        if (!array_key_exists($x, $data)) $data[$x] = '';

    if (!$data['rawdeadline'] && ($default_deadline = cobrand_creation_default_deadline())) {
        $data['rawdeadline'] = $default_deadline;
    }

    $br = '';
    if (cobrand_creation_main_all_newlines()) $br = '<br />';

    $details_max_chars = cobrand_creation_detail_max_chars();
    startform();
    print cobrand_create_heading('New petition &#8211; Part ' . $step . ' of ' . petition_form_steps() . ' &#8211; Your petition');
    errorlist($errors);
?>

<p>
    <?=cobrand_fill_form_instructions()?>
    <?= cobrand_creator_must_be() ?>
</p>
<p><?
	# allow a body to be passed in explicitly (for the whypoll cobrand); note this may be body id or ref
	if (get_http_var('body')) {
		$data['body'] = cobrand_convert_name_to_ref(get_http_var('body'));
	}
    echo '<strong><label for="pet_content">' . $petition_prefix;
    if (OPTION_SITE_TYPE == 'multiple') {
        if (OPTION_SITE_DOMAINS) {
            $body = db_getRow('select id, name from body where ref=?', $site_name);
            print "<input type='hidden' name='body' value='$body[id]' />";
            print $body['name'];
            echo ' to';
        } else {
            $bodies = db_getAll('select id, ref, name from body order by name');
            echo '<select name="body" id="body">';
            print "<option value=''>-- Please select --</option>";
            foreach ($bodies as $body) {
                print "<option value='$body[id]'";
                if (isset($data['body']) && ($body['id'] == $data['body'] || $body['ref'] == $data['body']))
                    print ' selected';
                print ">$body[name]</option>";
            }
            echo '</select> to';
        }
    }

    echo '...</label></strong> <br />';
    textfield('pet_content', $data['pet_content'], 70, $errors);
    echo '<br />';
    echo cobrand_creation_sentence_help();
?>
</p>
<p><label for="detail">More details about your petition (do not use block capitals &ndash; <? echo $details_max_chars ?> characters maximum):</label><br />
    <?
    textarea('detail', $data['detail'], 70, 7, false, $errors);
    ?>
</p>
<p><label for="rawdeadline">For how long would you like your petition to accept signatures?</label><?=$br?>
    <?
    $example_string = "1 month";
    $deadline_limits = cobrand_creation_deadline_limit();
    if (array_key_exists('date', $deadline_limits)) {
        $example_string = '1 week';
        $maximum = date('jS F Y', strtotime($deadline_limits['date']));
    } elseif (array_key_exists('weeks', $deadline_limits)) {
        $example_string = "4 weeks";
        $maximum = sprintf('%d weeks', $deadline_limits['weeks']);
    } elseif ($deadline_limits['years'] && $deadline_limits['months']) {
        $maximum = sprintf('%d year, %d months', $deadline_limits['years'], $deadline_limits['months']);
    } elseif ($deadline_limits['years']) {
        $maximum = sprintf('%d year', $deadline_limits['years']);
    } elseif ($deadline_limits['months']) {
        if ($deadline_limits['months'] == 1) {
            $maximum = '1 month';
            $example_string = '2 weeks';
        } else {
            $maximum = sprintf('%d months', $deadline_limits['months']);
        }
    }
    $after = "(e.g. &ldquo;$example_string&rdquo;; maximum $maximum";
    $after .= cobrand_creation_duration_help() . ')';
    textfield('rawdeadline', $data['rawdeadline'], 15, $errors, $after);
    ?>
</p>

<p><label for="ref"><?=cobrand_creation_short_name_label() ?></label><?=$br?>
    <?
    textfield('ref', $data['ref'], 16, $errors);
    ?>
<br /><small>This gives your petition an easy web address. e.g. http://<?=$_SERVER['HTTP_HOST'] ?>/<?=cobrand_creation_example_ref()?></small>
</p>

<?
    if (cobrand_display_category() && !cobrand_creation_category_first()) {
?>
<p><label for="category">Please select a category for your petition:</label><?=$br?>
<select name="category" id="category" aria-required="true"<?
    $class = formfield_class('category', $errors);
    if ($class) print $class;
?>>
<option value="">-- Select a category --</option><?
    foreach (cobrand_categories() as $id => $category) {
        if (!$id) continue;
        print '<option';
        if (array_key_exists('category', $data) && $id == $data['category'])
            print ' selected="selected"'; # I hate XHTML
        print ' value="' . $id . '">' . $category . '</option>';
    }
?>
</select></p>
<?
    }
    nextprevbuttons($steps, $step);
    endform($data);
}

/* petition_form_you [DATA [ERRORS]]
 * Display the "about you" (second) section of the petition creation form. */
function petition_form_you($steps, $step, $data = array(), $errors = array()) {
    startform();
    print cobrand_create_heading('New petition &#8211; Part ' . $step . ' of ' . petition_form_steps() . ' &#8211; About you');
    errorlist($errors);
?>
<div id="new_you">
<p>Please fill in the fields below. <?= cobrand_creator_must_be() ?></p><?

    $fields = array(
            'name'  =>          _('Your name'),
            'organisation' =>   _('Organisation'),
            'address' =>        _('Address'),
            'postcode' =>       cobrand_postcode_label(),
            'overseas' =>       cobrand_overseas_dropdown(),
    );

    if (cobrand_creation_ask_for_address_type()) {
        $fields['address_type'] = true;
    }
    $fields['telephone'] = _('Telephone number');

    if (!array_key_exists('token', $data)) {
        $fields['email'] = _('Your email');
        $fields['email2'] = _('Confirm email');
    }

    list ($optional, $mandatory, $mandatory_legend) = cobrand_input_field_mandatory_markers();

    foreach ($fields as $name => $desc) {
        if ($name == 'address' && ! cobrand_creation_ask_for_address() )
          continue; # skip loop: thereby suppressing address label as well as textarea input

        if ($name == 'address' && cobrand_creation_do_address_lookup() && !array_key_exists('address_lookup', $data))
            continue;

        if ($name == 'overseas' && ! $desc) {
            continue; # council has suppressed the overseas dropdown (e.g., Sufffolk Coastal)
        }

        if ($name == 'address' && get_http_var('tostepyou') == 'Look up address') {
            print '<p class="errortext">Please pick an address from the list below:</p>';
        }

        if (is_string($desc)){
            if ($name == 'org_url' || $name == 'organisation' || ($name == 'postcode' && cobrand_creation_postcode_optional()) || ($name == 'telephone' && cobrand_creation_phone_number_optional())) {
                $mandatory_mark = $optional;
            } else {
                $mandatory_mark = $mandatory;
            }
            printf('<p><label for="%s">%s:</label> %s', $name, htmlspecialchars($desc), $mandatory_mark );
        }

        if (!array_key_exists($name, $data))
            $data[$name] = '';

        if ($name == 'address') {
            if (!cobrand_creation_do_address_lookup()) {
                textarea($name, $data[$name], 30, 4, true, $errors);
                cobrand_creation_address_help();
            } else {
?>
<select name="address" id="address">
<option>-- Select --</option>
<?              foreach ($data['address_lookup'] as $opt) {
                    print '<option';
                    if (array_key_exists('address', $data) && $opt == $data['address'])
                        print ' selected="selected"';
                    print ">$opt</option>";
                } ?>
</select>
<?
            }
        } elseif ($name == 'overseas') {
            if ($desc && !cobrand_creation_within_area_only() ) { /* desc is empty if council wants to suppress this */
?>
<p><label class="long" for="overseas">Or, if you're an
expatriate, you're in an overseas territory, a Crown dependency or in
the Armed Forces without a postcode, please select from this list:</label>
<select name="overseas" id="overseas">
<?          foreach ($desc as $opt) {
                print '<option';
                if ($opt == $data['overseas'])
                    print ' selected="selected"';
                print ">$opt</option>";
            } ?>
</select></p>
        <?
            }
        } elseif ($name == 'address_type') {
            $checked_home = $data['address_type'] == 'home' ? ' checked' : '';
            $checked_work = $data['address_type'] == 'work' ? ' checked' : '';
            $checked_study = $data['address_type'] == 'study' ? ' checked' : '';
            print '<p><span class="label">' . cobrand_creation_address_type_label() . ':</span> ' . $mandatory;
            print '<input type="radio" id="address_type_home" name="address_type" value="home"' . $checked_home . ' />
<label class="radio" for="address_type_home">Home</label>
<input type="radio" id="address_type_work" name="address_type" value="work"' . $checked_work . ' />
<label class="radio" for="address_type_work">Work</label>
<input type="radio" id="address_type_study" name="address_type" value="study"' . $checked_study . ' />
<label class="radio" for="address_type_study">Study</label>';
        } else {
            $size = 20;
            if ($name == 'postcode')
                $size = 10;
            else if ($name == 'telephone')
                $size = 15;
            $after = '';
            if ($name == 'email2') {
                $after = '<br />(We need your email so we can get in touch with you e.g. when your petition finishes)';
                if ($over = cobrand_creation_email_request()) {
                    $after = "<br />($over)";
                }
                $after = '<span id="ms-email2-note">' . $after . '<span>';
            } elseif ($name == 'name') {
                $after = '(please use a full name e.g. Mr John Smith)';
            } elseif ($name == 'postcode' && cobrand_creation_do_address_lookup()) {
                $after = '<input type="submit" name="tostepyou" value="Look up address">';
            }
            textfield($name, $data[$name], $size, $errors, $after);
        }
        if (array_key_exists($name, $errors))
            print '<br /><span class="errortext">'. $errors[$name] . '</span>';

        if ($name == 'org_url' || $name == 'organisation' || ($name == 'postcode' && cobrand_creation_postcode_optional()) || ($name == 'telephone' && cobrand_creation_phone_number_optional()))
            print " <small>(optional)</small>";

        if (is_string($desc))
            print '</p>';
    }

    print($mandatory_legend);
    nextprevbuttons($steps, $step);
    print '</div>';
    endform($data);
}

function step_category_error_check(&$data) {
    $errors = array();
    if (cobrand_display_category()) {
        if (!array_key_exists('category', $data)
          || !$data['category']
          || !array_key_exists($data['category'], cobrand_categories())) {
            $errors['category'] = 'Please select a category';
        } elseif (!cobrand_category_okay($data['category'])) {
            $errors['category_wrong'] = cobrand_category_wrong_action($data['category']);
        }
    }
    return $errors;
}

/* step_main_error_check DATA
 * */
function step_main_error_check(&$data) {
    global $pet_today;

    $errors = array();

    $disallowed_refs = array('contact', 'translate', 'posters', 'graphs', 'privacy', 'reject');
    if (OPTION_SITE_TYPE == 'multiple') {
        if (!array_key_exists('body', $data) || !$data['body']) {
            $errors['body'] = _('Please pick who you wish to petition');
        } else {
            /*
               By default, lookup is keyed on 'id', but use 'ref' (which is effectively the body's
               slug, and also unique) if we have alphachars in it: the whypoll javascript may be
               using ref instead of id.
            */
            $lookup_fieldname = 'id';
            if (preg_match('/[a-z]/i', $data['body'])) {
                $lookup_fieldname = 'ref';
            }
            $q = db_query("SELECT ref FROM body WHERE $lookup_fieldname=?", array($data['body']));
            if (!db_num_rows($q))
                $errors['body'] = _('Please pick a valid body to petition');
        }
    }
    if (!array_key_exists('ref', $data) || !$data['ref'])
        $errors['ref'] = _('Please enter a short name for your petition');
    elseif (strlen($data['ref']) < 6)
        $errors['ref'] = _('The short name must be at least six characters long');
    elseif (strlen($data['ref']) > 16)
        $errors['ref'] = _('The short name can be at most 16 characters long');
    elseif (in_array(strtolower($data['ref']), $disallowed_refs))
        $errors['ref'] = _('That short name is not allowed.');
    elseif (preg_match('/[^a-z0-9-]/i', $data['ref']))
        $errors['ref'] = _('The short name must only contain letters, numbers, or a hyphen.  Spaces are not allowed.');
    elseif (!preg_match('/[a-z]/i', $data['ref']))
        $errors['ref'] = _('The short name must contain at least one letter.');

    /*
     * We can reach this page either for a genuinely new petition, or for
     * editing a resubmitted rejected petition. In the latter case we will have
     * an 'e' token. A user is permitted to change the ref on a resubmitted
     * petition.
     */
    $check_ref = true;
    if (OPTION_SITE_APPROVAL && array_key_exists('token', $data)) {
        list($what, $id) = token_check($data['token']);
        $ref = db_getOne('select ref from petition where id = ?', $id);
        if (strtolower($ref) <> strtolower($data['ref']))
            $check_ref = true;
        else
            $check_ref = false;
    }

    if ($check_ref) {
        $dupe = db_getOne('select id from petition where lower(ref) = ?', strtolower($data['ref']));
        if ($dupe)
            $errors['ref'] = _('That short name is already taken');
    }

#    if (!$data['detail'])
#        $errors['detail'] = _('Please enter more details');
    if (!$data['pet_content'])
        $errors['pet_content'] = _('Please enter the text of your petition');

    $detail_max_chars = cobrand_creation_detail_max_chars();
    $ddd = preg_replace('#\s#', '', $data['detail']);
    if (strlen($ddd) > $detail_max_chars)
        $errors['detail'] = _('Please make your more details a bit shorter (at most ' . $detail_max_chars . ' characters).');

    if (cobrand_display_category()) {
        if (!array_key_exists('category', $data)
          || !$data['category']
          || !array_key_exists($data['category'], cobrand_categories())) {
            $errors['category'] = 'Please select a category';
        } elseif (!cobrand_category_okay($data['category'])) {
            $errors['category'] = 'Petitions in that category cannot currently be made (they have to go to a different place).';
        }
    }

    $deadline_limits = cobrand_creation_deadline_limit();
    if (array_key_exists('date', $deadline_limits)) {
        $deadline_limit = $deadline_limits['date'];
    } elseif (array_key_exists('weeks', $deadline_limits)) {
        $relative_limit = sprintf('+ %d weeks', $deadline_limits['weeks']);
        $deadline_limit = date('Y-m-d', strtotime($relative_limit, strtotime($pet_today)));
    } else {
        $pet_today_arr = explode('-', $pet_today);
        $deadline_limit = date('Y-m-d', mktime(12, 0, 0, $pet_today_arr[1] + $deadline_limits['months'], $pet_today_arr[2], $pet_today_arr[0] + $deadline_limits['years']));
    }
    if (!$data['rawdeadline'] || !$data['deadline'])
        $errors['rawdeadline'] = _('Please enter a duration');
    elseif ($data['deadline_details']['error'])
        $errors['rawdeadline'] = _("Sorry, we did not recognise that duration. Please try again");
    elseif ($data['deadline'] < $pet_today)
        $errors['rawdeadline'] = _('The duration must be positive');
    elseif ($deadline_limit < $data['deadline']) {
        if (array_key_exists('date', $deadline_limits)) {
            $errors['rawdeadline'] = sprintf(_('Please change your duration so it is before %s.'), date('jS F Y', strtotime($deadline_limits['date'])));
        } elseif (array_key_exists('weeks', $deadline_limits)) {
            $errors['rawdeadline'] = sprintf(_('Please change your duration so it is less than %d weeks.'), $deadline_limits['weeks']);
        } elseif ($deadline_limits['years'] && $deadline_limits['months']) {
            $errors['rawdeadline'] = sprintf(_('Please change your duration so it is less than %d year, %d months.'), $deadline_limits['years'], $deadline_limits['months']);
        } elseif ($deadline_limits['years']) {
            $errors['rawdeadline'] = sprintf(_('Please change your duration so it is less than %d year.'), $deadline_limits['years']);
        } elseif ($deadline_limits['months']) {
            $errors['rawdeadline'] = sprintf(_('Please change your duration so it is less than %d months.'), $deadline_limits['months']);
        }
    }

    return $errors;
}

/* step_you_error_check DATA
 * */
function step_you_error_check(&$data) {
    global $pet_today;
    $errors = array();

    if (isset($data['e-mail'])) { $data['email'] = $data['e-mail']; unset($data['e-mail']); }
    if (isset($data['e-mail2'])) { $data['email2'] = $data['e-mail2']; unset($data['e-mail2']); }

    if (!validate_email($data['email'])) $errors['email'] = _('Please enter a valid email address');
    if (isset($data['email']) && isset($data['email2']) && $data['email'] != $data['email2'])
        $errors['email2'] = 'Please make sure your email addresses match';
    if ($data['postcode'] && !cobrand_validate_postcode($data['postcode']))
        $errors['postcode'] = _('Please enter a valid postcode');

    $tel = preg_replace('#[^0-9+]#', '', $data['telephone']);
    $tel = preg_replace('#^44#', '0', $tel);
    $tel = str_replace('+44', '0', $tel);
    $tel = str_replace('+', '00', $tel);
    if (cobrand_creation_phone_number_optional() && !$data['telephone']) {
        # Optional, so doesn't matter if blank
    } elseif (!preg_match('#[1-9]#', $data['telephone']))
        $errors['telephone'] = 'Please enter a telephone number, including the area code';
    elseif (strlen($data['telephone']) < 10)
        $errors['telephone'] = 'That seems a bit short - please specify your full telephone number, including the area code';
    elseif (! cobrand_validate_phone_number($tel))
        $errors['telephone'] = 'Please enter a valid telephone number, including the area code';

    if (!cobrand_overseas_dropdown()) {
        $data['overseas'] = '';
    }

    if (!cobrand_creation_postcode_optional()) {
        if (!$data['postcode'] && !$data['overseas']) {
            $errors['postcode'] = 'Please enter a valid postcode';
            if (!cobrand_creation_within_area_only()) {
                $errors['postcode'] .= ' or choose an option from the drop-down menu';
            }
        }
        if ($data['postcode'] && $data['overseas']) {
            $errors['postcode'] = 'You can\'t both put a postcode and pick an option from the drop-down.';
        }
    }
    if (($area = cobrand_creation_within_area_only()) && $data['postcode'] && cobrand_validate_postcode($data['postcode'])) {
        $areas = mapit_call('postcode', $data['postcode']);
        if (is_object($areas)) { # RABX Error
            $errors['postcode'] = 'Sorry, we did not recognise that postcode.';
        } elseif ($area[1]) {
            if (!in_array($area[1], array_keys($areas['areas']))) {
                $errors['postcode'] = sprintf("Sorry, that postcode is not within %s", $area[0]);
            }
        } else { # no area specified, check against the site's "body" data instead
            $body = db_getRow('SELECT * FROM body WHERE area_id in (' . join(',', array_keys($areas['areas'])) . ')');
            if ($body) {
                if (!OPTION_SITE_DOMAINS)
                    $data['body'] = $body['id'];
            } else {
                $errors['postcode'] = sprintf("Sorry, that postcode is not within %s", $area[0]);
            }
        }
    }

    if (cobrand_creation_ask_for_address_type()) {
        if (!isset($data['address_type']) || !in_array($data['address_type'], array('home','work','study'))) {
            $errors['address_type'] = 'Please specify your address type';
        }
    } else {
        $data['address_type'] = '';
    }

    $vars = array(
        'name' => 'name',
        'email' => 'email address',
    );
    if (!cobrand_creation_phone_number_optional()) {
        $vars['telephone'] = 'phone number';
    }

    if (cobrand_creation_do_address_lookup()) {
        if (!$data['address']) $errors['address'] = 'Please pick an address';
    } elseif (cobrand_creation_ask_for_address()) {
        $vars['address'] = 'postal address';
    } else {
        # Set it to blank string as no form field printed at all.
        $data['address'] = '';
    }

    if (!cobrand_overseas_dropdown()) {
        $data['overseas'] = '';
    }

    foreach ($vars as $var => $p_var) {
        if (!$data[$var]) $errors[$var] = 'Please enter your ' . $p_var;
    }
    return $errors;
}

function step_preview_error_check(&$data) {
    $errors = array();
    return $errors;
}

function petition_form_preview($steps, $step, $data, $errors = array()) {
    errorlist($errors);
    print cobrand_create_heading('New petition &#8211; Part ' . $step . ' of ' . petition_form_steps());
?>
<p>Your petition, with short name <em><?=$data['ref'] ?></em>, will look like this:</p>
<?
    $partial_petition = new Petition($data);
    $partial_petition->h_display_box();

    startform();
    ?>
<p>Now please read through your petition, above, and check the details thoroughly.
<strong>Read carefully</strong> &ndash; we cannot let you change the wording of your petition once people have started to sign up to it.
People who sign up to a petition are signing up to the specific wording of
the petition. If you change the wording, then their signatures would no
longer be valid.
</p>

<p class="leading">
<?
    submit_button('tostepmain', 'Change petition text');
?>
</p>

<p>Please also check your contact details; these are simply so that we can get
in touch with you about your petition, and will not be public apart from
your name and organisation:</p>

<ul><li>Name: <strong><?=$data['name'] ?></strong></li>
<li>Email: <strong><?=$data['email'] ?></strong></li>
<li>Organisation: <strong><?=$data['organisation'] ?></strong></li>
<? if (cobrand_creation_ask_for_address()) {
      echo '<li>Address: <strong>' . $data['address'] . '</strong></li>';
    }
?>
<? if ($data['postcode']) { ?>
    <li>Postcode: <strong><?=$data['postcode'] ?></strong></li>
<? } elseif ($data['overseas']) { ?>
    <li><strong><?=$data['overseas'] ?></strong></li>
<?
    }
    if (cobrand_creation_ask_for_address_type()) {
        echo '<li>Address type: <strong>' . ucfirst($data['address_type']) . '</strong></li>';
    }
?>
<li>Telephone: <strong><?=($data['telephone'] ? $data['telephone'] : 'None provided') ?></strong></li>
</ul>

<p class="leading">
<?
    submit_button('tostepyou', 'Change my contact details');
?>
</p>

<p>When you are happy with your petition, <?= cobrand_click_create_instuction() ?> to
confirm that you wish this site to display the petition at the top
of this page in your name, and that you agree to the terms and conditions below.
<?
    if (OPTION_SITE_APPROVAL) {
?>
</p>
<p>
    <label for="comments">
        <? echo cobrand_creation_comments_label(); ?>
    </label>
</p>
<p>
<?
        textarea('comments', $data['comments'], 40, 7, false, $errors);
    }
?>
</p>

<p class="leading">
<?
    if (cobrand_creation_top_submit_button())
        submit_button('tocreate', 'Create');
?>
</p>

<? cobrand_petition_guidelines(); ?>

<p class="leading">
<?
    submit_button('tocreate', 'Create');
?>
</p>

<?
    endform($data);
}

/* petition_create DATA
 * Create or update a petition, using the fields in DATA. */
function petition_create($data) {
    global $pet_time;

    /* Recalculate deadline, as email confirmation might have been on a
     * different day. */
    $rawdeadline = $data['rawdeadline'];
    if (preg_match('#^\s*\d+\s*$#', $rawdeadline))
        $rawdeadline = $rawdeadline . ' months';
    $data['deadline_details'] = datetime_parse_local_date($rawdeadline, $pet_time, 'en', 'GB');
    $data['deadline'] = $data['deadline_details']['iso'];

    # One of postcode and overseas must be null, but normally passed around as empty strings
    if ($data['postcode']) $data['overseas'] = null;
    else $data['postcode'] = null;

    $data['detail'] = str_replace("\t", ' ', $data['detail']);
    $data['pet_content'] = str_replace("\t", ' ', $data['pet_content']);

    if (! cobrand_display_category()) $data['category'] = 0;

    if (OPTION_SITE_APPROVAL && array_key_exists('token', $data)) {
        /* Resubmitted petition. */
        list($what, $id) = token_check($data['token']);

        $n = db_do("
                update petition set
                    detail = ?, content = ?,
                    deadline = ?, rawdeadline = ?,
                    name = ?, ref = ?, organisation = ?,
                    postcode = ?, overseas = ?, telephone = ?, org_url = ?,
                    comments = ?, category = ?,
                    status = 'resubmitted',
                    laststatuschange = ms_current_timestamp(),
                    lastupdate = ms_current_timestamp()
                where id = ? and status = 'rejectedonce'",
                $data['detail'], $data['pet_content'],
                $data['deadline'], $data['rawdeadline'],
                $data['name'], $data['ref'], $data['organisation'],
                $data['postcode'], $data['overseas'], $data['telephone'], '',
                $data['comments'], $data['category'], $id);

        /* Send the admins an email about it. */
        pet_send_message($id, MSG_ADMIN, MSG_ADMIN, 'petition-resubmitted', 'admin-resubmitted-petition');

        db_commit();

        global $page_title;
        $page_title = _("Thank you for resubmitting your petition");
?>
    <p class="noprint loudmessage">We have resubmitted your petition for approval.
    You'll be notified shortly with the results.</p>
    <p class="noprint loudmessage"><a href="/">Petitions home</a>
<?
    } else {
        if (is_null(db_getOne('select id from petition where ref = ?', $data['ref']))) {
            $data['id'] = db_getOne("select nextval('global_seq')");

            /* Guard against double-insertion. */
            db_query('lock table petition in share mode');
                /* Can't just use SELECT ... FOR UPDATE since that wouldn't prevent an
                 * insert on the table. */

            db_query("
                    insert into petition (
                        id, body_id, detail, content,
                        deadline, rawdeadline,
                        email, name, ref,
                        organisation, address, address_type,
                        postcode, overseas, telephone, org_url,
                        comments, creationtime, category,
                        status, laststatuschange, lastupdate
                    ) values (
                        ?, ?, ?, ?,
                        ?, ?,
                        ?, ?, ?,
                        ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ms_current_timestamp(), ?,
                        'unconfirmed', ms_current_timestamp(), ms_current_timestamp()
                    )",
                    $data['id'], $data['body'], $data['detail'], $data['pet_content'],
                    $data['deadline'], $data['rawdeadline'],
                    $data['email'], $data['name'], $data['ref'],
                    $data['organisation'], $data['address'], $data['address_type'],
                    $data['postcode'], $data['overseas'], $data['telephone'], '',
                    $data['comments'], $data['category']);
            db_commit();
        }

        global $page_title;
        $page_title = cobrand_creation_check_heading();
        echo '<p class="noprint loudmessage">';
        if ($page_title != 'Now check your email') {
            echo '<strong>Now check your email.</strong> ';
        }
        echo 'We have sent you an email to confirm that we have received your petition details.';
        if (OPTION_SITE_APPROVAL) {
?>
    In order for us to approve
    your petition, we need you to open this email and click on an activation
    link, which will send your petition details to our team for approval.</p>
<?
        } else {
?>
    In order for us to show your petition, we need you to open this email and
    click on the activation link in it.</p>
<?
        }
    }
}

/* petition_breadcrumbs NUMBER
 * Numbered "breadcrumbs" trail for current user; NUMBER is the (1-based)
 * number of the step to hilight. */
function petition_breadcrumbs($num) {
    $steps = array(
                'Create&nbsp;your&nbsp;petition',
                'Submit&nbsp;your&nbsp;petition',
                'Petition&nbsp;approval',
                'Petition&nbsp;live',
                'Petition&nbsp;close'
    );
    /* Ideally we'd like the numbers to appear as a result of this being a
     * list, but that's beyond CSS's tiny capabilities, so put them in
     * explicitly. That means that two numbers will appear in non-CSS
     * browsers. */
    $str = '<ol id="breadcrumbs">';
    for ($i = 0; $i < sizeof($steps); ++$i) {
        if ($i == $num - 1)
            $str .= '<li class=\hilight">';
        else
            $str .= '<li>';
        $str .= ($i + 1) . '.&nbsp;' . $steps[$i] . '</li> ';
    }
    $str .= "</ol>\n";
    return $str;
}

?>
