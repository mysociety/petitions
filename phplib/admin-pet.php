<?php
/*
 * admin-pet.php:
 * Petition admin pages.
 * 
 * Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: admin-pet.php,v 1.7 2006-07-27 12:57:15 matthew Exp $
 * 
 */

require_once "../phplib/pet.php";
require_once "../phplib/petition.php";
require_once "../../phplib/db.php";
require_once "../../phplib/utility.php";
require_once "../../phplib/importparams.php";

class ADMIN_PAGE_PET_SUMMARY {
    function ADMIN_PAGE_PET_SUMMARY() {
        $this->id = 'summary';
    }
    function display() {
        global $pet_today;

        $petitions = db_getOne('SELECT COUNT(*) FROM petition');
        $petitions_live = db_getOne("SELECT COUNT(*) FROM petition WHERE status='live'");
        $petitions_draft = db_getOne("SELECT COUNT(*) FROM petition WHERE status='draft'");
        $petitions_closed = db_getOne("SELECT COUNT(*) FROM petition WHERE status='finished'");
        $petitions_rejected = db_getOne("SELECT COUNT(*) FROM petition WHERE status='rejected' or status='rejectedonce'");
        $petitions_resubmitted = db_getOne("select count(*) from petition where status='resubmitted'");
        $signatures = db_getOne('SELECT COUNT(*) FROM signer WHERE showname');
        $signers = db_getOne('SELECT COUNT(DISTINCT person_id) FROM signer WHERE showname');
        
        print "Total petitions in system: $petitions<br>$petitions_live live, $petitions_draft draft, $petitions_closed finished, $petitions_rejected rejected, $petitions_resubmitted resubmitted<br>$signatures signatures, $signers signers";
        petition_admin_search_form();
    }
}

class ADMIN_PAGE_PET_SEARCH {
    function ADMIN_PAGE_PET_SEARCH() {
        $this->id = 'petsearch';
        $this->navname = 'Search petitions';
    }

