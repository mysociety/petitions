<?php
/*
 * petition.php:
 * Code to display a petition, get information about it etc.
 * 
 * Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org; WWW: http://www.mysociety.org
 *
 * $Id: petition.php,v 1.27 2006-10-14 10:51:30 matthew Exp $
 * 
 */

// Textual content
$petition_prefix = 'We the undersigned petition the Prime Minister to';

/* Must keep this synchronised with constraint in schema. */
$global_categories = array(
    1 => 'Party political material',
    2 => 'False or defamatory statements',
    4 => 'Information protected by an injunction or court order',
    8 => 'Material which is commercially sensitive, confidential or which may cause personal distress or loss',
    16 => 'Names of individual officials of public bodies, unless part of the senior management of those organisations',
    32 => 'Names of family members of officials of public bodies, or elected representatives',
    64 => 'Names of individuals, or information where they may be identified, in relation to criminal accusations',
    128 => 'Offensive language',
    256 => 'Isn\'t clear what the petition is asking signers to endorse',
    512 => 'Doesn\'t actually ask for an action',
    1024 => 'Attempting to market a product irrelevent to the role and office of the PM',
    2048 => 'Identical to an existing petition',
);

function prettify_categories($categories, $newlines) {
    global $global_categories;
    $out = array();
    foreach ($global_categories as $k => $v)
        if ($categories & $k) $out[] = $v;
    if ($newlines)
        return '    ' . join("\n    ", $out) . "\n";
    return join(', ', $out);
}

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
                               (SELECT count(*) FROM signer WHERE showname and 
                                    signer.petition_id = petition.id) AS signers,
                               email,
                               content, detail
                           FROM petition";
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
            if (isset($ref['pet_content']))
                    $this->data['content'] = $ref['pet_content'];
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

        if (array_key_exists('rejection_second_categories', $this->data)
            && $this->data['rejection_second_categories']) {
            $this->data['categories'] = prettify_categories($this->data['rejection_second_categories'], true);
            $this->data['reason'] = $this->data['rejection_second_reason'];
        } elseif (array_key_exists('rejection_first_categories', $this->data)
            && $this->data['rejection_first_categories']) {
            $this->data['categories'] = prettify_categories($this->data['rejection_first_categories'], true);
            $this->data['reason'] = $this->data['rejection_first_reason'];
        }
    }

    // Basic data
    function ref() { return $this->data['ref']; }
    function status() { return $this->data['status']; }
    function id() { return intval($this->data['id']); }
    function open() { return $this->data['open']; } // not gone past the deadline date
    function finished() { return $this->data['finished']; } // can take no more signers, for whatever reason
    function signers() { return $this->data['signers']; } 

    function creator_email() { return $this->data['email']; }
    function creator_name() { return $this->data['name']; }

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
    function h_content() {
        return htmlspecialchars($this->data['content']);
    }

    // Basic data access for HTML display
    function detail() { return $this->data['detail']; }
    function h_detail() {
        $detail = htmlspecialchars($this->data['detail']);
        $detail = str_replace("\r", '', $detail);
        $detail = preg_replace('#\n\n+#', '</p> <p>', $detail);
        return "<p>$detail</p>";
    }
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
            <?=_('Deadline to sign up by:') ?> <strong><?=$this->h_pretty_deadline()?></strong></p>
            <? if ($this->h_detail()) {
            print '<p><strong>More details:</strong></p>' . $this->h_detail();
            }

            if ($this->signers() >= 0) { ?>
            <p><i>
            <?printf(ngettext('%s person has signed the petition', '%s people have signed the petition', $this->signers()), prettify($this->signers()));?>
            </i></p>
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

    function rss_entry() {
        return array(
          'title' => "'" . 
                htmlspecialchars($this->sentence(array('firstperson'=>true, 'html'=>false)))
                . "' -- " . $this->h_name(),
          'description' => htmlspecialchars(trim_characters($this->detail(), 0, 80)),
          'link' => $this->url_main(),
          'creationtime' => $this->data['creationtime']
        );
    }

    # Only needs to look at rejection_second_categories as things
    # rejected once are not displayed anywhere
    function rejected_show_nothing() {
        # Defamatory, injunction, confidential, names
        $bitfield = 2 | 4 | 8 | 16 | 32 | 64;
        if ($this->data['rejection_second_categories'] & $bitfield)
            return true;
        return false;
    }
}

