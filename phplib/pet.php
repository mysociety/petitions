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
 * $Id: pet.php,v 1.6 2006-08-10 13:54:57 chris Exp $
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
