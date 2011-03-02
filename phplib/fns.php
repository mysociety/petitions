<?  
// fns.php:
// General functions for Petiitons
//
// Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: fns.php,v 1.20 2010-04-27 13:31:20 matthew Exp $

require_once "../commonlib/phplib/evel.php";
require_once '../commonlib/phplib/utility.php';

define('MSG_ADMIN', 1);
define('MSG_CREATOR', 2);
define('MSG_SIGNERS', 4);
define('MSG_ALL', MSG_ADMIN | MSG_CREATOR | MSG_SIGNERS);

/* pet_send_message ID SENDER RECIPIENTS CIRCUMSTANCE TEMPLATE
 * Send a message to the RECIPIENTS in respect of the petition with the given
 * ID, constructing it from the given email TEMPLATE. RECIPIENTS should be the
 * bitwise combination of one or more of MSG_ADMIN, MSG_CREATOR and
 * MSG_SIGNERS. The message will appear to come from SENDER, which must be
 * MSG_ADMIN or MSG_CREATOR; CIRCUMSTANCE indicates the reason for its
 * sending. */
function pet_send_message($petition_id, $sender, $recips, $circumstance, $template, $vars = null) {
    if(!ctype_digit((string)$petition_id))
        err("ID must be integer in pet_send_message");

    if ($sender != MSG_ADMIN && $sender != MSG_CREATOR)
        err("SENDER must be MSG_ADMIN or MSG_CREATOR");

    if ($recips == 0)
        err("RECIPIENTS must be nonzero in pet_send_message");
    elseif ($recips & ~MSG_ALL)
        err("Unknown bits present in RECIPIENTS $recips");

    db_query("
            insert into message (
                petition_id,
                circumstance,
                circumstance_count,
                fromaddress,
                sendtoadmin, sendtocreator, sendtosigners, sendtolatesigners,
                emailtemplatename, emailtemplatevars
            ) values (
                ?,
                ?,
                coalesce((select max(circumstance_count)
                            from message where petition_id = ?
                                and circumstance = ?), 0) + 1,
                ?,
                ?, ?, ?, 'f', -- XXX
                ?, ?
            )",
            $petition_id,
            $circumstance,
                $petition_id,
                $circumstance,
            $sender == MSG_ADMIN ? 'admin' : 'creator',
                ($recips & MSG_ADMIN) ? 't' : 'f',
                ($recips & MSG_CREATOR) ? 't' : 'f',
                ($recips & MSG_SIGNERS) ? 't' : 'f',
            $template, serialize($vars));
}

// $to can be one recipient address in a string, or an array of addresses
function pet_send_email_template($to, $template_name, $values, $headers = array()) {
    if (array_key_exists('creationtime', $values))
        $values['creationtime'] = prettify(substr($values['creationtime'], 0, 19), false);    
    if (array_key_exists('deadline', $values))
        $values['pretty_date'] = prettify($values['deadline'], false);
    if (array_key_exists('name', $values)) {
        $values['creator_name'] = $values['name'];
        $values['name'] = null;
    }
    if (array_key_exists('email', $values)) {
        $values['creator_email'] = $values['email'];
        $values['email'] = null;
    }
    if (array_key_exists('signers', $values))
        $values['signers_ordinal'] = ordinal($values['signers']);
        
    $values['signature'] = _("-- the ePetitions team");

    $site_name = null;
    if (array_key_exists('body_ref', $values)) {
        $site_name = $values['body_ref'];
    }

    global $site_group;
    if ($site_name && file_exists("../templates/emails/$site_name/$template_name")) {
        $template = file_get_contents("../templates/emails/$site_name/$template_name");
    } elseif (file_exists("../templates/emails/$site_group/$template_name")) {
        $template = file_get_contents("../templates/emails/$site_group/$template_name");
    } else {
        $template = file_get_contents("../templates/emails/$template_name");
    }
    $template = _($template);

    $spec = array(
        '_template_' => $template,
        '_parameters_' => $values
    );
    $spec = array_merge($spec, $headers);
    return pet_send_email_internal($to, $spec);
}

// $to can be one recipient address in a string, or an array of addresses
function pet_send_email($to, $subject, $message, $headers = array()) {
    $spec = array(
        '_unwrapped_body_' => $message,
        'Subject' => $subject,
    );
    $spec = array_merge($spec, $headers);
    return pet_send_email_internal($to, $spec);
}

function pet_send_email_internal($to, $spec) {
    // Construct parameters

    // Add standard header
    if (!array_key_exists("From", $spec)) {
        $spec['From'] = '"' . OPTION_CONTACT_NAME . '" <' . OPTION_CONTACT_EMAIL . ">";
    }

    // With one recipient, put in header.  Otherwise default to undisclosed recip.
    if (!is_array($to)) {
        $spec['To'] = $to;
        $to = array($to);
    }

    // Send the message
    $result = evel_send($spec, $to);
    $error = evel_get_error($result);
    if ($error) 
        error_log("pet_send_email_internal: " . $error);
    $success = $error ? FALSE : TRUE;

    return $success;
}

/* This is for Number 10 petitions only at present */
function pet_search_form($params=array()) {
    $front = isset($params['front']) && $params['front'];
    if (OPTION_SITE_NAME == 'number10') {
?>
<form<? if ($front) print ' id="search_front"'; ?> name="pet_search" method="get" action="http://search.petitions.number10.gov.uk/kbroker/number10/petitions/search.lsim">
<input type="hidden" name="ha" value="1157" />
<input type="hidden" name="sc" value="number10" />
<p><label for="q">Search petitions:</label>
<input type="text" name="qt" id="q" size="11" maxlength="1000" value="" />&nbsp;<input type="submit" value="Go" /></p>
</form>
<?
    } elseif (OPTION_SITE_NAME == 'sbdc' || OPTION_SITE_NAME == 'sbdc1') {
?>
<form<?
        if (isset($params['float'])) print ' style="float:right"';
        if ($front) print ' id="search_front"';
?> name="pet_search" method="get" action="/search">
<p><label for="q">Search petitions:</label>
<input type="text" name="q" id="q" size="11" maxlength="1000" value="" />&nbsp;<input type="submit" value="Go" /></p>
</form>
<?
    }
}

function pet_create_response_email($type, $url, $subject, $body) {
    $descriptorspec = array(
        0 => array('pipe', 'r'), 
        1 => array('pipe', 'w'),
    );
    $result = proc_open("./create-preview $type $url", $descriptorspec, $pipes);

    fwrite($pipes[0], "$subject\n\n$body");
    fclose($pipes[0]);
    $out = '';
    while (!feof($pipes[1])) {
        $out .= fread($pipes[1], 8192);
    }
    fclose($pipes[1]);
    proc_close($result);
    return $out;
}
