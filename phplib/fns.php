<?  
// fns.php:
// General functions for Petiitons
//
// Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: fns.php,v 1.1 2006-06-20 14:14:52 francis Exp $

require_once "../../phplib/evel.php";
require_once '../../phplib/person.php';
require_once '../../phplib/utility.php';

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


