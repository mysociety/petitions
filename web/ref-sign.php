<?
// ref-sign.php:
// Petition signing page
//
// Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: ref-sign.php,v 1.1 2006-06-27 22:40:29 matthew Exp $

require_once '../phplib/pet.php';
require_once '../phplib/petition.php';
require_once '../../phplib/person.php';
#require_once '../../phplib/utility.php';
require_once '../../phplib/importparams.php';

# page_check_ref(get_http_var('ref'));
$p = new Petition(get_http_var('ref'));

$title = _('Signature addition');
ob_start();
$errors = do_sign();
$body = ob_get_contents();
ob_end_clean();
page_header($title, array('ref'=>$p->ref()));
print $body;
if (is_array($errors)) {
    print '<div id="errors"><ul><li>';
    print join ('</li><li>', $errors);
    print '</li></ul></div>';
    $p->h_display_box();
    petition_sign_box();
}
page_footer();

function do_sign() {
    global $q_email, $q_name, $q_showname, $q_ref;
    $errors = importparams(
                array(array('name',true),       '//',        '', null),
                array('email',      'importparams_validate_email'),
                array('ref',        '/^[a-z0-9-]+$/i',  ''),
                array('showname',   '//',               '', 0)
            );
    if ($q_name==_('<Enter your name>')) {
        $q_name = null;
    }
    if (!$q_name) {
        $q_showname = false;
        $q_name = null;
    }

    if (!$q_ref)
        /* I don't think this error is likely to occur with real users, (see
         * mysociety-developers email of 20060209) but the error message which
         * occurs when ref is null is confusing, so better to trap it
         * explicitly. */
        err(_("No petition reference was specified"));

    $petition = new Petition($q_ref);

    if (!is_null($errors))
        return $errors;

    /* Get the user to log in. */
    $r = $petition->data;
    $P = pet_send_logging_in_email('signature-confirm', $r, $q_email, $q_name);
    $R = petition_is_valid_to_sign($petition->id(), $P->email());

    if ($R == 'ok') {
        /* All OK, sign petition. */
        db_query('insert into signer (petition_id, name, person_id, showname, signtime) values (?, ?, ?, ?, ms_current_timestamp())', array($petition->id(), ($P->has_name() ? $P->name() : null), $P->id(), $q_showname ? 't' : 'f'));
        db_commit();
        print '<p class="noprint loudmessage" align="center">' . _('Thanks for signing up to this petition!') . '</p>';
    } else if ($R == 'signed') {
        /* Either has already signer, or is creator. */
        print '<p><strong>';
        if ($P->id() == $petition->creator_id()) {
            print _('You cannot sign your own petition!');
        } else {
            print _('You\'ve already signed this petition!');
        }
        print '</strong></p>';
    } else {
        /* Something else has gone wrong. */
        print '<p><strong>' . _("Sorry &mdash; it wasn't possible to sign that petition.") . ' '
                . htmlspecialchars(petition_strerror($R))
                . ".</strong></p>";
    }
}

?>
