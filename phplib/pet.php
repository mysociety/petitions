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
 * $Id: pet.php,v 1.18 2009-12-08 12:21:10 matthew Exp $
 * 
 */

// Load configuration file
require_once "../conf/general";
require_once "../commonlib/phplib/error.php";
require_once "../commonlib/phplib/db.php";
require_once 'page.php';
require_once 'fns.php';

err_set_handler_display('pet_handle_error');

/* Output buffering: PHP's output buffering is broken, because it does not
 * affect headers. However, it's worth using it anyway, because in the common
 * case of outputting an HTML page, it allows us to clear any output and
 * display a clean error page when something goes wrong. Obviously if we're
 * displaying an error, a redirect, an image or anything else this will break
 * horribly.*/
function ob_callback($s) {
    $s = cobrand_html_final_changes($s);
    if ($s) {
        header('Content-Length: ' . strlen($s));
    }
    return $s;
}
ob_start('ob_callback');

$locale_current = ''; # To *not* use English suffixes in dates

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
        page_footer('Error');
    } else {
        /* Nuke any existing page output to display the error message. */
        while (ob_get_level()) {
            ob_end_clean();
        }
        if ($num & E_USER_NOTICE)
            # Assume we've said everything we need to
            $err = "<p><em>$message</em></p>";
        else
            # Message will be in log file, don't display it for cleanliness
            $err = '<p>' . sprintf(_('Please try again later, or <a href="mailto:%s">email us</a> for help resolving the problem.'), htmlspecialchars(OPTION_CONTACT_EMAIL)) . '</p>';
        if ($num & (E_USER_ERROR | E_USER_WARNING)) {
            $err = "<p><em>$message</em></p> $err";
        }
        ob_start(); // since page header writes content length, must be in ob_
        pet_show_error($err);
    }
}

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
        "\n" . $message;
    page_footer('Error');
}
