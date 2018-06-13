<?php
/*
 * petition.php:
 * Code to display a petition, get information about it etc.
 * 
 * Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org; WWW: http://www.mysociety.org
 *
 * $Id: petition.php,v 1.57 2010-04-23 19:41:58 matthew Exp $
 * 
 */

require_once 'cobrand.php';

// Textual content
if (OPTION_SITE_TYPE == 'one') {
    if (cobrand_we_the_undersigned_use_commas()){
        $petition_prefix = 'We, the undersigned,';
    } else {
        $petition_prefix = 'We the undersigned';
    }
    $petition_prefix .= ' petition ' . OPTION_SITE_PETITIONED . ' to';
} else {
    $petition_prefix = 'We the undersigned petition ';
}

/* Must keep this synchronised with constraint in schema. */
$remit = 'Outside the remit or powers of ' . OPTION_SITE_PETITIONED;
$global_rejection_categories = array(
    1 => 'Party political material',
    2 => 'Potentially libellous, false, or defamatory statements',
    4 => 'Information which may be protected by an injunction or court order',
    8 => 'Material which is potentially confidential, commercially sensitive, or which may cause personal distress or loss',
    16 => 'The names of individual officials of public bodies, unless they are part of the senior management of those organisations',
    32 => 'The names of family members of elected representatives or officials of public bodies',
    64 => 'The names of individuals, or information where they may be identified, in relation to criminal accusations',
    128 => 'Language which is offensive, intemperate, or provocative',
    256 => 'Wording that needs to be amended, or is impossible to understand',
    512 => 'Statements that don\'t actually request any action',
    1024 => 'Commercial endorsement, promotion of any product, service or publication, or statements that amount to adverts',
    2048 => 'Duplicate - this is similar to and/or overlaps with an existing petition or petitions',
    4096 => $remit,
    8192 => 'False or incomplete name or address information',
    16384 => 'Issues for which an e-petition is not the appropriate channel',
    32768 => 'Intended to be humorous, or has no point about government policy',
    65536 => 'Contains links to websites',
    131072 => 'Currently being administered via another process', # for Bassetlaw council
    
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

function stats_change($key, $a, $cat, $body_ref) {
    if (!db_do("update stats set value = value::integer $a where key = '$key'"))
        db_query("insert into stats (whencounted, key, value) values (ms_current_timestamp(), '$key', '1')");
    if (!db_do("update stats set value = value::integer $a where key = '${key}_$cat'"))
        db_query("insert into stats (whencounted, key, value) values (ms_current_timestamp(), '${key}_$cat', 1)");
    if ($body_ref) {
        if (!db_do("update stats set value = value::integer $a where key = '${key}_${body_ref}'"))
            db_query("insert into stats (whencounted, key, value) values (ms_current_timestamp(), '${key}_${body_ref}', '1')");
        if (!db_do("update stats set value = value::integer $a where key = '${key}_${body_ref}_$cat'"))
            db_query("insert into stats (whencounted, key, value) values (ms_current_timestamp(), '${key}_${body_ref}_$cat', 1)");
    }
}

class Petition {
    // Associative array of parameters about the petition, taken from database
    var $data;

    // Construct from either:
    // - string, the short name of a petition
    // - integer, the internal id from the petition table
    // - array, a dictionary of data about the petition
    function __construct($ref) {
        global $pet_today;
        $main_query_part = 'SELECT petition.*, ';
        if (OPTION_SITE_TYPE == 'multiple') {
            $main_query_part .= 'body.name as body_name, body.ref as body_ref, ';
        }
        $main_query_part .= "'$pet_today' <= petition.deadline AS open,
                               cached_signers+coalesce(offline_signers,0) as signers,
                               email,
                               content, detail
                           FROM petition";
        if (OPTION_SITE_TYPE == 'multiple') {
            $main_query_part .= ', body WHERE body_id = body.id AND';
        } else {
            $main_query_part .= ' WHERE';
        }

        if (gettype($ref) == "integer" or (gettype($ref) == "string" and preg_match('/^[1-9]\d*$/', $ref))) {
            $q = db_query("$main_query_part petition.id = ?", array($ref));
            if (!db_num_rows($q))
                err(_('Petition short name not known'));
            $this->data = db_fetch_array($q);
        } elseif (gettype($ref) == "string") {
            $q = db_query("$main_query_part lower(ref) = ?", array(strtolower($ref)));
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

        /* If we haven't fetched from the database, we only have a ref */
        if (OPTION_SITE_TYPE == 'multiple' && !array_key_exists('body_name', $this->data)) {
            $q = db_query('SELECT name, ref FROM body WHERE id=?', array($this->data['body']));
            if (!db_num_rows($q))
                err(_('Petition short name not known'));
            $row = db_fetch_array($q);
            $this->data['body_name'] = $row['name'];
            $this->data['body_ref'] = $row['ref'];
        }
        $this->data['petitioned'] = OPTION_SITE_TYPE == 'one' ? OPTION_SITE_PETITIONED : $this->data['body_name'];

        $this->data['sentence'] = $this->sentence();
        $this->data['h_sentence'] = $this->sentence(array('html'=>true));

        if (cobrand_display_category()){
            $this->data['category_id'] = $this->data['category'];
            $this->data['category'] = cobrand_category($this->data['category'], $this->body_ref());
        } else {
            $this->data['category_id'] = 0;
            $this->data['category'] = 0; # force no-category
        }

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

    function body_ref() { return OPTION_SITE_TYPE=='multiple' ? $this->data['body_ref'] : ''; }
    function body_name() { return $this->data['body_name']; }

    function category_id() { return $this->data['category_id']; }

    // Parameters:
    // html - return HTML, rather than plain text
    function sentence($params = array()) { 
        global $petition_prefix;
        $sentence = $petition_prefix;
        if (OPTION_SITE_TYPE == 'multiple') {
            $sentence .= $this->body_name() . ' to';
        }
        $sentence .= ' ' . $this->data['content'];
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
            $content = preg_replace('/\S{60}/', '$0 ', $content);
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
            <p class="banner">Submitted by <?=$this->h_name() ?>
            &ndash; Deadline to sign up by: <strong><?=$this->h_pretty_deadline()?></strong></p>
            <?  if (cobrand_display_category()){ ?>
                <p><strong>Category:</strong> <?=$this->data['category'] ?></p>
            <? } ?>
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
    function domain() {
        if (OPTION_SITE_DOMAINS) {
            $domain = cobrand_custom_domain($this->body_ref());
        } else {
            $domain = OPTION_BASE_URL;
        }
        return $domain;
    }

    function url_main($in_creation=false) {
        $url = $this->domain() . '/';
        if ($this->rejected_show_part('ref') || $in_creation)
            $url .= $this->h_ref . '/';
        else
            $url .= 'reject?id=' . $this->id();
        return $url;
    }

    # Used from cron to get name for From: header
    function from_name() {
        if (OPTION_SITE_TYPE=='multiple')
            return $this->body_name();
        return OPTION_CONTACT_NAME;
    }

    # Used from cron to get relevant admin email for this petition
    function admin_email() {
        return cobrand_admin_email($this->body_ref());
    }

    // Write history to log file 
    function log_event($message, $editor='') {
        if (!$editor) $editor = http_auth_user();
        $q = db_query("insert into petition_log (petition_id, whenlogged, message, editor)
            values (?, ms_current_timestamp(), ?, ?)", array($this->id(), $message, $editor));
    }

    function rss_entry() {
        return array(
          'title' => "'..." .  $this->h_content() . "' -- " . $this->h_name(),
          'description' => htmlspecialchars(trim_characters($this->detail(), 0, 80)),
          'link' => $this->url_main(),
          'pubdate' => $this->data['laststatuschange'],
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

    // Change who this petition is for
    function forward($body_ref) {
        $body = db_getRow("SELECT * FROM body WHERE ref=?", $body_ref);
        db_query("UPDATE petition
            SET body_id = ?
            WHERE id=?", $body['id'], $this->id());
        $this->data['body_name'] = $body['name'];
        $this->data['body_ref'] = $body['ref'];
    }

}

