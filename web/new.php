<?
// new.php:
// New petitions
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: new.php,v 1.10 2006-07-13 14:34:52 matthew Exp $

require_once '../phplib/pet.php';
require_once '../phplib/fns.php';
require_once '../phplib/petition.php';
require_once '../../phplib/datetime.php';
require_once '../../phplib/person.php';

$page_title = _('Create a new petition');
ob_start();
if (get_http_var('tostepintro') || get_http_var('tostepmain')
   || get_http_var('tostepyou') || get_http_var('tosteppreview')
   || get_http_var('tocreate') ) {
    petition_form_submitted();
} else {
    petition_form_intro(get_http_var('data'));
}
$contents = ob_get_contents();
ob_end_clean();
page_header($page_title, array());
print $contents;
page_footer();

function petition_form_submitted() {
    global $pet_time;
    $errors = array();
    $data = array();
    foreach (array_keys($_POST) as $field) {
        $data[$field] = get_http_var($field);
    }
    
    if (array_key_exists('data', $data)) {
        $alldata = unserialize(base64_decode($data['data']));
        if (!$alldata) $errors[] = _('Transferring the data from previous page failed :(');
        unset($data['data']);
        $data = array_merge($alldata, $data);
    }

    # Step 0, instructions
    if (get_http_var('tostepintro')) {
        petition_form_intro($data);
        return;
    }

    # Step 1 fixes
    if (!array_key_exists('rawdeadline', $data)) $data['rawdeadline'] = '';
    $data['deadline_details'] = datetime_parse_local_date($data['rawdeadline'], $pet_time, 'en', 'GB');
    $data['deadline'] = $data['deadline_details']['iso'];

    # Step 1, main petition details
    if (get_http_var('tostepmain')) {
        petition_form_main($data);
        return;
    }
    $errors = step_main_error_check($data);
    if (sizeof($errors)) {
        petition_form_main($data, $errors);
        return;
    }

    # Step 2 fixes
    if (array_key_exists('name', $data) && $data['name']==_('<Enter your name>')) 
        $data['name'] = '';

    # Step 2, your details
    if (get_http_var('tostepyou')) {
        petition_form_you($data, $errors);
        return;
    }
    $errors = step_you_error_check($data);
    if (sizeof($errors)) {
        petition_form_you($data, $errors);
        return;
    }

    # Step 3, preview
    if (get_http_var('tosteppreview')) {
        preview_petition($data, $errors);
        return;
    }
    $errors = preview_error_check($data);
    if (sizeof($errors)) {
        preview_petition($data, $errors);
        return;
    }
    $P = pet_send_logging_in_email('petition-confirm', $data, $data['email'], $data['name']);
    petition_create($P, $data);
}


