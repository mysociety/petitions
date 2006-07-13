<?php
/*
 * petition.php:
 * Code to display a petition, get information about it etc.
 * 
 * Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org; WWW: http://www.mysociety.org
 *
 * $Id: petition.php,v 1.5 2006-07-13 11:44:03 matthew Exp $
 * 
 */

// Textual content
$petition_prefix = 'We the undersigned petition the Prime Minister to';

class Petition {
    // Associative array of parameters about the petition, taken from database
    var $data;

    // Construct from either:
    // - string, the short name of a petition
    // - integer, the internal id from the petition table
    // - array, a dictionary of data about the petition
    function Petition($ref) {
        global $pet_today;
        $main_query_part = "SELECT petition.*,
                               '$pet_today' <= petition.deadline AS open,
                               (SELECT count(*) FROM signer WHERE
                                    signer.petition_id = petition.id) AS signers,
                               person.email AS email,
                               content, title
                           FROM petition
                           LEFT JOIN person ON person.id = petition.person_id";
        if (gettype($ref) == "integer" or (gettype($ref) == "string" and preg_match('/^[1-9]\d*$/', $ref))) {
            $q = db_query("$main_query_part WHERE petition.id = ?", array($ref));
            if (!db_num_rows($q))
                err(_('Petition short name not known'));
            $this->data = db_fetch_array($q);
        } elseif (gettype($ref) == "string") {
            $q = db_query("$main_query_part WHERE ref ILIKE ?", array($ref));
            if (!db_num_rows($q)) {
                err(_('We couldn\'t find that petition.  Please check the URL again carefully.'));
            }
            $this->data = db_fetch_array($q);
        } elseif (gettype($ref) == "array") {
            $this->data = $ref;
        } else {
            err("Unknown type '" . gettype($ref) . "' to Petition constructor");
        }
        $this->_calc();
    }

    // Internal function to calculate some values from data
    function _calc() {
        if (!array_key_exists('signers', $this->data)) $this->data['signers'] = -1;
        if (!array_key_exists('open', $this->data)) $this->data['open'] = 't';
        $this->data['open'] = ($this->data['open'] == 't');
        $this->h_ref = htmlspecialchars($this->data['ref']);

        // "Finished" means closed to new signers
        $finished = false;
        if (!$this->open())
            $finished = true;
        $this->data['finished'] = $finished;

        $this->data['sentence'] = $this->sentence();
        $this->data['h_sentence'] = $this->sentence(array('html'=>true));
    }

    // Basic data
    function ref() { return $this->data['ref']; }
    function status() { return $this->data['status']; }
    function id() { return $this->data['id']; }
    function open() { return $this->data['open']; } // not gone past the deadline date
    function finished() { return $this->data['finished']; } // can take no more signers, for whatever reason
    function signers() { return $this->data['signers']; } 

    function creator() { return new person($this->data['person_id']); }
    function creator_email() { return $this->data['email']; }
    function creator_name() { return $this->data['name']; }
    function creator_id() { return $this->data['person_id']; }

    function creationtime() { return $this->data['creationtime']; }
    function creationdate() { return substr($this->data['creationtime'], 0, 10); }

    // Parameters:
    // html - return HTML, rather than plain text
    function sentence($params = array()) { 
        global $petition_prefix;
        $sentence = $petition_prefix . " " . $this->data['content'];
        if (array_key_exists('html', $params)) {
            $sentence = htmlspecialchars($sentence);
        }
        return $sentence;
    }

    // Basic data access for HTML display
    function h_title() { return htmlspecialchars($this->data['title']); }
    function h_name() { return htmlspecialchars($this->data['name']); }
    function h_pretty_deadline() { return prettify(htmlspecialchars($this->data['deadline'])); }

    function h_display_box($params = array()) {
?>
        <div class="petition_box">
            <p style="margin-top: 0">
<?
        if (isset($params['href']))
            print '<a href="' . $params['href'] . '">';
        print $this->sentence(array('html'=>true));
        if (isset($params['href']))
            print '</a>';
?>
            </p> 
            <p align="right">&mdash; <?=$this->h_name() ?></p>
            <p>
            <?=_('Deadline to sign up by:') ?> <strong><?=$this->h_pretty_deadline()?></strong>
            <br>

            <?      if ($this->signers() >= 0) { ?>
            <i>
            <?printf(ngettext('%s person has signed the petition', '%s people have signed the petition', $this->signers()), prettify($this->signers()));?>
            </i>
            <? } ?>

        </div>
<?
    }

    // URLs
    function url_main() { return '/' . $this->h_ref; }

    // Write history to log file 
    function log_event($message, $editor) {
        $q = db_query("insert into petition_log (petition_id, whenlogged, message, editor)
            values (?, ms_current_timestamp(), ?, ?)", array($this->id(), $message, $editor));
    }
}

function petition_sign_box() {
    if (get_http_var('add_signatory'))
        $showname = get_http_var('showname') ? ' checked' : '';
    else
        $showname = ' checked';

    $email = get_http_var('email');
    $name = get_http_var('name', true);

    $P = person_if_signed_on();
    if (!is_null($P)) {
        if (is_null($email) || !$email)
            $email = $P->email();
        if (is_null($name) || !$name)
            $name = $P->name_or_blank();
    }
?>
<form accept-charset="utf-8" action="/<?=htmlspecialchars(get_http_var('ref')) ?>/sign" method="post">
<input type="hidden" name="add_signatory" value="1">
<input type="hidden" name="ref" value="<?=htmlspecialchars(get_http_var('ref')) ?>">
<?  print '<h2>Sign up now</h2>';
    $namebox = '<input onblur="fadeout(this)" onfocus="fadein(this)" size="20" type="text" name="name" id="name" value="' . htmlspecialchars($name) . '">';
    print '<p><strong>';
    printf(_('I, %s, sign up to the petition.'), $namebox);
?>
</strong><br>
</p>
<p>
<small>
<strong><input type="checkbox" name="showname" value="1"<?=$showname ?>>Show my name publically on this petition.</strong>
<br>People searching for your name on the Internet will be able
to find your signature, unless you uncheck this box.</small>
</p> 

<p><strong>Your email</strong>: <input type="text" size="30" name="email" value="<?=htmlspecialchars($email) ?>"><br><small>
(we need this so we can tell you when the petition is completed and let the Government get in touch)</small> </p>

<p><input type="submit" name="submit" value="Sign Petition"></p>
</form>
<? 
}

/* petition_is_valid_to_sign PETITION EMAIL
 * Return whether EMAIL may validly sign PETITION.
 * This function locks rows in petition and signer with select ... for
 * update / lock tables. */
function petition_is_valid_to_sign($petition_id, $email) {
    return db_getOne('select petition_is_valid_to_sign(?, ?)',
                    array($petition_id, $email));
}


