<?php
/*
 * admin-pet.php:
 * Petition admin pages.
 * 
 * Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: admin-pet.php,v 1.112 2007-10-22 09:11:20 matthew Exp $
 * 
 */

require_once "../phplib/pet.php";
require_once "../phplib/petition.php";
require_once "../../phplib/db.php";
require_once "../../phplib/utility.php";
require_once "../../phplib/importparams.php";
require_once '../../phplib/datetime.php';

class ADMIN_PAGE_PET_SUMMARY {
    function ADMIN_PAGE_PET_SUMMARY() {
        $this->id = 'summary';
    }
    function display() {
        global $pet_today;
        petition_admin_search_form();
    }
}

class ADMIN_PAGE_PET_STATS {
    function ADMIN_PAGE_PET_STATS() {
        $this->id = 'stats';
        $this->navname = 'Statistics';
    }
    function display() {
        global $pet_today;

        # Overall
        $statsdate = prettify(substr(db_getOne("SELECT whencounted FROM stats order by id desc limit 1"), 0, 19));
        print <<<EOF
<p>Statistics last updated: $statsdate
EOF;

        # Petitions
        $counts = array(
            'unconfirmed'=>0, 'failedconfirm'=>0, 'sentconfirm'=>0,
            'draft'=>0, 'rejectedonce'=>0, 'resubmitted'=>0,
            'rejected'=>0, 'live'=>0, 'finished'=>0,
            'all_confirmed'=>0, 'all_unconfirmed'=>0
        );
        foreach (array_keys($counts) as $t) {
            $counts[$t] = db_getOne("SELECT value FROM stats WHERE key = 'petitions_$t' order by id desc limit 1");
        }

        print <<<EOF
<h2>Petitions</h2>
<p>$counts[live] live, $counts[finished] finished, $counts[draft] draft, $counts[rejectedonce] rejected once, $counts[resubmitted] resubmitted, $counts[rejected] rejected again = <strong>$counts[all_confirmed]</strong> total with confirmed emails<br>
With unconfirmed emails: $counts[unconfirmed] not sent, $counts[failedconfirm] failed send, $counts[sentconfirm] sent
= <strong>$counts[all_unconfirmed]</strong> total with unconfirmed emails
<p><img src="pet-live-creation.png" alt="Graph of petition status by creation date">
EOF;

        # Signatures
        $signatures_confirmed = db_getOne("SELECT value FROM stats WHERE key = 'signatures_confirmed' order by id desc limit 1");
        $signatures_unconfirmed = db_getOne("SELECT value FROM stats WHERE key = 'signatures_sent' order by id desc limit 1");
        $signers = db_getOne("SELECT value FROM stats WHERE key = 'signatures_confirmed_unique' order by id desc limit 1");
        print <<<EOF
<h2>Signatures</h2>
<p>$signatures_confirmed confirmed signatures ($signers unique emails), $signatures_unconfirmed unconfirmed
<p><img src="pet-live-signups.png" alt="Graph of signers across whole site">
EOF;

        # Responses 
        $responses = db_getOne("select count(*) from message where circumstance = 'government-response'");
        $unique_responses = db_getOne("select count(distinct petition_id) from message where circumstance = 'government-response'");
        print <<<EOF
<h2>Government responses</h2>
<p>$responses responses sent, to $unique_responses unique petitions
EOF;

        # Rejection reasons - TODO (probably don't try storing in stats table
        # as can do quickly enough in real time, and data doesn't really fit
        # stats table well)
        #$rejection_table = "";
        #$q = db_query("select key, value from stats where whencounted = (select max(whencounted) from stats where key like 'rejection_%') and key like 'rejection_%' order by id desc");
        #while ($r = db_fetch_array($q)) {
        #    $cats = str_replace('rejection_', '', $r[0]);
        #    $counts = $r[1];
        #    $rejection_table .= "<tr><td>".prettify_categories($cats, false)."</td><td>$counts</td></tr>"; 
        #}
        #print <<<EOF
#<h2>Petition rejection reasons</h2>
#<p><table><tr><th>Categories</th><th>Count</th></tr>$rejection_table</table>
#EOF;

    }
}

class ADMIN_PAGE_PET_SEARCH {
    function ADMIN_PAGE_PET_SEARCH() {
        $this->id = 'petsearch';
        $this->navname = 'Search petitions';
    }

    function search_petitions($q, $search) {
        $out = '';
        while ($r = db_fetch_array($q)) {
            $out .= "<tr><td>$r[email]</td><td>".htmlspecialchars($r['name'])."</td><td>$r[ref]</td>";
            $out .= '<td>' . prettify($r['creationtime']) . '</td>';
            $out .= '<td><form name="petition_admin_search" method="post" action="'.$this->self_link.'"><input type="hidden" name="search" value="'.htmlspecialchars($search).'">';
            $out .= '<input type="hidden" name="confirm_petition_id" value="' . $r['id'] . '"><input type="submit" name="confirm" value="Confirm petition, move to \'draft\'">';
            $out .= "</form></td></tr>";
        }
        return $out;
    }

    function search_signers($q, $search) {
        $out = '';
        while ($r = db_fetch_array($q)) {
            $out .= "<tr><td>$r[email]</td><td>$r[name]</td><td><a href=\"".OPTION_BASE_URL."/$r[ref]\">$r[ref]</a></td>";
            $out .= '<td>' . prettify($r['signtime']) . '</td>';
            $out .= '<td><form name="petition_admin_search" method="post" action="'.$this->self_link.'"><input type="hidden" name="search" value="'.htmlspecialchars($search).'">';
            if ($r['emailsent'] == 'confirmed')
                $out .= '<input type="hidden" name="remove_signer_id" value="' . $r['id'] . '"><input type="submit" name="remove" value="Remove signer">';
            elseif ($r['emailsent'] == 'sent')
                $out .= '<input type="hidden" name="confirm_signer_id" value="' . $r['id'] . '"><input type="submit" name="confirm" value="Confirm signer">';
            $out .= "</form></td></tr>";
        }
        return $out;
    }