function petition_form_intro($data = array()) {
?>
<h1><span dir="ltr"><?=_('Step-by-step guide to making petitions') ?></span></h1>
<ol>

<h3><span dir="ltr"><?=_('Step 1: Create your petition')?></span></h3>

<p><?=_('You will be asked to give your name, organisation (if you represent one),
address and email address, title and text of your petition. You will also be
asked to give a short, one-word name for your petition. This will be used to
give your petition a unique URL (website address) that you can use to publicise
your petition.')?></p>

<p><?=_('You will be able to specify a start and finish date for your petition, and we
will host your petition for up to 12 months.')?></p>

<h3><span dir="ltr"><?=_('Step 2: Submit your petition')?></span></h3>

<p><?=_('Once you have submitted your petition, you will receive an email asking
you to click a link to confirm your petition. Your proposed petition will then
be delivered to the Downing Street inbox.')?></p>

<h3><span dir="ltr"><?=_('Step 3: Petition approval')?></span></h3>

<p><?=_('Officials at Downing Street will check your petition to make sure that it meets
the basic requirements set out in our acceptable use policy (link) and the
Civil Service code.')?></p>

<p><?=_('If for any reason we cannot accept petition, we will write to you to explain
why. You will be able to edit and resubmit your petition if you wish.')?></p>

<p><?=_('Once your petition is approved, we will email you to confirm a date for it to
appear on the website.')?></p>

<p><?=_('If we cannot approve your amended petition, we will write to you again to
explain our reason(s). ')?></p>

<p><?=_('Any petitions that are rejected or not resubmitted will be published on this
website, along with the reason(s) why it was rejected. Any content that is
offensive or illegal will be left out. Every petition that is received will be
acknowledged on this website.')?></p>

<h3><span dir="ltr"><?=_('Step 4: Petition live')?></span></h3>

<p><?=_('Once your petition is live, you will be able to publicise the URL (website
address) you chose when you created your petition, and anyone will be able to
come to the website and sign it.')?></p>

<p><?=_('They will be asked to give their name and address and an email address that we
can verify. The system is designed to identify duplicate names and addresses,
will not allow someone to sign a petition more than once. Anyone signing a
petition will be sent an email asking them to click a link to confirm that they
have signed the petition. Once they have done this, their name will be added to
the petition.')?></p>

<p><?=_('Your petition will show the total number of signatures received. It will also
display the names of signatories, unless they have opted not to be shown.')?></p>

<h3><span dir="ltr"><?=_('Step 5: Petition close')?></span></h3>

<p><?=_('When the petition closes, officials at Downing Street will ensure you get a
response to the issues you raise. Depending on the nature of the petition, this
may be from the Prime Minister, or he may ask one of his Ministers or officials
to respond.')?>

<p><?=_('We will email the petition organiser and everyone who has signed the
petition via this website giving details of the Government’s response.')?>

<form accept-charset="utf-8" method="post" action="/new">
<? if (sizeof($data)) {
    print '<input type="hidden" name="data" value="' . base64_encode(serialize($data)) . '">';
} ?>
<p style="text-align: right">
<input type="submit" name="tostepmain" value="<?=_('Next') ?> &gt;&gt;&gt;"></p>
</form>
<? 
}

function petition_form_main($data = array(), $errors = array()) {
    global $pet_time, $petition_prefix;
    if (sizeof($errors)) {
        print '<ul class="errors"><li>';
        print join ('</li><li>', array_values($errors));
        print '</li></ul>';
    }
?>

<form accept-charset="utf-8" method="post" action="/new">
<h2><span dir="ltr"><?=_('New petition &#8211; Part 1 of 3 &#8211; Your petition') ?></span></h2>

<p><strong><?=$petition_prefix ?>...</strong> 
<br><textarea name="content" rows="7" cols="40"<? if (array_key_exists('content', $errors)) print ' class="error"' ?>><? if (isset($data['content'])) print htmlspecialchars($data['content']) ?></textarea>

<p><?=_('Title of your petition:') ?> 
<br><input<? if (array_key_exists('title', $errors)) print ' class="error"' ?> type="text" size="60" maxlength="100" id="title" name="title" value="<? if (isset($data['title'])) print htmlspecialchars($data['title']) ?>"> 
</p>

<p><?=_('People must sign up before') ?> <input<? if (array_key_exists('date', $errors)) print ' class="error"' ?> title="<?=_('Deadline date') ?>" type="text" id="rawdeadline" name="rawdeadline" onfocus="fadein(this)" onblur="fadeout(this)" value="<? if (isset($data['rawdeadline'])) print htmlspecialchars($data['rawdeadline']) ?>"> <small>(<?=_('e.g.') ?> "<?
    print date('jS F Y', $pet_time+60*60*24*28); // 28 days
?>")</small></p>

<p><?=_('Choose a short name for your petition (6 to 16 letters):') ?> 
<input<? if (array_key_exists('ref', $errors) || array_key_exists('ref2', $errors)) print ' class="error"' ?> onkeyup="checklength(this)" type="text" size="16" maxlength="16" id="ref" name="ref" value="<? if (isset($data['ref'])) print htmlspecialchars($data['ref']) ?>"> 
<br><small><?=_('This gives your petition an easy web address. e.g. http://petitions.number10.gov.uk/badgers') ?></small>
</p>

<? if (sizeof($data)) {
    print '<input type="hidden" name="data" value="' . base64_encode(serialize($data)) . '">';
} ?>
<p style="text-align: right">
<input type="submit" name="tostepyou" value="<?=_('Next') ?> &gt;&gt;&gt;">
<br><input type="submit" name="tostepintro" value="<?=_('Previous') ?> &lt;&lt;&lt;">
</p>
</form>
<? 
}

function petition_form_you($data = array(), $errors = array()) {
    if (sizeof($errors)) {
        print '<ul class="errors"><li>';
        print join ('</li><li>', array_values($errors));
        print '</li></ul>';
    }

    $P = person_if_signed_on();
    if (!is_null($P)) {
        if (!array_key_exists('email', $data))
            $data['email'] = $P->email();
        if (!array_key_exists('name', $data))
            $data['name'] = $P->name_or_blank();
    }

?>
<form accept-charset="utf-8" method="post" action="/new">
<h2><span dir="ltr"><?=_('New petition &#8211; Part 2 of 3 &#8211; About you') ?></span></h2>

<div><strong><?=_('Your name:') ?></strong> <input<? if (array_key_exists('name', $errors)) print ' class="error"' ?> onblur="fadeout(this)" onfocus="fadein(this)" type="text" size="20" name="name" id="name" value="<? if (isset($data['name'])) print htmlspecialchars($data['name']) ?>" />
<br><br><strong>Organisation:</strong>
<input type="text" name="organisation" value="<? if (isset($data['organisation'])) print htmlspecialchars($data['organisation']) ?>" size="20" /> (optional)
<br><br><strong style="float: left">Address:&nbsp;</strong>
<textarea<? if (array_key_exists('address', $errors)) print ' class="error"' ?> name="address" rows="4" cols="30"><? if (isset($data['address'])) print htmlspecialchars($data['address']) ?>
</textarea>
<br><br><strong>Postcode:</strong>
<input<? if (array_key_exists('postcode', $errors)) print ' class="error"' ?> type="text" name="postcode" value="<? if (isset($data['postcode'])) print htmlspecialchars($data['postcode']) ?>" size="10" />
<br><br><strong>Telephone number:</strong>
<input<? if (array_key_exists('telephone', $errors)) print ' class="error"' ?> type="text" name="telephone" value="<? if (isset($data['telephone'])) print htmlspecialchars($data['telephone']) ?>" size="15" />
<br><br><strong>URL of campaign/organisation</strong>
<input<? if (array_key_exists('org_url', $errors)) print ' class="error"' ?> type="text" name="org_url" value="<? if (isset($data['org_url'])) print htmlspecialchars($data['org_url']) ?>" size="20" /> (optional)
<br><br>
<strong><?=_('Your email:') ?></strong> <input<? if (array_key_exists('email', $errors)) print ' class="error"' ?> type="text" size="30" name="email" value="<? if (isset($data['email'])) print htmlspecialchars($data['email']) ?>">
<br><strong><?=_('Confirm email:') ?></strong> <input<? if (array_key_exists('email2', $errors)) print ' class="error"' ?> type="text" size="30" name="email2" value="<? if (isset($data['email2'])) print htmlspecialchars($data['email2']) ?>">
<br><small><?=_('(we need your email so we can get in touch with you when your petition completes, and so on)') ?></small>
</div>

<? if (sizeof($data)) {
    print '<input type="hidden" name="data" value="' . base64_encode(serialize($data)) . '">';
} ?>
<p style="text-align: right">
<input type="submit" name="tosteppreview" value="<?=_('Next') ?> &gt;&gt;&gt;">
<br><input type="submit" name="tostepmain" value="<?=_('Previous') ?> &lt;&lt;&lt;"></p>
</form>
<? 
}


function step_main_error_check($data) {
    global $pet_today;

    $errors = array();

    $disallowed_refs = array('contact', 'translate', 'posters', 'graphs');
    if (!$data['ref']) $errors['ref'] = _('Please enter a short name for your petition');
    elseif (strlen($data['ref'])<6) $errors['ref'] = _('The short name must be at least six characters long');
    elseif (strlen($data['ref'])>16) $errors['ref'] = _('The short name can be at most 16 characters long');
    elseif (in_array(strtolower($data['ref']), $disallowed_refs)) $errors['ref'] = _('That short name is not allowed.');
    elseif (preg_match('/[^a-z0-9-]/i',$data['ref'])) $errors['ref2'] = _('The short name must only contain letters, numbers, or a hyphen.  Spaces are not allowed.');
    elseif (!preg_match('/[a-z]/i',$data['ref'])) $errors['ref2'] = _('The short name must contain at least one letter.');

    $dupe = db_getOne('SELECT id FROM petition WHERE ref ILIKE ?', array($data['ref']));
    if ($dupe) $errors['ref'] = _('That short name is already taken!');
    if (!$data['title']) $errors['title'] = _('Please enter a title');
    elseif (strlen($data['title']) > 100) $errors['title'] = _('Please make the title a bit shorter (at most 100 characters).');
    if (!$data['content']) $errors['content'] = _('Please enter the text of your petition');

    $pet_today_arr = explode('-', $pet_today);
    $deadline_limit_years = 1; # in years
    $deadline_limit = date('Y-m-d', mktime(12, 0, 0, $pet_today_arr[1], $pet_today_arr[2], $pet_today_arr[0] + $deadline_limit_years));
    if (!$data['rawdeadline'] || !$data['deadline']) $errors['date'] = _('Please enter a deadline');
    if ($data['deadline'] < $pet_today) $errors['date'] = _('The deadline must be in the future');
    if ($data['deadline_details']['error']) $errors['date'] = _('Please enter a valid date for the deadline');
    if ($deadline_limit < $data['deadline'])
        $errors['date'] = sprintf(_('Please change your deadline so it is less than %d year in the future.'), $deadline_limit_years);

    return $errors;
}

function step_you_error_check($data) {
    global $pet_today;
    $errors = array();
    if (!validate_email($data['email'])) $errors['email'] = _('Please enter a valid email address');
    if (isset($data['email']) && isset($data['email2']) && $data['email'] != $data['email2'])
        $errors['email2'] = 'Please make sure your email addresses match';
    if (!validate_postcode($data['postcode'])) $errors['postcode'] = _('Please enter a valid postcode');
    if (!preg_match('#\d#', $data['telephone']))
        $errors['telephone'] = 'Please enter a valid telephone number';
    $vars = array(
        'name' => 'name',
        'address' => 'postal address',
        'email' => 'email address',
        'postcode' => 'postcode',
        'telephone' => 'phone number',
    );
    foreach ($vars as $var => $p_var) {
    	if (!$data[$var]) $errors[$var] = 'Please enter your ' . $p_var;
    }
    return $errors;
}

function preview_error_check($data) {
    $errors = array();
    return $errors;
}

function preview_petition($data, $errors) {
    if (sizeof($errors)) {
        print '<ul class="errors"><li>';
        print join ('</li><li>', array_values($errors));
        print '</li></ul>';
    }
?>
<h1><span dir="ltr"><?=_('New petition &#8211; Part 3 of 3')?></span></h1>
<p>Your petition, with short name <em><?=$data['ref'] ?></em>, will look like this:</p>
<?
    $partial_pledge = new Petition($data);
    $partial_pledge->h_display_box();
?>

<form accept-charset="utf-8" method="post" action="/new">
<input type="hidden" name="data" value="<?=base64_encode(serialize($data)) ?>">
<p>Now please read through your petition, above, and check the details thoroughly.
<strong>Read carefully</strong> - we can't let you
<a href="/faq#editpledge" id="changethewording" onclick="return toggleNewModifyFAQ()">change the wording</a>
of your petition once people have started to sign up to it.</p>

<div id="modifyfaq">
<h3><?=_("Why can't I modify my petition after I've made it?")?></h3>

<p><?=_("People who sign up to a petition are signing up to the specific wording of
the petition. If you change the wording, then their signatures would no
longer be valid.")?></p>

</div>

<p style="text-align: right;">
<input type="submit" name="tostepmain" value="&lt;&lt; <?=_('Change petition text') ?>">
<br><input type="submit" name="tostepyou" value="&lt;&lt; <?=_('Change my contact details') ?>">
</p>

<?
    print '<p>' . _('When you\'re happy with your petition, <strong>click "Create"</strong> to confirm that you wish www.number10.gov.uk to display the petition at the top of this page in your name, and that you agree to the terms and conditions below.');
?>
<p style="text-align: right;">
<input type="submit" name="tocreate" value="<?=_('Create') ?> &gt;&gt;&gt;">
</p>

<h3>Terms and Conditions</h3>
<p>Terms and Conditions will go here...</p>

</form>
<?
}

# Someone has submitted a new petition
function petition_create($P, $data) {
    /* Guard against double-insertion. */
    db_query('lock table petition in share mode');
        /* Can't just use SELECT ... FOR UPDATE since that wouldn't prevent an
         * insert on the table. */
    if (is_null(db_getOne('select id from petition where ref = ?', $data['ref']))) {
        $data['id'] = db_getOne("select nextval('petition_id_seq')");

        db_query("
                insert into petition (
                    id, title, content,
                    deadline, rawdeadline,
                    person_id, name, ref, 
		    organisation, address,
		    postcode, telephone, org_url,
                    creationtime, 
                    status, laststatuschange
                ) values (
                    ?, ?, ?,
                    ?, ?,
                    ?, ?, ?, 
		    ?, ?,
		    ?, ?, ?,
                    ms_current_timestamp(), 
                    'draft', ms_current_timestamp()
                )", array(
                    $data['id'], $data['title'], $data['content'],
                    $data['deadline'], $data['rawdeadline'],
                    $P->id(), $data['name'], $data['ref'],
		    $data['organisation'], $data['address'],
		    $data['postcode'], $data['telephone'], $data['org_url']
                ));
    }

    // Send email to admin
    $data['url'] = OPTION_ADMIN_URL . '?page=pet&o=draft';
    pet_send_email_template(OPTION_CONTACT_EMAIL, 'admin-new-petition', $data);

    $p = new Petition($data['ref']); // Reselect full data set from DB
    $p->log_event("User created draft petition", null);
    db_commit();
    global $page_title;
    $page_title = _('Petition Created');
    $url = htmlspecialchars(OPTION_BASE_URL . "/" . urlencode($p->data['ref']));
?>
    <p class="noprint loudmessage">Thank you for creating your petition.</p>
    <p class="noprint loudmessage" align="center">It has been entered on our
    system and will now go to the Number 10 team for approval.</p>
<?  
}

?>

