<?  
// fns.php:
// General functions for Petiitons
//
// Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: fns.php,v 1.6 2006-09-28 11:55:22 matthew Exp $

require_once "../../phplib/evel.php";
require_once '../../phplib/utility.php';

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
function pet_send_message($petition_id, $sender, $recips, $circumstance, $template) {
    if(!is_int($petition_id))
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
                emailtemplatename
            ) values (
                ?,
                ?,
                coalesce((select max(circumstance_count)
                            from message where petition_id = ?
                                and circumstance = ?), 0) + 1,
                ?,
                ?, ?, ?, 'f', -- XXX
                ?
            )",
            $petition_id,
            $circumstance,
                $petition_id,
                $circumstance,
            $sender == MSG_ADMIN ? 'number10' : 'creator',
                ($recips & MSG_ADMIN) ? 't' : 'f',
                ($recips & MSG_CREATOR) ? 't' : 'f',
                ($recips & MSG_SIGNERS) ? 't' : 'f',
            $template);
}

// $to can be one recipient address in a string, or an array of addresses
function pet_send_email_template($to, $template_name, $values, $headers = array()) {
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

    $template = file_get_contents("../templates/emails/$template_name");
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
        $spec['From'] = '"10 Downing Street" <' . OPTION_CONTACT_EMAIL . ">";
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


