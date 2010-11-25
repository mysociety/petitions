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

if (cobrand_creation_category_first())
    $steps = array('', 'category', 'main', 'you', 'preview');
elseif (OPTION_SITE_TYPE == 'multiple')
    $steps = array('', 'you', 'main', 'preview');
elseif (OPTION_SITE_TYPE == 'one')
    $steps = array('', 'main', 'you', 'preview');

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
        call_user_func('petition_form_' . $steps[1], $steps, 1, $data);
    } elseif (OPTION_CREATION_DISABLED) {
        page_closed_message();
    } elseif (OPTION_SITE_NAME == 'number10') {
        # Special search for Number 10
        petition_search_first();
    } else {
        call_user_func('petition_form_' . $steps[1], $steps, 1);
    }
}
$contents = ob_get_contents();
ob_end_clean();
page_header($page_title, array());
cobrand_extra_heading($page_title);
print $contents;
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
    if (OPTION_CREATION_DISABLED && !$isedited) {
        page_closed_message();
        return;
    }

    foreach ($steps as $i => $step) {
        if (!$step) continue;
        call_user_func('petition_submitted_' . $step, &$data);
        if (get_http_var('tostep' . $step)) {
            call_user_func('petition_form_' . $step, $steps, $i, $data);
            return;
        }
        $errors = call_user_func('step_' . $step . '_error_check', &$data);
        if (sizeof($errors)) {
            call_user_func('petition_form_' . $step, $steps, $i, $data, $errors);
            return;
        }
    }
    petition_create($data);
}

function petition_submitted_category($data) {
    return; # Dummy function for loop, doesn't do anything
}

/*
 * Functions to tidy up incoming data
 */

function petition_submitted_main($data) {
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
}

function petition_submitted_you($data) {
    if (array_key_exists('name', $data) && $data['name']==_('<Enter your name>')) 
        $data['name'] = '';
    if (array_key_exists('overseas', $data) && $data['overseas']=='-- Select --') 
        $data['overseas'] = '';
}

function petition_submitted_preview($data) {
    if (!array_key_exists('comments', $data))
        $data['comments'] = '';
}

/* 
 * Various HTML utilities for these forms
 */

function startform() {
    print '<form accept-charset="utf-8" method="post" action="/new" name="newpetition">';
}

