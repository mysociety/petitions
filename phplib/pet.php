<?php
/*
 * pet.php:
 * General purpose functions specific to ePetitions.  This must
 * be included first by all scripts to enable error logging.
 * This is only used by the web page PHP scripts, command line ones 
 * use petcli.php.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org; WWW: http://www.mysociety.org
 *
 * $Id: pet.php,v 1.5 2006-06-28 23:35:56 matthew Exp $
 * 
 */

// Load configuration file
require_once "../conf/general";
// Some early config files - put most config files after language negotiation below
require_once "../../phplib/error.php";
require_once 'page.php';
require_once 'fns.php';

/* Output buffering: PHP's output buffering is broken, because it does not
 * affect headers. However, it's worth using it anyway, because in the common
 * case of outputting an HTML page, it allows us to clear any output and
 * display a clean error page when something goes wrong. Obviously if we're
 * displaying an error, a redirect, an image or anything else this will break
 * horribly.*/
ob_start();

$locale_current = 'en-gb';

/* pet_handle_error NUMBER MESSAGE
 * Display a PHP error message to the user. */
function pet_handle_error($num, $message, $file, $line, $context) {
    if (OPTION_PET_STAGING) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start(); // since page header writes content length, must be in ob_
        page_header(_("Sorry! Something's gone wrong."), array('override'=>true));
        print("<strong>$message</strong> in $file:$line");
        page_footer();
    } else {
        /* Nuke any existing page output to display the error message. */
        while (ob_get_level()) {
            ob_end_clean();
        }
        /* Message will be in log file, don't display it for cleanliness */
        $err = p(_('Please try again later, or XXXemail usXXX for help resolving the problem.'));
        if ($num & E_USER_ERROR) {
            $err = "<p><em>$message</em></p> $err";
        }
        ob_start(); // since page header writes content length, must be in ob_
        pet_show_error($err);
    }
}
err_set_handler_display('pet_handle_error');

/* POST redirects */
stash_check_for_post_redirect();

/* Date which petition application believes it is */
$pet_today = db_getOne('select ms_current_date()');
$pet_timestamp = substr(db_getOne('select ms_current_timestamp()'), 0, 19);
$pet_time = strtotime($pet_timestamp);

/* pet_show_error MESSAGE
 * General purpose error display. */
function pet_show_error($message) {
    header('HTTP/1.0 500 Internal Server Error');
    page_header(_("Sorry! Something's gone wrong."), array('override'=>true));
    print _('<h2>Sorry!  Something\'s gone wrong.</h2>') .
        "\n<p>" . $message . '</p>';
    page_footer();
}

/* User must have an account to do something (create petition, sign petition).
 * Yet again, copying some of phplib/person.php.
 * Must do this properly at some point.
 * This is so they can avoid all the confusing passwordy stuff.
 */
function pet_send_logging_in_email($template, $data, $q_email, $q_name) {
    $P = person_if_signed_on();
    if ($P && $P->email() == $q_email)
        return $P;

    $token = auth_token_store('login', array(
        'email' => $q_email,
        'name' => $q_name,
        'stash' => stash_request(),
        'direct' => 1
    ));
    db_commit();
    $url = OPTION_BASE_URL . "/L/$token";
    $data['url'] = $url;
    $data['user_name'] = $q_name;
    if (is_null($data['user_name']))
        $data['user_name'] = 'Petition signer';
    $data['user_email'] = $q_email;
    pet_send_email_template($q_email, $template, $data);
    page_header("Now check your email!");
?>
<p class="loudmessage">
Now check your email!<br>
We've sent you an email, and you'll need to click the link in it before you can
continue</p>
<p class="loudmessage">
<small>If you use <acronym title="Web based email">Webmail</acronym> or have
"junk mail" filters, you may wish to check your bulk/spam mail folders:
sometimes, our messages are marked that way.</small></p>
<?
    page_footer(array('nonav' => 1));
    exit();
}

