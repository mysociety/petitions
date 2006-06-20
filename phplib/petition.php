<?php
/*
 * petition.php:
 * Code to display a petition, get information about it etc.
 * 
 * Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org; WWW: http://www.mysociety.org
 *
 * $Id: petition.php,v 1.1 2006-06-20 14:14:52 francis Exp $
 * 
 */

// Textual content
$petition_prefix = 'We the undersigned petition the Prime Minister to';

class Petition {
    // Associative array of parameters about the pledge, taken from database
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
        #$this->data['open'] = ($this->data['open'] == 't');
        if (!array_key_exists('signers', $this->data)) $this->data['signers'] = 0;
    }

    // Basic data
    function ref() { return $this->data['ref']; }
    function id() { return $this->data['id']; }
    function open() { return $this->data['open']; } // not gone past the deadline date
    function signers() { return $this->data['signers']; } 

    function creator() { return new person($this->data['person_id']); }
    function creator_email() { return $this->data['email']; }
    function creator_name() { return $this->data['name']; }
    function creator_id() { return $this->data['person_id']; }

    function creationtime() { return $this->data['creationtime']; }
    function creationdate() { return substr($this->data['creationtime'], 0, 10); }

    // Parameters:
    // html - return HTML, rather than plain text
    function sentence($params) { 
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

    function h_display_box() {
?>
        <div class="petition_box">
            <p style="margin-top: 0">
            <?= $this->sentence(array('html'=>true)) ?>
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
    #function url_main() { return pet_domain_url() . $this->h_ref; }


}