function nextprevbuttons($steps, $i) {
    print '<p align="right">';
    if ($i < count($steps)) {
        submit_button('tostep' . $steps[$i+1], 'Next');
        if ($i > 1) print "<br />";
    }
    if ($i > 1) {
        submit_button('tostep' . $steps[$i-1], 'Previous');
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

function textarea($name, $val, $cols, $rows, $required, $errors) {
    printf('<textarea id="%s" name="%s" cols="%d" rows="%d"%s%s>%s</textarea>',
            htmlspecialchars($name), htmlspecialchars($name),
            $cols, $rows,
            $required ? ' aria-required="true"' : '',
            array_key_exists($name, $errors) ? ' class="error"' : '',
            htmlspecialchars(is_null($val) ? '' : $val));
}

function textfield($name, $val, $size, $errors, $after = '') {
    printf('<input type="text" name="%s" id="%s" size="%d" value="%s"%s%s%s />',
            htmlspecialchars($name), htmlspecialchars($name),
            $size,
            htmlspecialchars(is_null($val) ? '' : $val),
            $name=='organisation' ? '' : ' aria-required="true"',
            $name=='email2' ? ' autocomplete="off"' : '',
            array_key_exists($name, $errors) ? ' class="error"' : '');
    if ($after)
        print ' <small>' . $after . '</small>';
}

function submit_button($name, $value) {
    printf('<input type="submit" name="%s" value="%s" class="button" />', $name, $value);
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

<p>Please note that you must <?=cobrand_creator_must_be()?> to create a petition.</p>

<p>First you must pick the relevant category for your petition. This is because the council
is only responsible for certain matters, and we need to make sure you are taken to the
appropriate place.</p>

<p><label for="category">Category:</label>
<select name="category" id="category">
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

    if ($site_name == 'elmbridge' && !$data['rawdeadline'])
        $data['rawdeadline'] = '90 days';

    startform();
    print cobrand_create_heading('New petition &#8211; Part ' . $step . ' of ' . petition_form_steps() . ' &#8211; Your petition');
    errorlist($errors);
?>

<p>Please fill in all the fields below. Please note that you must <?=cobrand_creator_must_be()?> to create a petition.</p>

<p><?
    echo '<strong><label for="pet_content">' . $petition_prefix;
    if (OPTION_SITE_TYPE == 'multiple') {
        if (OPTION_SITE_DOMAINS) {
            $body = db_getRow('select id, name from body where ref=?', $site_name);
        } else {
            $body = db_getRow('select id, name from body where id=?', $data['body']);
        }
        print "<input type='hidden' name='body' value='$body[id]' />";
        print $body['name'];
        echo ' to';
    }
    echo '...</label></strong> <br />';
    textfield('pet_content', $data['pet_content'], 70, $errors);
    echo '<br />';
    echo cobrand_creation_sentence_help();
?>
</p>
<p><label for="detail">More details about your petition (do not use block capitals &ndash; 1000 characters maximum):</label><br />
    <?
    textarea('detail', $data['detail'], 70, 7, false, $errors);
    ?>
</p>
<p><label for="rawdeadline">For how long would you like your petition to accept signatures?</label>
    <?
    $deadline_limits = cobrand_creation_deadline_limit();
    if ($deadline_limits['years'] && $deadline_limits['months']) {
        $maximum = sprintf('%d year, %d months', $deadline_limits['years'], $deadline_limits['months']);
    } elseif ($deadline_limits['years']) {
        $maximum = sprintf('%d year', $deadline_limits['years']);
    } elseif ($deadline_limits['months']) {
        $maximum = sprintf('%d months', $deadline_limits['months']);
    }
    textfield('rawdeadline', $data['rawdeadline'], 15, $errors, '(e.g. "2 months"; maximum ' . $maximum . ')');
    ?>
</p>

<p><label for="ref"><?=_('Choose a short name for your petition (6 to 16 letters):') ?></label>
    <?
    textfield('ref', $data['ref'], 16, $errors);
    ?>
<br /><small>This gives your petition an easy web address. e.g. http://<?=$_SERVER['HTTP_HOST'] ?>/<?=cobrand_creation_example_ref()?></small>
</p>

<?
    if (!cobrand_creation_category_first()) {
?>
<p><label for="category">Please select a category for your petition:</label>
<select name="category" id="category" aria-required="true">
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
<p>Please fill in the fields below. Please note that you must <?=cobrand_creator_must_be() ?> to create a petition.</p><?

    $fields = array(
            'name'  =>          _('Your name'),
            'organisation' =>   _('Organisation'),
            'address' =>        _('Address'),
            'postcode' =>       _('UK postcode'),
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

    foreach ($fields as $name => $desc) {
        if ($name == 'address' && ! cobrand_creation_ask_for_address() )
          continue; # skip loop: thereby suppressing address label as well as textarea input
          
        if (is_string($desc))
            printf('<p><label for="%s">%s:</label> ', $name, htmlspecialchars($desc));

        if (!array_key_exists($name, $data))
            $data[$name] = '';
        
        if ($name == 'address') {
            textarea($name, $data[$name], 30, 4, true, $errors);
            cobrand_creation_address_help();
        } elseif ($name == 'overseas') {
            if (!cobrand_creation_within_area_only()) {
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
            print '<p><span class="label">Type of address:</span> ';
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
            } elseif ($name == 'name') {
                $after = '(please use a full name e.g. Mr John Smith)';
            }
            textfield($name, $data[$name], $size, $errors, $after);
        }
        if (array_key_exists($name, $errors))
            print '<br /><span class="errortext">'. $errors[$name] . '</span>';

        if ($name == 'org_url' || $name == 'organisation' || ($name == 'telephone' && cobrand_creation_phone_number_optional()))
            print " <small>(optional)</small>";

        if (is_string($desc))
            print '</p>';
    }

    nextprevbuttons($steps, $step);
    print '</div>';
    endform($data);
}

function step_category_error_check($data) {
    $errors = array();
    if (!array_key_exists('category', $data)
      || !$data['category']
      || !array_key_exists($data['category'], cobrand_categories())) {
        $errors['category'] = 'Please select a category';
    } elseif (!cobrand_category_okay($data['category'])) {
        $errors['category_wrong'] = cobrand_category_wrong_action($data['category']);
    }
    return $errors;
}

/* step_main_error_check DATA
 * */
function step_main_error_check($data) {
    global $pet_today;

    $errors = array();

    $disallowed_refs = array('contact', 'translate', 'posters', 'graphs', 'privacy', 'reject');
    if (OPTION_SITE_TYPE == 'multiple') {
        if (!array_key_exists('body', $data) || !$data['body']) {
            $errors['body'] = _('Please pick who you wish to petition');
        } else {
            $q = db_query('SELECT ref FROM body WHERE id=?', array($data['body']));
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

    $ddd = preg_replace('#\s#', '', $data['detail']);
    if (strlen($ddd) > 1000)
        $errors['detail'] = _('Please make your more details a bit shorter (at most 1000 characters).');

    if (!array_key_exists('category', $data)
      || !$data['category']
      || !array_key_exists($data['category'], cobrand_categories())) {
        $errors['category'] = 'Please select a category';
    } elseif (!cobrand_category_okay($data['category'])) {
        $errors['category'] = 'Petitions in that category cannot currently be made (they have to go to a different place).';
    }

    $pet_today_arr = explode('-', $pet_today);
    $deadline_limits = cobrand_creation_deadline_limit();
    $deadline_limit = date('Y-m-d', mktime(12, 0, 0, $pet_today_arr[1] + $deadline_limits['months'], $pet_today_arr[2], $pet_today_arr[0] + $deadline_limits['years']));
    if (!$data['rawdeadline'] || !$data['deadline'])
        $errors['rawdeadline'] = _('Please enter a duration');
    elseif ($data['deadline_details']['error'])
        $errors['rawdeadline'] = _("Sorry, we did not recognise that duration. Please try again");
    elseif ($data['deadline'] < $pet_today)
        $errors['rawdeadline'] = _('The duration must be positive');
    elseif ($deadline_limit < $data['deadline']) {
        if ($deadline_limits['years'] && $deadline_limits['months']) {
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
function step_you_error_check($data) {
    global $pet_today;
    $errors = array();

    if (isset($data['e-mail'])) { $data['email'] = $data['e-mail']; unset($data['e-mail']); }
    if (isset($data['e-mail2'])) { $data['email2'] = $data['e-mail2']; unset($data['e-mail2']); }

    if (!validate_email($data['email'])) $errors['email'] = _('Please enter a valid email address');
    if (isset($data['email']) && isset($data['email2']) && $data['email'] != $data['email2'])
        $errors['email2'] = 'Please make sure your email addresses match';
    if ($data['postcode'] && !validate_postcode($data['postcode']))
        $errors['postcode'] = _('Please enter a valid postcode');

    $tel = preg_replace('#[^0-9]#', '', $data['telephone']);
    $tel = preg_replace('#^44#', '0', $tel);
    $tel = str_replace('+44', '0', $tel);
    if (cobrand_creation_phone_number_optional() && !$data['telephone']) {
        # Optional, so doesn't matter if blank
    } elseif (!preg_match('#[1-9]#', $data['telephone']))
        $errors['telephone'] = 'Please enter a telephone number';
    elseif (strlen($data['telephone']) < 10)
        $errors['telephone'] = 'That seems a bit short - please specify your full telephone number';
    elseif (!preg_match('#01[2-9][^1]\d{6,7}|01[2-69]1\d{7}|011[3-8]\d{7}|02[03489]\d{8}|07[04-9]\d{8}|00#', $tel))
        $errors['telephone'] = 'Please enter a valid telephone number';

    if (!$data['postcode'] && !$data['overseas']) {
        $errors['postcode'] = 'Please enter a valid postcode';
        if (!cobrand_creation_within_area_only()) {
            $errors['postcode'] .= ' or choose an option from the drop-down menu';
        }
    }
    if ($data['postcode'] && $data['overseas'])
        $errors['postcode'] = 'You can\'t both put a postcode and pick an option from the drop-down.';

    if (($area = cobrand_creation_within_area_only()) && $data['postcode'] && validate_postcode($data['postcode'])) {
        $areas = mapit_get_voting_areas($data['postcode']);
        if (is_object($areas)) { # RABX Error
            $errors['postcode'] = 'Sorry, we did not recognise that postcode.';
        } elseif ($area[1]){
            if (!in_array($area[1], $areas)) {
                $errors['postcode'] = sprintf("Sorry, that postcode is not within %s", $area[0]);
            }
        } else { # no area specified, check against the site's "body" data instead
            $body = db_getRow('SELECT * FROM body WHERE area_id in (' . join(',', array_values($areas)) . ')');
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
    if (cobrand_creation_ask_for_address()) {
        $vars['address'] = 'postal address';
    } else {
        # Set it to blank string as no form field printed at all.
        $data['address'] = '';
    }
    foreach ($vars as $var => $p_var) {
            if (!$data[$var]) $errors[$var] = 'Please enter your ' . $p_var;
    }
    return $errors;
}

function step_preview_error_check($data) {
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

<p align="right">
<?
    submit_button('tostepmain', 'Change petition text');
?>
</p>

<p>Please also check your contact details:</p>
<ul><li>Name: <strong><?=$data['name'] ?></strong></li>
<li>Email: <strong><?=$data['email'] ?></strong></li>
<li>Organisation: <strong><?=$data['organisation'] ?></strong></li>
<? if (cobrand_creation_ask_for_address()){
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

<p align="right">
<?
    submit_button('tostepyou', 'Change my contact details');
?>
</p>

<p>When you're happy with your petition, <strong>click "Create"</strong> to
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

<p align="right">
<?
    if (cobrand_creation_top_submit_button())
        submit_button('tocreate', 'Create');
?>
</p>

<h3 class="page_title_border">Petition Guidelines</h3>

<? cobrand_petition_guidelines(); ?>

<p>Petitioners may freely disagree with
<?=OPTION_SITE_NAME=='number10'?'the Government':OPTION_SITE_PETITIONED?> or
call for changes of policy. There will be no attempt to exclude critical views
and decisions will not be made on a party political basis.</p>

<p align="right">
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
