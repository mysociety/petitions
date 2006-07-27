<?
// new.php:
// New petitions
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: new.php,v 1.19 2006-07-27 22:09:58 matthew Exp $

require_once '../phplib/pet.php';
require_once '../phplib/fns.php';
require_once '../phplib/petition.php';
require_once '../../phplib/datetime.php';

$page_title = 'Create a new petition';
ob_start();
if (get_http_var('tostepmain')
   || get_http_var('tostepyou') || get_http_var('tosteppreview')
   || get_http_var('tocreate') ) {
    petition_form_submitted();
} else {
    petition_form_main();
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

    petition_create($data);
}

/* various HTML utilities for these forms */
function startform() {
    print '<form accept-charset="utf-8" method="post" action="/new">';
}

function nextprevbuttons($prev, $prevdesc, $next, $nextdesc) {
    print '<p style="text-align: right">';
    if (!is_null($next)) {
        if (is_null($nextdesc)) $nextdesc = _('Next');
        printf('<input type="submit" name="%s" value="%s" />',
                htmlspecialchars($next), htmlspecialchars($nextdesc));
        if (!is_null($prev)) print "<br />";
    }
    if (!is_null($prev)) {
        if (is_null($prevdesc)) $prevdesc = _('Prev');
        printf('<input type="submit" name="%s" value="%s" />',
                htmlspecialchars($prev), htmlspecialchars($prevdesc));
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
        print '<ul class="errors"><li>'
                . join('</li><li>',
                    array_map('htmlspecialchars', array_values($errors)))
                . '</li></ul>';
}

function textarea($name, $val, $cols, $rows, $errors) {
    printf('<textarea name="%s" cols="%d" rows="%d"%s>%s</textarea>',
            htmlspecialchars($name),
            $cols, $rows,
            array_key_exists($name, $errors) ? ' class="error"' : '',
            htmlspecialchars(is_null($val) ? '' : $val));
}

function textfield($name, $val, $size, $errors) {
    printf('<input onfocus="fadein(this)" onblur="fadeout(this)" '
            . 'type="text" name="%s" id="%s" size="%d" value="%s"%s />',
            htmlspecialchars($name), htmlspecialchars($name),
            $size,
            htmlspecialchars(is_null($val) ? '' : $val),
            array_key_exists($name, $errors) ? ' class="error"' : '');
}

function petition_form_main($data = array(), $errors = array()) {
    global $pet_time, $petition_prefix;

    print 'There are 5 stages to the petition process:';
    print petition_breadcrumbs(0);
    print '<a href="/steps">More detailed description of these steps</a>';

    foreach (array('content', 'title', 'rawdeadline', 'ref') as $x)
        if (!array_key_exists($x, $data)) $data[$x] = '';

    errorlist($errors);
    startform();
    ?>
<h2><span dir="ltr"><?=_('New petition &#8211; Part 1 of 3 &#8211; Your petition') ?></span></h2>

<p><strong><?=$petition_prefix ?>...</strong> <br />
    <?
    textarea('content', $data['content'], 40, 7, $errors);
    ?>
<p><?=_('Title of your petition:') ?> <br />
    <?
    textfield('title', $data['title'], 60, $errors);
    ?>
</p>
<p>Requested duration:
    <?
    textfield('rawdeadline', $data['rawdeadline'], 15, $errors);
    ?> <small>(e.g. "2 months")</small>
</p>

<p><?=_('Choose a short name for your petition (6 to 16 letters):') ?>
    <?
    textfield('ref', $data['ref'], 16, $errors);
    ?>
<br><small><?=htmlspecialchars(_('This gives your petition an easy web address. e.g. http://petitions.number10.gov.uk/badgers')) ?></small>
</p>

<?
    nextprevbuttons(null, null, 'tostepyou', null);
    endform($data);
}

function petition_form_you($data = array(), $errors = array()) {
    errorlist($errors);
    startform();
    ?>
<h2><span dir="ltr"><?=_('New petition &#8211; Part 2 of 3 &#8211; About you') ?></span></h2>
<div><?

    $fields = array(
            'name'  =>          _('Your name'),
            'organisation' =>   _('Organisation'),
            'address' =>        _('Address'),
            'postcode' =>       _('Postcode'),
            'telephone' =>      _('Telephone number'),
            'org_url' =>        _('URL of campaign/organisation'),
            'email' =>          _('Your email'),
            'email2' =>         _('Confirm email')
        );

    foreach ($fields as $name => $desc) {
        printf('<strong>%s:</strong>', htmlspecialchars($desc));

        if (!array_key_exists($name, $data))
            $data[$name] = '';
        
        if ($name == 'address')
            textarea($name, $data[$name], 30, 4, $errors);
        else {
            $size = 20;
            if ($name == 'postcode')
                $size = 10;
            else if ($name == 'telephone')
                $size = 15;
            textfield($name, $data[$name], $size, $errors);
        }

        if ($name == 'org_url')
            print "(optional)";

        if ($name == 'email2')
            print "<small>"
                    . htmlspecialchars(_('(we need your email so we can get in touch with you when your petition completes, and so on)'))
                    . "</small>";
        
        print "<br /><br />";
    }

    nextprevbuttons('tostepmain', null, 'tosteppreview', null);
    endform($data);
}


function step_main_error_check($data) {
    global $pet_today;

    $errors = array();

    $disallowed_refs = array('contact', 'translate', 'posters', 'graphs');
    if (!$data['ref'])
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

    $dupe = db_getOne('SELECT id FROM petition WHERE ref ILIKE ?', array($data['ref']));
    if ($dupe)
        $errors['ref'] = _('That short name is already taken!');
    if (!$data['title'])
        $errors['title'] = _('Please enter a title');
    elseif (strlen($data['title']) > 100)
        $errors['title'] = _('Please make the title a bit shorter (at most 100 characters).');
    if (!$data['content'])
        $errors['content'] = _('Please enter the text of your petition');

    $pet_today_arr = explode('-', $pet_today);
    $deadline_limit_years = 1; # in years
    $deadline_limit = date('Y-m-d', mktime(12, 0, 0, $pet_today_arr[1], $pet_today_arr[2], $pet_today_arr[0] + $deadline_limit_years));
    if (!$data['rawdeadline'] || !$data['deadline'])
        $errors['rawdeadline'] = _('Please enter a deadline');
    if ($data['deadline'] < $pet_today)
        $errors['rawdeadline'] = _('The deadline must be in the future');
    if ($data['deadline_details']['error'])
        $errors['rawdeadline'] = _('Please enter a valid date for the deadline');
    if (!$data['rawdeadline'] || !$data['deadline']) $errors['rawdeadline'] = _('Please enter a duration');
    if ($data['deadline'] < $pet_today) $errors['rawdeadline'] = _('The duration must be positive');
    if ($data['deadline_details']['error']) $errors['rawdeadline'] = _("Sorry, we did not recognise that duration. Please try again");
    if ($deadline_limit < $data['deadline'])
        $errors['rawdeadline'] = sprintf(_('Please change your duration so it is less than %d year.'), $deadline_limit_years);

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
    errorlist($errors);
?>
<h1><span dir="ltr"><?=_('New petition &#8211; Part 3 of 3')?></span></h1>
<p>Your petition, with short name <em><?=$data['ref'] ?></em>, will look like this:</p>
<?
    $partial_petition = new Petition($data);
    $partial_petition->h_display_box();

    startform();
    ?>
<p>Now please read through your petition, above, and check the details thoroughly.
<strong>Read carefully</strong> - we can't let you
<a href="/faq#editpetition" id="changethewording" onclick="return toggleNewModifyFAQ()">change the wording</a>
of your petition once people have started to sign up to it.</p>

<div id="modifyfaq">
<h3><?=_("Why can't I modify my petition after I've made it?")?></h3>

<p><?=_("People who sign up to a petition are signing up to the specific wording of
the petition. If you change the wording, then their signatures would no
longer be valid.")?></p>

</div>

<p style="text-align: right;">
<input type="submit" name="tostepmain" value="Change petition text">
<br><input type="submit" name="tostepyou" value="Change my contact details">
</p>

<p>When you're happy with your petition, <strong>click "Create"</strong> to
confirm that you wish www.number10.gov.uk to display the petition at the top
of this page in your name, and that you agree to the terms and conditions below.
<br />If you have any special requests for the Number 10 web team, please include them
here:<br />
<textarea name="comments" rows="7" cols="40"><? if (isset($data['comments'])) print htmlspecialchars($data['comments']) ?></textarea>
</p>

<p style="text-align: right;">
<input type="submit" name="tocreate" value="Create">
</p>

<h3>Terms and Conditions</h3>
<p>Terms and Conditions will go here...</p>

<?
    endform($data);
}

# Someone has submitted a new petition
function petition_create($data) {
    global $pet_time;

    if (is_null(db_getOne('select id from petition where ref = ?', $data['ref']))) {
        $data['id'] = db_getOne("select nextval('global_seq')");

        /* Guard against double-insertion. */
        db_query('lock table petition in share mode');
            /* Can't just use SELECT ... FOR UPDATE since that wouldn't prevent an
             * insert on the table. */

        # Recalculate deadline, as email confirmation might have been on a different day
        $data['deadline_details'] = datetime_parse_local_date($data['rawdeadline'], $pet_time, 'en', 'GB');
        $data['deadline'] = $data['deadline_details']['iso'];
        db_query("
                insert into petition (
                    id, title, content,
                    deadline, rawdeadline,
                    email, name, ref, 
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
                    'unconfirmed', ms_current_timestamp()
                )", array(
                    $data['id'], $data['title'], $data['content'],
                    $data['deadline'], $data['rawdeadline'],
                    $data['email'], $data['name'], $data['ref'],
		    $data['organisation'], $data['address'],
		    $data['postcode'], $data['telephone'], $data['org_url']
                ));
        db_commit();
    }

    global $page_title;
    $page_title = _('Now check your email');
?>
    <p class="noprint loudmessage">We have sent you an email to confirm
    that we've received your petition details. In order for us to approve
    your petition, we need you to open this email and click on an activation
    link, which will send your petition details to our team for approval.</p>
<?  
}

/* fyr_breadcrumbs NUMBER
 * Numbered "breadcrumbs" trail for current user; NUMBER is the (1-based)
 * number of the step to hilight. */
function petition_breadcrumbs($num) {
    $steps = array(
                'Create your petition',
                'Submit your petition',
                'Petition approval',
                'Petition live',
                'Petition close'
    );
    /* Ideally we'd like the numbers to appear as a result of this being a
     * list, but that's beyond CSS's tiny capabilities, so put them in
     * explicitly. That means that two numbers will appear in non-CSS
     * browsers. */
    $str = '<ol id="breadcrumbs">';
    for ($i = 0; $i < sizeof($steps); ++$i) {
        if ($i == $num - 1)
            $str .= "<li class=\"hilight\">";
        else
            $str .= "<li>";
        $str .= ($i + 1) . ". " . htmlspecialchars($steps[$i]) . "</li>";
    }
    $str .= "</ol>";
    return $str;
}

?>
