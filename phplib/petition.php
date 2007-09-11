<?php
/*
 * petition.php:
 * Code to display a petition, get information about it etc.
 * 
 * Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org; WWW: http://www.mysociety.org
 *
 * $Id: petition.php,v 1.50 2007-09-11 10:52:42 matthew Exp $
 * 
 */

// Textual content
$petition_prefix = 'We the undersigned petition the Prime Minister to';

/* Must keep this synchronised with constraint in schema. */
$global_rejection_categories = array(
    1 => 'Party political material',
    2 => 'Potentially libellous, false, or defamatory statements',
    4 => 'Information which may be protected by an injunction or court order',
    8 => 'Material which is potentially confidential, commercially sensitive, or which may cause personal distress or loss',
    16 => 'The names of individual officials of public bodies, unless they are part of the senior management of those organisations',
    32 => 'The names of family members of elected representatives or officials of public bodies',
    64 => 'The names of individuals, or information where they may be identified, in relation to criminal accusations',
    128 => 'Language which is offensive, intemperate, or provocative',
    256 => 'Wording that is impossible to understand',
    512 => 'Statements that don\'t actually request any action',
    1024 => 'Commercial endorsement, promotion of any product, service or publication, or statements that amount to adverts',
    2048 => 'Duplicate - this is similar to and/or overlaps with an existing petition or petitions',
    4096 => 'Outside the remit or powers of the Prime Minister and Government',
    8192 => 'False name or address information',
    16384 => 'Issues for which an e-petition is not the appropriate channel',
    32768 => 'Intended to be humorous, or has no point about government policy',
    65536 => 'Contains links to websites',
    // XXX also change in perllib/Petitions/Page.pm
);

# Top level categories of the IPSV v2
$global_petition_categories = array(
    0 => 'None',
    692 => 'Business and industry',
    726 => 'Economics and finance',
    439 => 'Education and skills',
    981 => 'Employment, jobs and careers',
    499 => 'Environment',
    760 => 'Government, politics and public administration',
    557 => 'Health, well-being and care',
    460 => 'Housing',
    758 => 'Information and communication',
    911 => 'International affairs and defence',
    616 => 'Leisure and culture',
    642 => 'Life in the community',
    6999 => 'People and organisations',
    564 => 'Public order, justice and rights',
    652 => 'Science, technology and innovation',
    521 => 'Transport and infrastructure'
);

function prettify_categories($categories, $newlines) {
    global $global_rejection_categories;
    $out = array();
    foreach ($global_rejection_categories as $k => $v)
        if ($categories & $k) $out[] = $v;
    if ($newlines)
        return "\n\n   * " . join("\n\n   * ", $out) . "\n\n";
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
                               cached_signers as signers,
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
        global $global_petition_categories;

        if (!array_key_exists('rejection_hidden_parts', $this->data))
            $this->data['rejection_hidden_parts'] = 0;
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

        $this->data['category'] = $global_petition_categories[$this->data['category']];

        if (array_key_exists('rejection_second_categories', $this->data)
            && $this->data['rejection_second_categories']) {
            $this->data['rejection_categories'] = prettify_categories($this->data['rejection_second_categories'], true);
            $this->data['rejection_reason'] = $this->data['rejection_second_reason'];
        } elseif (array_key_exists('rejection_first_categories', $this->data)
            && $this->data['rejection_first_categories']) {
            $this->data['rejection_categories'] = prettify_categories($this->data['rejection_first_categories'], true);
            $this->data['rejection_reason'] = $this->data['rejection_first_reason'];
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
        if (!$this->rejected_show_part('content'))
            $sentence = 'This petition cannot be shown';
        if (array_key_exists('html', $params)) {
            $sentence = htmlspecialchars($sentence);
        }
        return $sentence;
    }
    function h_content($long = false) {
        if (!$this->rejected_show_part('content'))
            return 'Cannot be shown';
        $content = $this->data['content'];
        if ($long)
            $content = preg_replace('/\S{60}/', '$0 ', $text);
        return htmlspecialchars($content);
    }

    // Basic data access for HTML display
    function detail() {
        if ($this->rejected_show_part('detail'))
            return $this->data['detail'];
        return 'More details cannot be shown';
    }

    # This function is only used when creating a petition
    # So don't need to worry about censoring parts
    function h_detail() {
        $detail = htmlspecialchars($this->data['detail']);
        $detail = str_replace("\r", '', $detail);
        $detail = preg_replace('#\n\n+#', '</p> <p>', $detail);
        return "<p>$detail</p>";
    }
    function h_name() {
        if ($this->rejected_show_part('name'))
            return htmlspecialchars($this->data['name']);
        return 'Name cannot be shown';
    }
    function h_pretty_deadline() { return prettify(htmlspecialchars($this->data['deadline'])); }

    # This function is only used when creating a petition
    # So don't need to worry about censoring parts
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
            <p align="center">Submitted by <?=$this->h_name() ?>
            &ndash; Deadline to sign up by: <strong><?=$this->h_pretty_deadline()?></strong></p>
            <p><strong>Category:</strong> <?=$this->data['category'] ?></p>
            <? if ($this->h_detail() != '<p></p>') {
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
    function url_main() {
        $url = OPTION_BASE_URL . '/';
        if ($this->rejected_show_part('ref'))
            $url .= $this->h_ref . '/';
        else
            $url .= 'reject?id=' . $this->id();
        return $url;
    }

    // Write history to log file 
    function log_event($message, $editor) {
        $q = db_query("insert into petition_log (petition_id, whenlogged, message, editor)
            values (?, ms_current_timestamp(), ?, ?)", array($this->id(), $message, $editor));
    }

    function rss_entry() {
        return array(
          'title' => "'..." .  $this->h_content() . "' -- " . $this->h_name(),
          'description' => htmlspecialchars(trim_characters($this->detail(), 0, 80)),
          'link' => $this->url_main(),
          'creationtime' => $this->data['creationtime']
        );
    }

    # Can we show PART ?
    function rejected_show_part($part) {
        $map = array('ref'=>1, 'content'=>2, 'detail'=>4,
            'name'=>8, 'organisation'=>16, 'org_url'=>32);
        if ($this->data['rejection_hidden_parts'] & $map[$part])
            return false;
        return true;
    }
}