    function display() {
        petition_admin_perform_actions();
        $search = strtolower(get_http_var('search'));
        petition_admin_navigation(array('search'=>$search));
        $search_pet = "select id, ref, name, email, status, date_trunc('second', creationtime) as creationtime
            from petition where status = 'sentconfirm' ";
        $search_sign = "select signer.id, ref, signer.name, signer.email, emailsent,
                date_trunc('second', signtime) as signtime
            from signer, petition
            where signer.petition_id = petition.id
            and showname = 't' and emailsent in ('sent', 'confirmed') ";
        $out = '';
        if ($search && validate_email($search)) {
            $q = db_query($search_pet . "and lower(email) = ?", array($search));
            $out = $this->search_petitions($q, $search);
            $q = db_query($search_sign . "and lower(signer.email) = ?", array($search));
            $out .= $this->search_signers($q, $search);
        } elseif ($search) {
            $q = db_query($search_pet . "
                and (name ilike '%'||?||'%' or lower(email) ilike '%'||?||'%')
                order by lower(email)", array($search, $search));
            $out = $this->search_petitions($q, $search);
            $q = db_query($search_sign . "
                and (signer.name ilike '%'||?||'%' or lower(signer.email) ilike '%'||?||'%')
                order by lower(signer.email)", array($search, $search));
            $out = $this->search_signers($q, $search);
        }
        if ($out) {
            print "<table cellpadding=3 border=0><tr><th>Email</th><th>Name</th>
                <th>Petition</th><th>Creation time</th><th>Actions</th></tr>";
            print $out;
            print "</table>";
        }
        else print '<p><em>No matches</em></p>';
    }
}

function petition_admin_perform_actions() {
    $petition_id = null;
    if (get_http_var('remove_signer_id')) {
        $signer_id = get_http_var('remove_signer_id');
        if (ctype_digit($signer_id)) {
            $petition_id = db_getOne("SELECT petition_id FROM signer WHERE id = $signer_id");
            db_query('UPDATE signer set showname = false where id = ?', $signer_id);
            db_query('update petition set cached_signers = cached_signers - 1,
                lastupdate = ms_current_timestamp() where id = ?', $petition_id);
            $p = new Petition($petition_id);
            $p->log_event('Admin hid signer ' . $signer_id, http_auth_user());
            db_commit();
            print '<p><em>That signer has been removed.</em></p>';
        }
    }
    if (get_http_var('confirm_signer_id')) {
        $signer_id = get_http_var('confirm_signer_id');
        if (ctype_digit($signer_id)) {
            $petition_id = db_getOne("SELECT petition_id FROM signer WHERE id = $signer_id");
            db_query("UPDATE signer set emailsent = 'confirmed' where id = ?", $signer_id);
            db_query('update petition set cached_signers = cached_signers + 1,
                lastupdate = ms_current_timestamp() where id = ?', $petition_id);
            $p = new Petition($petition_id);
            $p->log_event('Admin confirmed signer ' . $signer_id, http_auth_user());
            db_commit();
            print '<p><em>That signer has been confirmed.</em></p>';
        }
    }
    if (get_http_var('confirm_petition_id')) {
        $petition_id = get_http_var('confirm_petition_id');
        if (ctype_digit($petition_id)) {
            db_query("UPDATE petition set status = 'draft' where id = ?", $petition_id);
            $p = new Petition($petition_id);
            $p->log_event('Admin confirmed petition ' . $petition_id, http_auth_user());
            db_commit();
            print '<p><em>That petition has been confirmed.</em></p>';
        }
    }

    # Category updates
    if (isset($_POST['category']) && is_array($_POST['category'])) {
        foreach ($_POST['category'] as $pid => $cat) {
            db_query('update petition set category = ? where id = ?', $cat, $pid);
        }
        db_commit();
    }

    return $petition_id;
}

function petition_admin_navigation($array = array()) {
    $status = isset($array['status']) ? $array['status'] : '';
    # $found = isset($array['found']) ? $array['found'] : 0;
    $search = isset($array['search']) ? $array['search'] : '';
    print "<p><strong>Show &ndash;</strong> ";
    if ($status == 'draft') {
        print 'Draft / '; # (' . $found . ') / ';
        print '<a href="?page=pet&amp;o=live">Live</a> / ';
        print '<a href="?page=pet&amp;o=finished">Finished</a> / ';
        print '<a href="?page=pet&amp;o=rejected">Rejected</a>';
    } elseif ($status == 'live') {
        print '<a href="?page=pet&amp;o=draft">Draft</a> / ';
        print 'Live / '; # (' . $found . ') / ';
        print '<a href="?page=pet&amp;o=finished">Finished</a> / ';
        print '<a href="?page=pet&amp;o=rejected">Rejected</a>';
    } elseif ($status == 'finished') {
        print '<a href="?page=pet&amp;o=draft">Draft</a> / ';
        print '<a href="?page=pet&amp;o=live">Live</a> / ';
        print 'Finished / '; # (' . $found . ') / ';
        print '<a href="?page=pet&amp;o=rejected">Rejected</a>';
    } elseif ($status == 'rejected') {
        print '<a href="?page=pet&amp;o=draft">Draft</a> / ';
        print '<a href="?page=pet&amp;o=live">Live</a> / ';
        print '<a href="?page=pet&amp;o=finished">Finished</a> / ';
        print 'Rejected / '; # (' . $found . ')';
    } else {
        print '<a href="?page=pet&amp;o=draft">Draft</a> / ';
        print '<a href="?page=pet&amp;o=live">Live</a> / ';
        print '<a href="?page=pet&amp;o=finished">Finished</a> / ';
        print '<a href="?page=pet&amp;o=rejected">Rejected</a>';
    }
    print " <strong>&ndash; petitions</strong></p>";
    petition_admin_search_form($search);
    print '<hr>';
}

function petition_admin_search_form($search='') { ?>
<form name="petition_admin_search" method="get" action="./">
<input type="hidden" name="page" value="petsearch">
Search for name/email: <input type="text" name="search" value="<?=htmlspecialchars($search) ?>" size="40">
<input type="submit" value="Search">
</form>
<?
}

class ADMIN_PAGE_PET_MAIN {
    function ADMIN_PAGE_PET_MAIN () {
        $this->id = "pet";
        $this->navname = "Petitions and signers";
    }

    function petition_header($sort, $status) {
        print '<table border="1" cellpadding="3" cellspacing="0"><tr>';
        $cols = array(
            'z'=>'Signers<br>(in last day)',
            'r'=>'Ref', 
            'a'=>'Title', 
            's'=>'Signers', 
            'd'=>'Deadline', 
            'e'=>'Creator', 
            'c'=>'Last Status Change', 
        );
        foreach ($cols as $s => $col) {
            print '<th>';
            if ($sort != $s && ($s != 'z' || $status == 'live'))
                print '<a href="'.$this->self_link.'&amp;s='.$s.'&amp;o='.$status.'">';
            print $col;
            if ($sort != $s && ($s != 'z' || $status == 'live'))
                print '</a>';
            print '</th>';
        }
        if (!$this->cat_change && ($status == 'finished' || $status == 'draft'))
            print '<th>Actions</th>';
        print '</tr>';
        print "\n";
    }

    function list_all_petitions() {
        global $pet_today, $global_petition_categories;
        $sort = get_http_var('s');
        if (!$sort || preg_match('/[^radecsz]/', $sort)) $sort = 'c';
        $order = '';
        if ($sort=='r') $order = 'ref';
        elseif ($sort=='a') $order = 'content';
        elseif ($sort=='d') $order = 'deadline desc';
        elseif ($sort=='e') $order = 'name';
        elseif ($sort=='c') $order = 'petition.laststatuschange';
        elseif ($sort=='s') $order = 'signers desc';
        elseif ($sort=='z') $order = 'surge desc';

        $page = get_http_var('p'); if (!ctype_digit($page) || $page<0) $page = 0;
        $page_limit = 100;
        $offset = $page * $page_limit;

        $this->cat_change = get_http_var('cats') ? true : false;
        $categories = '';
        foreach ($global_petition_categories as $id => $cat) {
            $categories .= '<option value="' . $id . '">' . $cat;
        }

        $status = get_http_var('o');
        if (!$status || !preg_match('#^(draft|live|rejected|finished)$#', $status)) $status = 'draft';

        $status_query = "status = '$status'";
        if ($status == 'draft')
            $status_query = "(status = 'draft' or status = 'resubmitted')";
        elseif ($status == 'rejected')
            $status_query = "(status = 'rejected' or status = 'rejectedonce')";
        
        $surge = '';
        if ($status == 'live')
            $surge = "(SELECT count(*) FROM signer WHERE showname = 't' and petition_id=petition.id AND signtime > ms_current_timestamp() - interval '1 day' and emailsent = 'confirmed') AS surge,";

        $q = db_query("
            SELECT petition.*,
                date_trunc('second',laststatuschange) AS laststatuschange,
                (ms_current_timestamp() - interval '7 days' > laststatuschange) AS late, 
                cached_signers AS signers,
                $surge
                message.id AS message_id
            FROM petition
            LEFT JOIN message ON petition.id = message.petition_id AND circumstance = 'government-response'
            WHERE $status_query
            " .  ($order ? ' ORDER BY ' . $order : '')
            . ' OFFSET ' . $offset . ' LIMIT ' . $page_limit);
        $found = array();
        while ($r = db_fetch_array($q)) {
            $row = "";

            $row .= '<td>' . (isset($r['surge']) ? $r['surge'] : '') . '</td>';
            $row .= '<td>';
            if ($r['status']=='live' || $r['status']=='finished' || $r['status']=='rejected')
                $row .= '<a href="' . OPTION_BASE_URL . '/' . $r['ref'] . '">';
            $row .= $r['ref'];
            if ($r['status']=='live' || $r['status']=='finished' || $r['status']=='rejected')
                $row .= '</a>';
            $row .= '<br><a href="'.$this->self_link.'&amp;petition='.$r['ref'].'">admin</a>';
            $row .= '</td>';
            $row .= '<td>' . trim_characters(htmlspecialchars($r['content']),0,100);
            if ($this->cat_change) {
                $disp_cat = preg_replace('#value="'.$r['category'].'"#', '$0 selected', $categories);
                $row .= '<br><select name="category[' . $r['id'] . ']">' . $disp_cat . '</select>';
            }
            $row .= '</td>';
            $row .= '<td>' . htmlspecialchars($r['signers']) . '</td>';
            $row .= '<td>' . prettify($r['deadline']) . '</td>';
            $row .= '<td><a href="mailto:'.htmlspecialchars($r['email']).'">'.
                htmlspecialchars($r['name']).'</a></td>';
            $row .= '<td>'.$r['laststatuschange'].'</td>';
            $late = false;
            if ($status == 'draft' && $r['late'] == 't') $late = true;
            if ($status == 'rejected') {
                $row .= '<td>';
                if ($r['status'] == 'rejectedonce') {
                    $row .= 'Rejected once';
                } elseif ($r['status'] == 'rejected') {
                    $row .= '<form name="petition_admin_go_respond" method="post" action="'.$this->self_link.'"><input type="hidden" name="petition_id" value="' . $r['id'] . '">';
                    $row .= 'Rejected twice';
                    $row .= ' <input type="submit" name="remove" value="Remove petition">';
                    $row .= '</form>';
                }
                $row .= '</td>';
            } elseif (!$this->cat_change && $status == 'draft') {
                $row .= '<td><form name="petition_admin_approve" method="post" action="'.$this->self_link.'"><input type="hidden" name="petition_id" value="' . $r['id'] .
                    '"><input type="submit" name="reject" value="Reject"></form>';
                if ($r['status'] == 'resubmitted') {
                    $row .= ' resubmitted';
                }
                $row .= '</td>';
            } elseif (!$this->cat_change && ($status == 'finished' || $status == 'live')) {
                $row .= '<td>';
                if ($r['message_id']) 
                    $row .= 'Response sent';
                else {
                    $row .= '<form name="petition_admin_go_respond" method="post" action="'.$this->self_link.'"><input type="hidden" name="petition_id" value="' . $r['id'] . 
                        '"><input type="submit" name="respond" value="Write response">';
                    if ($status == 'live') {
                        $row .= ' <input type="submit" name="redraft" value="Move back to draft">';
                    }
                    $row .= ' <input type="submit" name="remove" value="Remove petition">';
                    $row .= '</form>';
                }
                $row .= '</td>';
            }
            $found[] = array($late, $row);
        }
/*
        if ($sort=='o') {
            function sort_by_percent($a, $b) {
                global $open;
                preg_match('#<td>([\d\.,]+)%</td>#', $open[$a], $m); $aa = str_replace(',','',$m[1]);
                preg_match('#<td>([\d\.,]+)%</td>#', $open[$b], $m); $bb = str_replace(',','',$m[1]);
                if ($aa==$bb) return 0;
                return ($aa<$bb) ? 1 : -1;
            }
            uksort($open, 'sort_by_percent');
        }
*/
        petition_admin_navigation(array('status'=>$status, 'found'=>count($found)));
        if ($this->cat_change) { ?>
<form method="post" action="<?=$this->self_link ?>">
<input type="hidden" name="cats" value="1"><input type="hidden" name="o" value="<?=$status ?>">
<p><input type="submit" value="Update all categories">
<a href="<?=$this->self_link ?>;o=<?=$status ?>">Back to normal screen</a></p>
<?      } else {
            print '<p><a href="'.$this->self_link.';o='.$status.';cats=1">Update categories</a></p>';
        }
        print '<p><a href="'.$this->self_link.';s='.$sort.';o='.$status.';p='.($page-1).'">Previous '.$page_limit.'</a>';
        print ' | <a href="'.$this->self_link.';s='.$sort.';o='.$status.';p='.($page+1).'">Next '.$page_limit.'</a></p>';
        $this->petition_header($sort, $status);
        $a = 0;
        foreach ($found as $row) {
            print '<tr';
            $class = array();
            if ($row[0]) $class[] = 'l';
            if ($a++%2==0) $class[] = 'v';
            if ($class) print ' class="' . join(' ', $class) . '"';
            print ">$row[1]</tr>\n";
        }
        print '</table>';
        if ($this->cat_change) {
            print '</form>';
        }
        print '<p>';
    }

    function show_one_petition($petition) {
        petition_admin_navigation();

        $sort = get_http_var('s');
        if (!$sort || preg_match('/[^etc]/', $sort)) $sort = 't';
        $list_limit = get_http_var('l');
        if ($list_limit) {
            $list_limit = intval($list_limit);
            if ($list_limit == -1)
                $list_limit = null;
        }
        else
            $list_limit = 100;

        $q = db_query("SELECT petition.*,
                date_trunc('second', laststatuschange) AS laststatuschange,
                date_trunc('second', creationtime) AS creationtime,
                (SELECT count(*) FROM signer WHERE showname = 't' and petition_id=petition.id AND
                    emailsent in ('sent', 'confirmed')) AS signers
            FROM petition
            WHERE ref ILIKE ?", $petition);
        $pdata = db_fetch_array($q);
        if (!$pdata) {
            print sprintf("Petition '%s' not found", htmlspecialchars($petition));
            return;
        }
        $petition_obj = new Petition($pdata);
#        $petition_obj->render_box(array('showdetails' => true));

        print "<h2>Petition &lsquo;<a href=\"" . OPTION_BASE_URL . '/' .
            $pdata['ref'] . '/">' . $pdata['ref'] . '</a>&rsquo;';
        print "</h2>";

        print "<ul><li>Set by: <b>" . htmlspecialchars($pdata['name']) . " &lt;" .  htmlspecialchars($pdata['email']) . "&gt;</b>, " . $pdata['address'] . ', ' . $pdata['postcode'] . ', ' . $pdata['telephone'];
        print '<li>Organisation: ';
        print $pdata['organisation'] ? htmlspecialchars($pdata['organisation']) : 'None given';
        if ($pdata['org_url'])
            print ', <a href="' . htmlspecialchars($pdata['org_url']) . '">' . htmlspecialchars($pdata['org_url']) . '</a>';
        print "<li>Created: " . prettify($pdata['creationtime']);
        print "<li>Last status change: " . prettify($pdata['laststatuschange']);
        print '<li>Current status: <b>' . htmlspecialchars($pdata['status']) . '</b>';
        if ($pdata['status'] == 'rejectedonce' || $pdata['status'] == 'rejected') {
            # Why rejected?
            if ($pdata['status'] == 'rejectedonce') {
                $category = $pdata['rejection_first_categories'];
                $reason = $pdata['rejection_first_reason'];
            } else {
                $category = $pdata['rejection_second_categories'];
                $reason = $pdata['rejection_second_reason'];
            }
            $cats_pretty = prettify_categories($category, false);
            print '<ul><li><form method="post" action="' . $this->self_link
                . '"><input type="hidden" name="petition_id" value="' . $pdata['id']
                . '"><input type="hidden" name="change_criteria" value="1">For being in the following categories: '
                . $cats_pretty . ' &mdash; <input type="submit" value="Change"></form>';
            print '<li>Extra reason provided by admin: ' . $reason . '</li></ul>';
        }
        print '<li><form name="petition_admin_change_deadline" method="post" action="' . $this->self_link . '">
<input type="hidden" name="deadline_change" value="1">
<input type="hidden" name="petition_id" value="' . $pdata['id'] . '">
Deadline: ';
        if ($pdata['status'] == 'live')
            print '<input type="text" name="deadline" value="';
        else
            print '<b>';
        print trim(prettify($pdata['deadline']));
        if ($pdata['status'] == 'live')
            print '">';
        else
            print '</b>';
        if ($pdata['status'] == 'live')
            print ' <input type="submit" value="Change">';
        print ' (user entered "' . htmlspecialchars($pdata['rawdeadline']) . '")
</form>';
        print '<li>Title: <b>' . htmlspecialchars($pdata['content']) . '</b>';
        print '<li>Details of petition: ';
        print $pdata['detail'] ? htmlspecialchars($pdata['detail']) : 'None';
        print '</ul>';

        if ($pdata['status'] == 'draft' || $pdata['status'] == 'resubmitted') {
            print '
<form name="petition_admin_approve" method="post" action="'.$this->self_link.'">
<p align="center">
<input type="hidden" name="petition_id" value="' . $pdata['id'] . '">
<input type="submit" name="approve" value="Approve">
<input type="submit" name="reject" value="Reject">
</p>
</form>';
        } elseif ($pdata['status'] == 'finished' || $pdata['status'] == 'live') {
            print '<form name="petition_admin_go_respond" method="post" action="'
                . $this->self_link . '"><input type="hidden" name="petition_id" value="' . $pdata['id'] . 
                '"><input type="submit" name="respond" value="Write response">';
            if ($pdata['status'] == 'live')
                print ' <input type="submit" name="redraft" value="Move back to draft">';
            print ' <input type="submit" name="remove" value="Remove petition">';
            print '</form>';
        } elseif ($pdata['status'] == 'rejected') {
            print '<form name="petition_admin_go_respond" method="post" action="'
                . $this->self_link . '"><input type="hidden" name="petition_id" value="' . $pdata['id'] . 
                '"><input type="submit" name="remove" value="Remove petition">';
            print '</form>';
        }

        // Messages
        print '<h2>Messages</h2>';
        $q = db_query('select * from message 
                where petition_id = ? order by whencreated', $pdata['id']);

        $n = 0;
        while ($r = db_fetch_array($q)) {
            if ($n++)
                print '<hr>';

            $got_creator_count = db_getOne('select count(*) from message_creator_recipient where message_id = ?', $r['id']);
            $got_signer_count = db_getOne('select count(*) from message_signer_recipient where message_id = ?', $r['id']);

            $whom = array();
            if ($r['sendtocreator'] == 't') { $whom[] = 'creator'; }
            if ($r['sendtosigners'] == 't') { $whom[] = 'signers'; }
            if ($r['sendtolatesigners'] == 't') { $whom[] = 'late signers'; }

            print "<p>";
            print "<strong>". $r['circumstance'] . ' ' . $r['circumstance_count'] . '</strong>';
            print " created on ". prettify(substr($r['whencreated'], 0, 19));
            print " to be sent from <strong>" . $r['fromaddress'] . "</strong> to <strong>";
            print join(", ", $whom) . "</strong>";
            print "<br>has been queued for ";
            print "<strong>$got_creator_count creators</strong>";
            print " and <strong>$got_signer_count signers</strong>";
            if ($r['emailtemplatename'])
                print "<br><strong>email template:</strong> " . $r['emailtemplatename'];
            if ($r['emailsubject'])
                print "<br><strong>email subject:</strong> " . htmlspecialchars($r['emailsubject']);
            if ($r['emailbody']) {
                print '<br><strong>email body:</strong>
                <div class="message">.'.
                nl2br(ms_make_clickable(htmlspecialchars($r['emailbody']), array('contract'=>true)))
                ."</div>";
            }

        }
        if ($n == 0) {
            print "No messages yet.";
        }

        // Admin actions
        print '<h2>Administrator events</h2>';
        $q = db_query('select * from petition_log 
                where petition_id = ? order by order_id', $pdata['id']);

        print '<table border="1" cellpadding="3" cellspacing="0">';
        $n = 0;
        print "<tr><th>Date/time</th><th>Event</th><th>Administrator</th></tr>\n";
        while ($r = db_fetch_array($q)) {
            print "<tr>";
            $n++;

            print "<td>". prettify(substr($r['whenlogged'], 0, 19)) . "</td>";
            print "<td>". $r['message'] . "</td>";
            print "<td>". ($r['editor'] ? $r['editor'] : "unknown"). "</td>";
            
            print "</tr>\n";
        }
        if ($n == 0) {
            print "<tr><td colspan=\"3\">No events yet.</td></tr>";
        }
        print "</table>";

        if ($pdata['status'] != 'draft' && $pdata['status'] != 'resubmitted') {
            // Signers
            print "<h2>Signers (".$pdata['signers'].")</h2>";
            $query = "SELECT signer.name as signname, signer.email as signemail,
                         date_trunc('second',signtime) AS signtime,
                         signer.id AS signid, emailsent
                       FROM signer
                       WHERE showname = 't' AND petition_id=? AND emailsent in ('sent', 'confirmed')";
            if ($sort=='t') $query .= ' ORDER BY signtime DESC';
            else $query .= ' ORDER BY signname DESC';
            if ($list_limit) 
                $query .= " LIMIT $list_limit";
            $q = db_query($query, $pdata['id']);
            $out = array();
            $c = 0;
            while ($r = db_fetch_array($q)) {
                $c++;
                $r = array_map('htmlspecialchars', $r);
                $e = array();
                if ($r['signname'])
                    array_push($e, $r['signname']);
                if ($r['signemail'])
                    array_push($e, $r['signemail']);
                $e = join("<br>", $e);
                $out[$e] = '<td>'.$e.'</td>';
                $out[$e] .= '<td>'.prettify($r['signtime']).'</td>';

                $out[$e] .= '<td>';
                $out[$e] .= '<form name="removesignerform'.$c.'" method="post" action="'.$this->self_link.'">';
                if ($r['emailsent'] == 'confirmed')
                    $out[$e] .= '<input type="hidden" name="remove_signer_id" value="' . $r['signid'] . '"><input type="submit" name="remove_signer" value="Remove signer">';
                elseif ($r['emailsent'] == 'sent')
                    $out[$e] .= '<input type="hidden" name="confirm_signer_id" value="' . $r['signid'] . '"><input type="submit" name="confirm_signer" value="Confirm signer">';
                $out[$e] .= '</form></td>';
            }
            if ($sort == 'e') {
                function sort_by_domain($a, $b) {
                    $aa = stristr($a, '@');
                    $bb = stristr($b, '@');
                    if ($aa==$bb) return 0;
                    return ($aa>$bb) ? 1 : -1;
                }
                uksort($out, 'sort_by_domain');
            }
            if (count($out)) {
                print '<table border="1" cellpadding="3" cellspacing="0"><tr>';
                $cols = array('e'=>'Signer', 't'=>'Time');
                foreach ($cols as $s => $col) {
                    print '<th>';
                    if ($sort != $s) print '<a href="'.$this->self_link.'&amp;petition='.$petition.'&amp;s='.$s.'">';
                    print $col;
                    if ($sort != $s) print '</a>';
                    print '</th>';
                }
                print '<th>Action</th>';
                print '</tr>';
                $a = 0;
                foreach ($out as $row) {
                    print '<tr'.($a++%2==0?' class="v"':'').'>';
                    print $row;
                    print '</tr>';
                }
                print '</table>';
                if ($list_limit && $c >= $list_limit) {
                    print "<p>... only $list_limit signers shown, "; 
                    print '<a href="'.$this->self_link.'&amp;petition='.$petition.'&amp;l=-1">show all</a>';
                    print ' (do not press if you are Tom, it will crash your computer :)</p>';
                }
            } else {
                print '<p>Nobody has signed up to this petition.</p>';
            }
        }
    }

    function display_categories($current = 0) {
        global $global_rejection_categories;
        foreach ($global_rejection_categories as $n => $category) {
            print '<br><input type="checkbox" name="rejection_cats[]"';
            if ($current & $n)
                print ' checked';
            print ' value="' . $n;
            print '" id="cat' . $n . '"> <label for="cat' . $n . '">';
            print $category . '</label>';
        }
    }

    function reject_form($id) {
        $p = new Petition($id); ?>
<p>You have chosen to reject the petition '<?=$p->ref() ?>'.</p>
<form method="post" name="rejection_details_form" action="<?=$this->self_link?>"><input type="hidden" name="reject_form_submit" value="1">
<input type="hidden" name="petition_id" value="<?=$id ?>">
<p>Category or categories for rejection: <small>
<?      $this->display_categories(); ?>
</small></p>
<p>Reason for rejection (this will be emailed to the creator and available on the website):
<br><textarea name="reject_reason" rows="10" cols="70"></textarea></p>

<p>Please now select the parts of this petition that <strong>cannot</strong> be shown on the website for legal reasons:</p>
<table>
<?
        $bits = array(
            1 => array('ref', 'petition URL'),
            2 => array('content', 'main sentence'),
            4 => array('detail', 'extra detail'),
            8 => array('name', 'creator\'s name'),
            16 => array('organisation', 'creator\'s organisation'),
            32 => array('org_url', 'organisation\'s URL')
        );
        foreach ($bits as $bit => $arr) {
            list($part,$pretty) = $arr;
            $value = htmlspecialchars($p->data[$part]);
            print <<<EOF
<tr>
<td><input type="checkbox" name="reject_hide[$part]" value="$bit"></td>
<td>The $pretty: $value</td>
</tr>
EOF;
        }
?>
</table>

<p><input type="submit" name="reject_submit" value="Reject petition"></p>

</form>
<?  }

    /* reject_petition ID CATEGORIES REASON
     * Reject the petition with the given ID because it falls foul of the given
     * CATEGORIES (bitwise combination of values); REASON is a text explanation
     * of the rejection. */
    function reject_petition($id, $categories, $reason) {
        $p = new Petition($id);
        $status = $p->status();
        $cats_pretty = prettify_categories($categories, false);
        $hide = get_http_var('reject_hide');
        if (is_array($hide)) $hide = array_sum($hide);
        else $hide = 0;
        if ($status == 'draft') {
            db_getOne("
                    UPDATE petition
                    SET status = 'rejectedonce',
                        rejection_first_categories = ?,
                        rejection_first_reason = ?,
                        rejection_hidden_parts = ?,
                        laststatuschange = ms_current_timestamp(),
                        lastupdate = ms_current_timestamp()
                    WHERE id=?", $categories, $reason, $hide, $id);
            $p->log_event("Admin rejected petition for the first time. Categories: $cats_pretty. Reasons: $reason", http_auth_user());
            $template = 'admin-rejected-once';
            $circumstance = 'rejected-once';
        } elseif ($status == 'resubmitted') {
            db_getOne("
                    UPDATE petition
                    SET status = 'rejected',
                        rejection_second_categories = ?,
                        rejection_second_reason = ?,
                        rejection_hidden_parts = ?,
                        laststatuschange = ms_current_timestamp(),
                        lastupdate = ms_current_timestamp()
                    WHERE id = ?", $categories, $reason, $hide, $id);
            $p->log_event("Admin rejected petition for the second time. Categories: $cats_pretty. Reason: $reason", http_auth_user());
            $template = 'admin-rejected-again';
            $circumstance = 'rejected-again';
        } else {
            $p->log_event("Bad rejection", http_auth_user());
            db_commit();
            err("Should only be able to reject petitions in draft or resubmitted state");
        }
        pet_send_message($id, MSG_ADMIN, MSG_CREATOR, $circumstance, $template);
        db_commit();
        print '<p><em>That petition has been rejected.</em></p>';
    }

    # Admin function to send government response
    function respond($petition_id) {
        global $q_message_id, $q_submit, $q_n, $q_message_subject, $q_message_body, $q_message_links, $q_html_mail;
        global $q_h_message_id, $q_h_message_subject, $q_h_message_body, $q_h_message_links;
        $p = new Petition($petition_id);

        $status = $p->status();
        if ($status != 'finished' && $status != 'live') {
            $p->log_event("Bad response state", http_auth_user());
            db_commit();
            err("Should only be able to respond to petitions in live or finished state");
            return;
        }

        $n = db_getOne("select id from message where petition_id = ? and circumstance = 'government-response' and circumstance_count = 1", $petition_id);
        if (!is_null($n)) {
            print '<p><em>You have already sent two responses to this petition!</em></p>';
            return;
        }

        $email_subject = sprintf("Government response to petition '%s'", $p->ref());
        importparams(
            array('message_id', '/^[1-9]\d*$/',      '',     null),
            array('message_subject', '//', '', $email_subject),
            array('message_body', '//', '', ''),
            array('message_links', '//', '', ''),
            array('n', '/^[0-9]+$/',      '',     0),
            array('submit', '//',      '',     false),
            array('html_mail', '//', '', 0)
        );
        if (is_null($q_message_id)) {
            $q_message_id = $q_h_message_id = db_getOne("select nextval('global_seq')");
            db_commit();
        }

        $errors = array();
        if (strlen($q_message_body) < 50)
            $errors[] = 'Please enter a longer message.';

        $email = $q_message_body;
        if ($q_message_links) {
            $email .= "\n\n\nFurther information\n\n$q_message_links";
        }
        $email = str_replace("\r\n", "\n", $email);

        if ($q_submit && !sizeof($errors)) {
            $p->log_event("Admin responded to petition", http_auth_user());

            /* Got all the data we need. Just drop the announcement into the database
             * and let the send-messages script pass it to the signers. */
            $id = db_getOne('select id from message where id = ? for update', $q_message_id);
            if (is_null($id)) {
                db_query("insert into message
                        (id, petition_id, circumstance, circumstance_count, fromaddress,
                        sendtocreator, sendtosigners, sendtolatesigners, sendtoadmin,
                        emailsubject, emailbody)
                    values (?, ?, 'government-response',
                        coalesce((select max(circumstance_count)
                            from message where petition_id = ?
                                and circumstance = 'government-response'), -1) + 1,
                        ?, true, true, false, true, ?, ?)",
                array($q_message_id, $p->id(), $p->id(),
                    $q_html_mail ? 'number10html' : 'number10',
                    $q_message_subject, $email));
            }
            db_commit();
            $this->respond_success();
        } else {
            if ($q_n > 0) {
                if (sizeof($errors))
                    print '<div id="errors"><ul><li>' . 
                        join('</li><li>' , $errors) . '</li></ul></div>';
                print '<h2>Preview</h2>';
                $out = $this->respond_generate($q_html_mail ? 'html' : 'plain',
                    $p->ref(), "$q_message_subject\n\n$email");
                if ($q_html_mail) {
                    $out = preg_replace('#^.*?<body>#s', '', $out);
                    $out = preg_replace('#</body>.*$#s', '', $out);
                    print '<div style="max-width: 50em;">' . $out . '</div>';
                } else {
                    print "<pre style='margin-left: 50px; padding-left: 5px; border-left: solid 10px #666666;'>$out</pre>";
                }
            }
?>
<p>You are responding to the petition '<?=$p->ref() ?>'.
To do links in an HTML mail, write them as e.g. <kbd>[http://www.pm.gov.uk/ Number 10 homepage]</kbd>.
</p>
<form name="petition_admin_respond" action="<?=$this->self_link?>" accept-charset="utf-8" method="post">
<input type="hidden" name="respond" value="1">
<input type="hidden" name="petition_id" value="<?=$petition_id ?>">
<?          if ($q_h_message_id) { ?>
<input type="hidden" name="message_id" value="<?=$q_h_message_id ?>">
<?          } ?>
<input type="hidden" name="n" value="<?=$q_n+1 ?>">
<p><label for="message_subject">Subject:</label> <input name="message_subject" id="message_subject" size="40" value="<?=$q_h_message_subject ?>"></p>
<p><label for="message_body">Response:</label>
<br><textarea id="message_body" name="message_body" rows="20" cols="72"><?=$q_h_message_body ?></textarea></p>
<p><label for="message_links">Further Information:</label>
<br><textarea id="message_links" name="message_links" rows="10" cols="72"><?=$q_h_message_links ?></textarea></p>
<p><input type="checkbox" name="html_mail" value="1"<?=($q_html_mail?' checked':'')?>> Send as an HTML email?</p>
<input type="submit" name="respond" value="Preview">
<?          if ($q_n > 0 && !sizeof($errors)) { ?>
<input type="submit" name="submit" value="Send">
<?          } ?>
</form>
<hr>
<?
        }
    }

    function respond_success() {
        print '<p><em>Your response has been recorded and will be sent out shortly.</em></p>';
    }

    function respond_generate($pp, $ref, $input) {
        $descriptorspec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
        );
        $pp = proc_open("../bin/create-preview $pp $ref", $descriptorspec, $pipes);
        fwrite($pipes[0], $input);
        fclose($pipes[0]);
        $out = '';
        while (!feof($pipes[1])) {
            $out .= fread($pipes[1], 8192);
        }
        fclose($pipes[1]);
        proc_close($pp);
        return $out;
    }

    # Admin function to change the deadline of a petition, up to the 1 year limit
    function change_deadline($petition_id) {
        global $pet_today;
        $new_deadline = get_http_var('deadline');

        $p = new Petition($petition_id);
        $status = $p->status();
        if ($status != 'live') return;

        $current_deadline = $p->data['deadline'];
        $went_live = $p->data['laststatuschange'];
        $went_live_epoch = strtotime($went_live);
        $new_deadline = datetime_parse_local_date($new_deadline, $went_live_epoch, 'en', 'GB');
        preg_match('#^(\d\d\d\d)-(\d\d)-(\d\d)#', $went_live, $m);
        $deadline_limit_years = 1; # in years
        $deadline_limit = date('Y-m-d', mktime(12, 0, 0, $m[2], $m[3], $m[1] + $deadline_limit_years));

        $error = null;
        if (!$new_deadline)
            $error = 'Please enter a new duration or deadline';
        elseif ($new_deadline['iso'] < $pet_today)
            $error = 'The resultant deadline must be in the future';
        elseif ($deadline_limit < $new_deadline['iso'])
            $error = 'Please change the duration so it is less than 1 year from when the petition was first approved.';

        if ($error) {
            print "<p><em>$error</em></p>";
        } else {
            db_getOne('update petition set deadline=?, lastupdate = ms_current_timestamp()
                where id=?', $new_deadline['iso'], $petition_id);
            $p->log_event("Admin altered deadline of petition from $current_deadline to $new_deadline[iso]", http_auth_user());
            db_commit();
            print '<p><em>Deadline updated</em></p>';
        }
    }

    # Approve a petition. Note the approval might have been a few days later,
    # so take account of that and calculate a new deadline
    function approve($petition_id) {
        $p = new Petition($petition_id);
        $status = $p->status();
        if ($status == 'draft' || $status == 'resubmitted') {
            db_getOne("UPDATE petition
                SET status='live',
                deadline=deadline+(ms_current_date()-date_trunc('day', laststatuschange)),
                rejection_hidden_parts = 0,
                laststatuschange = ms_current_timestamp(), lastupdate = ms_current_timestamp()
                WHERE id=?", $petition_id);
            $p->log_event("Admin approved petition", http_auth_user());
        } else {
            $p->log_event("Bad approval", http_auth_user());
            db_commit();
            err("Should only be able to approve petitions in draft or resubmitted state");
        }
        pet_send_message($petition_id, MSG_ADMIN, MSG_CREATOR, 'approved', 'petition-approved');
        db_commit();
        print '<p><em>Petition approved!</em></p>';
    }

    # Admin function to move live petitions back to draft state
    # Mistaken approval, creator request, or similar
    function redraft($petition_id, $type) {
        $p = new Petition($petition_id);
        $status = $p->status();
        if ($type == 'redraft' && $status != 'live') return;
        if ($type == 'remove' && $status != 'live' && $status != 'finished' && $status != 'rejected') return;

        $reason = get_http_var('reason');
        $errors = array();
        if (!$reason)
            $errors[] = 'Please give a reason!';

        if (get_http_var('submit') && !sizeof($errors)) {
            if ($type == 'remove') {
                $p->log_event("Admin removed petition with reason '$reason'", http_auth_user());
                db_query("update petition set status='sentconfirm', laststatuschange=ms_current_timestamp(),
                    lastupdate=ms_current_timestamp() where id=?", $p->id());
                $message = 'That petition has been removed from the site';
            } elseif ($type == 'redraft') {
                $p->log_event("Admin redrafted petition with reason '$reason'", http_auth_user());
                db_query("update petition set status='draft', laststatuschange=ms_current_timestamp(),
                    lastupdate=ms_current_timestamp() where id=?", $p->id());
                db_query('delete from signer where petition_id=?', $p->id());
                $message = 'That petition has been moved back into the draft state';
            }
            db_commit();
            print "<p><em>$message</em></p>";
        } else {
            if (get_http_var('submit') && sizeof($errors))
                print '<div id="errors"><ul><li>' . 
                    join('</li><li>' , $errors) . '</li></ul></div>';
?>
<form name="petition_admin_redraft" action="<?=$this->self_link?>" accept-charset="utf-8" method="post"
onsubmit="this.submit_button.disabled=true">
<input type="hidden" name="<?=$type?>" value="1">
<input type="hidden" name="submit" value="1">
<input type="hidden" name="petition_id" value="<?=$petition_id ?>">
<?
            if ($type == 'redraft') {
?>
<p>You are moving the <em><?=$p->ref() ?></em> petition back into its draft state.
<br>This will remove the petition from the site, and it can then be
rejected through the admin interface as normal.
<br>All current signatories will be deleted.</p>
<p><label for="reason">Please give the reason for moving this petition back to draft, for audit purposes:</label>
<br><textarea id="reason" name="reason" rows="5" cols="40"></textarea></p>
<input type="submit" name="submit" value="Move petition">
<? } elseif ($type == 'remove') { ?>
<p>You are removing the <em><?=$p->ref() ?></em> petition from the petition site.
<br>This should only be done if the petition creator has asked for it to be
removed.
<br>Otherwise, use the "Move back to draft" button, so that the petition
can be rejected properly.</p>
<p><label for="reason">Please give the reason for removing this petition, for audit purposes. For example, provide a copy of the request email from the petition creator:</label>
<br><textarea id="reason" name="reason" rows="10" cols="40"></textarea></p>
<input type="submit" name="submit_button" value="Remove petition">
<? } ?>
</form>
<hr><hr>
<?
        }
    }

    function change_criteria($petition_id) {
        $p = new Petition($petition_id);
        $status = $p->status();
        if ($status != 'rejected' && $status != 'rejectedonce') {
            $p->log_event('Changing criteria of non-rejected petition', http_auth_user());
            db_commit();
            err("Should only be able to change criteria of rejected petitions");
            return;
        }

        if ($status == 'rejectedonce')
            $column = 'rejection_first_categories';
        else
            $column = 'rejection_second_categories';
        $criteria = $p->data[$column];

        $errors = array();

        $reason = get_http_var('reason');
        if (!$reason) $errors[] = 'Please give a reason!';

        $criteria_new = get_http_var('rejection_cats');
        if (is_array($criteria_new)) $criteria_new = array_sum($criteria_new);
        else $criteria_new = 0;
        if (!$criteria_new) $errors[] = 'Please give some rejection categories';

        if (get_http_var('submit') && !sizeof($errors)) {
            db_getOne("UPDATE petition SET $column=?, lastupdate=ms_current_timestamp()
                where id=?", $criteria_new, $petition_id);
            $p->log_event("Admin changed rejection criteria from $criteria to $criteria_new, reason '$reason'", http_auth_user());
            db_commit();
            print '<p><em>Petition criteria changed</em></p>';
        } else {
            if (get_http_var('submit') && sizeof($errors))
                print '<div id="errors"><ul><li>' . 
                    join('</li><li>' , $errors) . '</li></ul></div>';
?>
<form method="post" name="admin_change_rejection_criteria" action="<?=$this->self_link?>">
<input type="hidden" name="submit" value="1">
<input type="hidden" name="change_criteria" value="1">
<input type="hidden" name="petition_id" value="<?=$petition_id ?>">
<p>Category or categories for rejection: <small>
<?
            if (!get_http_var('submit')) $criteria_new = $criteria;
            $this->display_categories($criteria_new);
?>
</small></p>
<p>Reason for changing criteria:
<br><textarea name="reason" rows="5" cols="40"></textarea></p>

<p><input type="submit" value="Change criteria"></p>

</form>
<?
        }
    }

    function display() {
        db_connect();
        $petition_id = petition_admin_perform_actions();
        if (!$petition_id)
            $petition_id = get_http_var('petition_id') + 0; # id

        if (get_http_var('approve')) {
            $this->approve($petition_id);
        } elseif (get_http_var('reject')) {
            $this->reject_form($petition_id);
        } elseif (get_http_var('reject_form_submit')) {
            $categories = get_http_var('rejection_cats');
            if (is_array($categories)) $categories = array_sum($categories);
            else $categories = 0;
            $reason = get_http_var('reject_reason');
            if ($categories) {
                $this->reject_petition($petition_id, $categories, $reason);
            } else {
                $this->reject_form($petition_id);
            }
        } elseif (get_http_var('respond')) {
            $this->respond($petition_id);
        } elseif (get_http_var('deadline_change')) {
            $this->change_deadline($petition_id);
        } elseif (get_http_var('redraft')) {
            $this->redraft($petition_id, 'redraft');
        } elseif (get_http_var('remove')) {
            $this->redraft($petition_id, 'remove');
        } elseif (get_http_var('change_criteria')) {
            $this->change_criteria($petition_id);
        }

        // Display page
        if ($petition_id) {
            $petition = db_getOne('SELECT ref FROM petition WHERE id = ?', $petition_id);
        } else {
            $petition = get_http_var('petition');
        }

        if ($petition) {
            $this->show_one_petition($petition);
        } else {
            $this->list_all_petitions();
        }
    }
}