    function display() {
        // Perform actions
        if (get_http_var('remove_signer_id')) {
            $signer_id = get_http_var('remove_signer_id');
            if (ctype_digit($signer_id)) {
                petition_admin_remove_signer($signer_id);
            }
        }

        $search = get_http_var('search');
        petition_admin_navigation(array('search'=>$search));
        if ($search) {
            $q = db_query("select signer.id, ref, signer.name, email
                from signer, person, petition
                where signer.person_id = person.id and signer.petition_id = petition.id
                and showname
                and (person.name like '%'||?||'%' or person.email like '%'||?||'%')
                order by email
            ", array($search, $search));
            $out = '';
            while ($r = db_fetch_array($q)) {
                $out .= "<tr><td>$r[email]</td><td>$r[name]</td><td>$r[ref]</td>";
                $out .= '<td><form method="post" action="'.$this->self_link.'"><input type="hidden" name="search" value="'.htmlspecialchars($search).'"><input type="hidden" name="remove_signer_id" value="' . $r['id'] . '"><input type="submit" value="Remove signer"></form></td>';
                $out .= "</tr>";
            }
            if ($out) {
                    print "<table cellpadding=3 border=0><tr><th>Email</th><th>Name</th><th>Petition</th><th>Actions</th></tr>";
                print $out;
                print "</table>";
            }
            else print '<p><em>No matches</em></p>';
        }
    }
}

function petition_admin_remove_signer($id) {
    db_query('UPDATE signer set showname = false where id = ?', $id);
    db_commit();
    print '<p><em>That signer has been removed.</em></p>';
}

function petition_admin_navigation($array = array()) {
    $status = isset($array['status']) ? $array['status'] : '';
    $found = isset($array['found']) ? $array['found'] : 0;
    $search = isset($array['search']) ? $array['search'] : '';
    print "<p><strong>Show &ndash;</strong> ";
    if ($status == 'draft') {
        print 'Draft';
        print ' (' . count($found) . ') / ';
        print '<a href="?page=pet&amp;o=live">Live</a> / ';
        print '<a href="?page=pet&amp;o=finished">Finished</a> / ';
        print '<a href="?page=pet&amp;o=rejected">Rejected</a>';
    } elseif ($status == 'live') {
        print '<a href="?page=pet&amp;o=draft">Draft</a> / ';
        print 'Live';
        print ' (' . count($found) . ') / ';
        print '<a href="?page=pet&amp;o=finished">Finished</a> / ';
        print '<a href="?page=pet&amp;o=rejected">Rejected</a>';
    } elseif ($status == 'finished') {
        print '<a href="?page=pet&amp;o=draft">Draft</a> / ';
        print '<a href="?page=pet&amp;o=live">Live</a> / ';
        print 'Finished';
        print ' (' . count($found) . ') / ';
        print '<a href="?page=pet&amp;o=rejected">Rejected</a>';
    } elseif ($status == 'rejected') {
        print '<a href="?page=pet&amp;o=draft">Draft</a> / ';
        print '<a href="?page=pet&amp;o=live">Live</a> / ';
        print '<a href="?page=pet&amp;o=finished">Finished</a> / ';
        print 'Rejected';
        print ' (' . count($found) . ')';
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
<form method="get" action="./">
<input type="hidden" name="page" value="petsearch">
Search for name/email: <input type="text" name="search" value="<?=htmlspecialchars($search) ?>" size="40">
<input type="submit" value="Search">
</form>
<?
}

class ADMIN_PAGE_PET_MAIN {
    function ADMIN_PAGE_PET_MAIN () {
        $this->id = "pet";
        $this->navname = "Petitions and Signers";
    }

    function petition_header($sort, $status) {
        print '<table border="1" cellpadding="3" cellspacing="0"><tr>';
        $cols = array(
            'z'=>'Surge (day)',
            'r'=>'Ref', 
            'a'=>'Title', 
            's'=>'Signers', 
            'd'=>'Deadline', 
            'e'=>'Creator', 
            'c'=>'Creation Time', 
        );
        foreach ($cols as $s => $col) {
            print '<th>';
            if ($sort != $s) print '<a href="'.$this->self_link.'&amp;s='.$s.'&amp;o='.$status.'">';
            print $col;
            if ($sort != $s) print '</a>';
            print '</th>';
        }
        if ($status == 'draft')
            print '<th>Actions</th>';
        print '</tr>';
        print "\n";
    }

    function list_all_petitions() {
        global $open, $pet_today;
        $sort = get_http_var('s');
        if (!$sort || preg_match('/[^radecsz]/', $sort)) $sort = 'c';
        $order = '';
        if ($sort=='r') $order = 'ref';
        elseif ($sort=='a') $order = 'title';
        elseif ($sort=='d') $order = 'deadline desc';
        elseif ($sort=='e') $order = 'email';
        elseif ($sort=='c') $order = 'petition.creationtime desc';
        elseif ($sort=='s') $order = 'signers desc';
        elseif ($sort=='z') $order = 'surge desc';

        $status = get_http_var('o');
        if (!$status || !preg_match('#^(draft|live|rejected|finished)$#', $status)) $status = 'draft';

        $status_query = "status = '$status'";
        if ($status == 'draft')
            $status_query = "(status = 'draft' or status = 'resubmitted')";
        elseif ($status == 'rejected')
            $status_query = "(status = 'rejected' or status = 'rejectedonce')";
        $q = db_query("
            SELECT petition.*, person.email,
                date_trunc('second',creationtime) AS creationtime, 
                (SELECT count(*) FROM signer WHERE showname and petition_id=petition.id) AS signers,
                (SELECT count(*) FROM signer WHERE showname and petition_id=petition.id AND signtime > ms_current_timestamp() - interval '1 day') AS surge
            FROM petition 
            LEFT JOIN person ON person.id = petition.person_id
            WHERE $status_query
            " .  ($order ? ' ORDER BY ' . $order : '') );
        $found = array();
        while ($r = db_fetch_array($q)) {
            $row = "";

            $row .= '<td>'.$r['surge'].'</td>';
            $row .= '<td>';
            if ($r['status']=='live' || $r['status']=='finished' || $r['status']=='rejected')
                $row .= '<a href="' . OPTION_BASE_URL . '/' . $r['ref'] . '">';
            $row .= $r['ref'];
            if ($r['status']=='live' || $r['status']=='finished' || $r['status']=='rejected')
                $row .= '</a>';
            $row .= '<br><a href="'.$this->self_link.'&amp;petition='.$r['ref'].'">admin</a>';
            $row .= '</td>';
            $row .= '<td>'.trim_characters(htmlspecialchars($r['title']),0,100).'</td>';
            $row .= '<td>'.htmlspecialchars($r['signers']) . '</td>';
            $row .= '<td>' . prettify($r['deadline']) . '</td>';
            $row .= '<td><a href="mailto:'.htmlspecialchars($r['email']).'">'.
                htmlspecialchars($r['name']).'</a></td>';
            $row .= '<td>'.$r['creationtime'].'</td>';
        if ($status == 'draft') {
            $row .= '<td><form method="post"><input type="hidden" name="petition" value="' . $r['id'] .
            '"><input type="submit" name="approve" value="Approve"> <input type="submit" name="reject" value="Reject"></form>';
            if ($r['status'] == 'resubmitted') {
                $row .= ' resubmitted';
            }
            $row .= '</td>';
        }

            $found[] = $row;
        }
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

        petition_admin_navigation(array('status'=>$status, 'found'=>$found));
        $this->petition_header($sort, $status);
        $a = 0;
        foreach ($found as $row) {
            print '<tr'.($a++%2==0?' class="v"':'').'>';
            print $row;
            print '</tr>'."\n";
        }
        print '</table>';
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

        $q = db_query('SELECT petition.*, person.email,
                (SELECT count(*) FROM signer WHERE showname and petition_id=petition.id) AS signers
            FROM petition 
            LEFT JOIN person ON person.id = petition.person_id 
            WHERE ref ILIKE ?', $petition);
        $pdata = db_fetch_array($q);
        if (!$pdata) {
            print sprintf("Petition '%s' not found", htmlspecialchars($petition));
            return;
        }
        $petition_obj = new Petition($pdata);
#        $pledge_obj->render_box(array('showdetails' => true));

        print "<h2>Petition '<a href=\"" . OPTION_BASE_URL . '/' .
            $petition_obj->ref() . "\">" . $pdata['ref'] . "</a>'";
        print "</h2>";

        print "<p>Set by: <b>" . htmlspecialchars($pdata['name']) . " &lt;" .  htmlspecialchars($pdata['email']) . "&gt;</b>, " . $pdata['address'] . ', ' . $pdata['postcode'] . ', ' . $pdata['telephone'];
        print '<br>Organisation: ';
        if ($pdata['org_url']) print '<a href="' . htmlspecialchars($pdata['org_url']) . '">';
        print $pdata['organisation'] ? htmlspecialchars($pdata['organisation']) : 'None given';
        if ($pdata['org_url']) print '</a>';
        print "<br>Created: " . prettify($pdata['creationtime']);
        print "<br>Deadline: <b>" . prettify($pdata['deadline']) . "</b> (" . htmlspecialchars($pdata['rawdeadline']) . ')';
        print '<br>Current status: <b>' . htmlspecialchars($pdata['status']) . '</b>';
        print '<br>Title: <b>' . htmlspecialchars($pdata['title']) . '</b>';
        print '<p>Content of petition: ' . htmlspecialchars($pdata['content']);

        // Signers
        print "<h2>Signers (".$pdata['signers'].")</h2>";
        $query = 'SELECT signer.name as signname,person.email as signemail,
                         date_trunc(\'second\',signtime) AS signtime,
                         signer.id AS signid 
                   FROM signer 
                   LEFT JOIN person ON person.id = signer.person_id
                   WHERE showname AND petition_id=?';
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
            $out[$e] .= '<form name="removesignerform'.$c.'" method="post" action="'.$this->self_link.'"><input type="hidden" name="remove_signer_id" value="' . $r['signid'] . '"><input type="submit" name="remove_signer" value="Remove signer"></form>';
            $out[$e] .= '</td>';
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
        print '<p>';
        
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
            print "<br>has been queued to evel for ";
            print "<strong>$got_creator_count creators</strong>";
            print " and <strong>$got_signer_count signers</strong>";
            if ($r['sms'])
                print "<br><strong>sms content:</strong> " . $r['sms'];
            if ($r['emailtemplatename'])
                print "<br><strong>email template:</strong> " . $r['emailtemplatename'];
            if ($r['emailsubject'])
                print "<br><strong>email subject:</strong> " . htmlspecialchars($r['emailsubject']);
            if ($r['emailbody']) {
                print '<br><strong>email body:</strong>
                <div class="message">.'.comments_text_to_html($r['emailbody'])."</div>";
            }

        }
        if ($n == 0) {
            print "No messages yet.";
        }

        print '<h2>Actions</h2>';
#        print '<form name="sendannounceform" method="post" action="'.$this->self_link.'"><input type="hidden" name="send_announce_token_pledge_id" value="' . $pdata['id'] . '"><input type="submit" name="send_announce_token" value="Send announce URL to creator"></form>';

#print '<form name="removepledgepermanentlyform" method="post" action="'.$this->self_link.'" style="clear:both"><strong>Caution!</strong> This really is forever, you probably don\'t want to do it: <input type="hidden" name="remove_pledge_id" value="' . $pdata['id'] . '"><input type="submit" name="remove_pledge" value="Remove pledge permanently"></form>';

    }

/*    function remove_petition($id) {
        petition_delete_petition($id);
        db_commit();
        print p(_('<em>That petition has been successfully removed, along with all its signatories.</em>'));
    }
*/

    var $categories = array(
        1 => 'Party political material',
        2 => 'False or defamatory statements',
        4 => 'Information protected by an injunction or court order',
        8 => 'Material which is commercially sensitive, confidential or which may cause personal distress or loss',
        16 => 'Names of individual officials of public bodies, unless part of the senior management of those organisations',
        32 => 'Names of family members of officials of public bodies, or elected representatives',
        64 => 'Names of individuals, or information where they may be identified, in relation to criminal accusations',
        128 => 'Offensive language'
    );

    function prettify_categories($categories, $newlines) {
        $cat = 1;
	$out = array();
        while ($categories>0) {
	    if ($categories % 2) $out[] = $this->categories[$cat];
	    $categories = floor($categories / 2);
	    $cat *= 2;
	}
	if ($newlines)
            return '    ' . join("\n    ", $out) . "\n";
        return join(', ', $out);
    }

    function display_categories() {
        foreach ($this->categories as $n => $category) {
            print '<br><input type="checkbox" name="categories[]" value="' . $n;
            print '"> id="cat' . $n . '"> <label for="cat' . $n . '">';
	    print $category . '</label>';
        }
    }

    function reject_form($id) {
        $p = new Petition($id); ?>
<p>You have chosen to reject the petition '<?=$p->ref() ?>'.</p>
<form method="post"><input type="hidden" name="reject_form_submit" value="1">
<input type="hidden" name="petition" value="<?=$id ?>">
<p>Category or categories for rejection:
<?      $this->display_categories(); ?>
</p>
<p>Reason for rejection (this will be emailed to the creator and available on the website):
<br><textarea name="reason" rows="10" cols="70"></textarea></p>
<input type="submit" value="Reject petition">
</form>
<?  }

    function reject_petition($id, $categories, $reason) {
        $p = new Petition($id);
        $status = $p->status();
	$cats_pretty = $this->prettify_categories($categories, false);
	$cats_pretty_nl = $this->prettify_categories($categories, true);
        if ($status == 'draft') {
            db_getOne("UPDATE petition SET status='rejectedonce', rejection_first_categories=?, rejection_first_reason=? WHERE id=?", array($categories, $reason, $id));
            $p->log_event("Admin rejected petition for the first time. Category $cats_pretty, reason $reason", null);
            $template = 'admin-rejected-once';
            $post_data = array('data' => base64_encode(serialize($p->data)), 'tostepintro' => 1);
            $stash = stash_new_request('POST', OPTION_BASE_URL . '/new', $post_data);
            $url = OPTION_BASE_URL . '/new?stashpost=' . $stash;
        } elseif ($status == 'resubmitted') {
            db_getOne("UPDATE petition SET status='rejected', rejection_second_categories=?, rejection_second_reason=? WHERE id=?", array($categories, $reason, $id));
            $p->log_event("Admin rejected petition for the second time. Category $cats_pretty, reason $reason", null);
            $template = 'admin-rejected-again';
            $url = '';
        } else {
            $p->log_event("Bad rejection", null);
            db_commit();
            err("Should only be able to reject petitions in draft or resubmitted state");
        }
	return;
        db_commit();
        $to = $p->creator_email();
        $values = array_merge($p->data, array(
            'url' => $url,
            'categories' => $cats_pretty_nl,
            'reason' => $reason
        ));
        print '<p><em>That petition has been rejected.';
        if (pet_send_email_template($to, $template, $values))
            print ' Message sent to creator.';
        else
            print ' Email to creator failed!';
        print '</em></p>';
    }

    function display() {
        db_connect();

        $petition = get_http_var('petition'); # currently ID
        $petition_id = null;

        // Perform actions
        if (get_http_var('remove_signer_id')) {
            $signer_id = get_http_var('remove_signer_id');
            if (ctype_digit($signer_id)) {
                $petition_id = db_getOne("SELECT petition_id FROM signer WHERE id = $signer_id");
                petition_admin_remove_signer($signer_id);
            }
        }
/*
        } elseif (get_http_var('send_announce_token')) {
            $petition_id = get_http_var('send_announce_token_pledge_id');
            if (ctype_digit($petition_id)) {
                send_announce_token($petition_id);
                print p(_('<em>Announcement permission mail sent</em>'));
            }
        }
*/
        if (get_http_var('approve')) {
            $p = new Petition($petition);
            db_getOne("UPDATE petition SET status='live',deadline=deadline+(ms_current_date()-date_trunc('day', creationtime)) WHERE id=?", $petition);
            $p->log_event("Admin approved petition", null);
            db_commit();
            print '<p><em>Petition approved!</em></p>';
            $petition = null;
        } elseif (get_http_var('reject')) {
            $this->reject_form($petition);
            $petition = null;
        } elseif (get_http_var('reject_form_submit')) {
            $categories = get_http_var('categories');
	    if (is_array($categories)) $categories = array_sum($categories);
	    else $categories = 0;
            $reason = get_http_var('reason');
            if ($categories && $reason) {
                $this->reject_petition($petition, $categories, $reason);
            } else {
                $this->reject_form($petition);
            }
            $petition = null;
        }

        // Display page
        if ($petition_id) {
            $petition = db_getOne('SELECT ref FROM petition WHERE id = ?', $petition_id);
        }
        if ($petition) {
            $this->show_one_petition($petition);
        } else {
            $this->list_all_petitions();
        }
    }
}

?>
