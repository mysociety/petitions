<?php
/*
 * admin-pet.php:
 * Petition admin pages.
 * 
 * Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: admin-pet.php,v 1.1 2006-06-28 23:35:56 matthew Exp $
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
        $petitions_rejected = db_getOne("SELECT COUNT(*) FROM petition WHERE status='rejected'");
        $signatures = db_getOne('SELECT COUNT(*) FROM signer');
        $signers = db_getOne('SELECT COUNT(DISTINCT person_id) FROM signer');
        
        print "Total petitions in system: $petitions<br>$petitions_live live, $petitions_draft draft, $petitions_closed finished, $petitions_rejected rejected<br>$signatures signatures, $signers signers";
    }
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

        $q = db_query("
            SELECT petition.*, person.email,
                date_trunc('second',creationtime) AS creationtime, 
                (SELECT count(*) FROM signer WHERE petition_id=petition.id) AS signers,
                (SELECT count(*) FROM signer WHERE petition_id=petition.id AND signtime > ms_current_timestamp() - interval '1 day') AS surge
            FROM petition 
            LEFT JOIN person ON person.id = petition.person_id
            WHERE status='$status'
            " .  ($order ? ' ORDER BY ' . $order : '') );
        $found = array();
        while ($r = db_fetch_array($q)) {
            $row = "";

            $row .= '<td>'.$r['surge'].'</td>';
            $row .= '<td><a href="' . OPTION_BASE_URL . '/' . $r['ref'] . 
#                pb_domain_url(array('path'=>"/".$r['ref'])) .
                '">'.$r['ref'].'</a>'.
                '<br><a href="'.$this->self_link.'&amp;petition='.$r['ref'].'">admin</a>';
            $row .= '</td>';
            $row .= '<td>'.trim_characters(htmlspecialchars($r['title']),0,100).'</td>';
            $row .= '<td>'.htmlspecialchars($r['signers']) . '</td>';
            $row .= '<td>' . prettify($r['deadline']) . '</td>';
            $row .= '<td><a href="mailto:'.htmlspecialchars($r['email']).'">'.
                htmlspecialchars($r['name']).'</a></td>';
            $row .= '<td>'.$r['creationtime'].'</td>';
        if ($status == 'draft') {
            $row .= '<td><form method="post"><input type="hidden" name="petition" value="' . $r['id'] .
            '"><input type="submit" name="approve" value="Approve"> <input type="submit" name="reject" value="Reject"></form></td>';
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

        print "<p><strong>Show:</strong> ";
        $status_url = "";
        if ($status == 'draft') {
            print 'Draft';
            print ' (' . count($found) . ') | ';
            print '<a href="?page=pet&amp;o=live">Live</a> | ';
            print '<a href="?page=pet&amp;o=finished">Finished</a> | ';
            print '<a href="?page=pet&amp;o=rejected">Rejected</a>';
        } elseif ($status == 'live') {
            print '<a href="?page=pet&amp;o=draft">Draft</a> | ';
            print 'Live';
             print ' (' . count($found) . ') | ';
            print '<a href="?page=pet&amp;o=finished">Finished</a> | ';
            print '<a href="?page=pet&amp;o=rejected">Rejected</a>';
        } elseif ($status == 'finished') {
            print '<a href="?page=pet&amp;o=draft">Draft</a> | ';
            print '<a href="?page=pet&amp;o=live">Live</a> | ';
            print 'Finished';
            print ' (' . count($found) . ') | ';
            print '<a href="?page=pet&amp;o=rejected">Rejected</a>';
        } else {
            print '<a href="?page=pet&amp;o=draft">Draft</a> | ';
            print '<a href="?page=pet&amp;o=live">Live</a> | ';
            print '<a href="?page=pet&amp;o=finished">Finished</a> | ';
            print 'Rejected';
            print ' (' . count($found) . ')';
        }
        print " <strong>petitions</strong></p>";
          
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

    function show_one_petition($pledge) {
        return;
        print '<p><a href="'.$this->self_link.'">' . _('List of all pledges') . '</a></p>';

        $sort = get_http_var('s');
        if (!$sort || preg_match('/[^etcn]/', $sort)) $sort = 't';
        $list_limit = get_http_var('l');
        if ($list_limit) {
            $list_limit = intval($list_limit);
            if ($list_limit == -1)
                $list_limit = null;
        }
        else
            $list_limit = 100;

        $q = db_query('SELECT pledges.*, person.email,
                (SELECT count(*) FROM signer WHERE pledge_id=pledges.id) AS signers,
                (SELECT count(*) FROM comment WHERE pledge_id=pledges.id AND NOT ishidden) AS comments
            FROM pledges 
            LEFT JOIN person ON person.id = pledges.person_id 
            LEFT JOIN location ON location.id = pledges.location_id
            WHERE ref ILIKE ?', $pledge);
        $pdata = db_fetch_array($q);
        if (!$pdata) {
            print sprintf("Pledge '%s' not found", htmlspecialchars($pledge));
            return;
        }
        $pledge_obj = new Petition($pdata);

        $pledge_obj->render_box(array('showdetails' => true));

        print "<h2>Pledge '<a href=\"".
#                pb_domain_url(array('path'=>"/".$pledge_obj->ref(), 'lang'=>$pledge_obj->lang(), 'country'=>$pledge_obj->country_code())) .
                "\">" . $pdata['ref'] . "</a>'";
        print "</h2>";

        print "<p>Set by: <b>" . htmlspecialchars($pdata['name']) . " &lt;" .  htmlspecialchars($pdata['email']) . "&gt;</b>";
        print "<br>Created: <b>" . prettify($pdata['creationtime']) . "</b>";
        print "<br>Deadline: <b>" . prettify($pdata['deadline']) . "</b>";

        // Signers
        print "<h2>Signers (".$pdata['signers'].")</h2>";
        $query = 'SELECT signer.name as signname,person.email as signemail,
                         date_trunc(\'second\',signtime) AS signtime,
                         showname, signer.id AS signid 
                   FROM signer 
                   LEFT JOIN person ON person.id = signer.person_id
                   WHERE pledge_id=?';
        if ($sort=='t') $query .= ' ORDER BY signtime DESC';
        elseif ($sort=='n') $query .= ' ORDER BY showname DESC';
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

            $out[$e] .= '<td><form name="shownameform'.$c.'" method="post" action="'.$this->self_link.'"><input type="hidden" name="showname_signer_id" value="' . $r['signid'] . '">';
            $out[$e] .= '<select name="showname">';
            $out[$e] .=  '<option value="1"' . ($r['showname'] == 't'?' selected':'') . '>Yes</option>';
            $out[$e] .=  '<option value="0"' . ($r['showname'] == 'f'?' selected':'') . '>No</option>';
            $out[$e] .=  '</select>';
            $out[$e] .= '<input type="submit" name="showname_signer" value="update">';
            $out[$e] .= '</form></td>';

            $out[$e] .= '<td>';
            $out[$e] .= '<form name="removesignerform'.$c.'" method="post" action="'.$this->self_link.'"><input type="hidden" name="remove_signer_id" value="' . $r['signid'] . '"><input type="submit" name="remove_signer" value="Remove signer permanently"></form>';
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
            $cols = array('e'=>'Signer', 't'=>'Time', 'n'=>'Show name?');
            foreach ($cols as $s => $col) {
                print '<th>';
                if ($sort != $s) print '<a href="'.$this->self_link.'&amp;pledge='.$pledge.'&amp;s='.$s.'">';
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
                print '<a href="'.$this->self_link.'&amp;pledge='.$pledge.'&amp;l=-1">show all</a>';
                print ' (do not press if you are Tom, it will crash your computer :)</p>';
            }
        } else {
            print '<p>Nobody has signed up to this pledge.</p>';
        }
        print '<p>';
        
        // Messages
        print h2(_("Messages"));
        $q = db_query('select * from message 
                where pledge_id = ? order by whencreated', $pdata['id']);

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
        print '<form name="sendannounceform" method="post" action="'.$this->self_link.'"><input type="hidden" name="send_announce_token_pledge_id" value="' . $pdata['id'] . '"><input type="submit" name="send_announce_token" value="Send announce URL to creator"></form>';

print '<form name="removepledgepermanentlyform" method="post" action="'.$this->self_link.'" style="clear:both"><strong>Caution!</strong> This really is forever, you probably don\'t want to do it: <input type="hidden" name="remove_pledge_id" value="' . $pdata['id'] . '"><input type="submit" name="remove_pledge" value="Remove pledge permanently"></form>';

    }

/*    function remove_petition($id) {
        petition_delete_petition($id);
        db_commit();
        print p(_('<em>That petition has been successfully removed, along with all its signatories.</em>'));
    }
*/

    function remove_signer($id) {
        petition_delete_signer($id);
        db_commit();
        print p(_('<em>That signer has been successfully removed.</em>'));
    }

    function showname_signer($id) {
        db_query('UPDATE signer set showname = ? where id = ?', 
            array(get_http_var('showname') ? true : false, $id));
        db_commit();
    # TRANS: http://www.mysociety.org/pipermail/mysociety-i18n/2005-November/000078.html
        print p(_('<em>Show name for signer updated</em>'));
    }

    function display($self_link) {
        db_connect();

        $petition = get_http_var('petition'); # currently ID
        $petition_id = null;

        // Perform actions
/*        if (get_http_var('remove_pledge_id')) {
            $remove_id = get_http_var('remove_pledge_id');
            if (ctype_digit($remove_id))
                $this->remove_pledge($remove_id); */
/*
        if (get_http_var('remove_signer_id')) {
            $signer_id = get_http_var('remove_signer_id');
            if (ctype_digit($signer_id)) {
                $petition_id = db_getOne("SELECT petition_id FROM signer WHERE id = $signer_id");
                $this->remove_signer($signer_id);
            }
        } elseif (get_http_var('showname_signer_id')) {
            $signer_id = get_http_var('showname_signer_id');
            if (ctype_digit($signer_id)) {
                $petition_id = db_getOne("SELECT petition_id FROM signer WHERE id = $signer_id");
                $this->showname_signer($signer_id);
            }
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
            db_getOne("UPDATE petition SET status='live' WHERE id=?", $petition);
            db_commit();
            $p->log_event("Admin approved petition $petition", null);
            print '<p><em>Petition approved!</em></p>';
            $petition = null;
        } elseif (get_http_var('reject')) {
            $p = new Petition($petition);
            db_getOne("UPDATE petition SET status='rejected' WHERE id=?", $petition);
            db_commit();
            $p->log_event("Admin rejected petition $petition", null);
            print '<p><em>Petition rejected!</em> (will ask you to give reason here etc.)</p>';
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
