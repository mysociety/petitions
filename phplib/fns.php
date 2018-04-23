<?  
// fns.php:
// General functions for Petiitons

require_once 'libphp-phpmailer/class.phpmailer.php';
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
function pet_send_email_template($to, $template_name, $values, $from) {
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
    ob_start();
    if ($site_name && file_exists("../templates/emails/$site_name/$template_name")) {
        require "../templates/emails/$site_name/$template_name";
    } elseif (file_exists("../templates/emails/$site_group/$template_name")) {
        require "../templates/emails/$site_group/$template_name";
    } else {
        require "../templates/emails/$template_name";
    }
    $body = ob_get_contents();
    ob_end_clean();

    # First line is "Subject: SUBJECT", second line blank
    $lines = explode("\n", $body);
    $subject = substr($lines[0], 9);
    $body = join("\n", array_slice($lines, 2));

    return pet_send_email_internal($to, $subject, $body, $from);
}

function pet_send_email($to, $subject, $message, $from) {
    return pet_send_email_internal($to, $subject, $message, $from);
}

function pet_send_email_internal($to, $subject, $body, $from) {
    $mail = new PHPMailer;
    $mail->CharSet = 'utf-8';
    $mail->setFrom($from['email'], $from['name']);
    $mail->addAddress($to);
    $mail->Subject = $subject;
    if (is_array($body)) {
        $mail->isHTML(true);
        $mail->Body = $body['html'];
        $mail->AltBody = $body['plain'];
    } else {
        $mail->Body = $body;
    }
    $success = $mail->send();
    if (!$success) {
        error_log("pet_send_email_internal failed");
    }

    return $success;
}

/* This is for example SBDC petitions only at present */
function pet_search_form($params=array()) {
    $front = isset($params['front']) && $params['front'];
    if (OPTION_SITE_NAME == 'sbdc' || OPTION_SITE_NAME == 'sbdc1') {
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
    $result = proc_open("../bin/create-preview $type $url", $descriptorspec, $pipes);

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
