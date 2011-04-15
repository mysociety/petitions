<?php
/*
 * admin-pet.php:
 * Petition admin pages.
 * 
 * Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: admin-pet.php,v 1.136 2010-05-06 12:30:59 matthew Exp $
 * 
 */

require_once "../phplib/pet.php";
require_once "../phplib/petition.php";
require_once '../phplib/cobrand.php';
require_once "../commonlib/phplib/db.php";
require_once "../commonlib/phplib/utility.php";
require_once "../commonlib/phplib/importparams.php";
require_once '../commonlib/phplib/datetime.php';

class ADMIN_PAGE_PET_SUMMARY {
    function ADMIN_PAGE_PET_SUMMARY() {
        $this->id = 'summary';
        $this->navname = 'Admin interface';
    }
    function display() {
        petition_admin_navigation($this);
    }
}

class ADMIN_PAGE_PET_STATS {
    function ADMIN_PAGE_PET_STATS() {
        $this->id = 'stats';
        $this->navname = 'Statistics';
    }

    function date_range($from, $to) {
        $end_interval = "?::date+'1 day'::interval";
        $petitions_submitted = db_getOne("select count(*) from petition
            where creationtime>=? and creationtime<$end_interval
            and status not in ('unconfirmed', 'failedconfirm', 'sentconfirm')",
            $from['iso'], $to['iso']);
        $threshold = cobrand_signature_threshold();
        $closed_less = db_getOne("select count(*) from petition
            where deadline>=? and deadline<=? and cached_signers<?
            and status='finished'", $from['iso'], $to['iso'], $threshold);
        $closed_more = db_getOne("select count(*) from petition
            where deadline>=? and deadline<=? and cached_signers>=?
            and status='finished'", $from['iso'], $to['iso'], $threshold);
        $signatures = db_getOne("select count(*) from signer
            where signtime>=? and signtime<$end_interval
            and emailsent='confirmed' and showname='t'",
            $from['iso'], $to['iso']);
        $responses = db_getOne("select count(*) from message
            where whencreated>=? and whencreated<$end_interval
            and circumstance='government-response'",
            $from['iso'], $to['iso']);
        $responded_sigs = db_getOne("select count(*) from message_signer_recipient
            where message_id in (select id from message
                where whencreated>=? and whencreated<$end_interval
                and circumstance='government-response')",
            $from['iso'], $to['iso']);
        $petitions_closed = db_getAll("select ref from petition
            left join message on petition.id=petition_id and circumstance='government-response'
            where deadline>=? and deadline<=? and cached_signers>=? and status='finished'
            and petition_id is null order by deadline", $from['iso'], $to['iso'], $threshold);
        $from_pretty = prettify($from['iso']);
        $to_pretty = prettify($to['iso']);
        echo <<<EOF
<h2>Statistics for $from_pretty to $to_pretty</h2>
<ul>
<li>Number of petitions submitted: $petitions_submitted
<li>Petitions closed with fewer than $threshold signatures: $closed_less
<li>Petitions closed with $threshold signatures or more: $closed_more
<li>Number of signatures placed: $signatures
<li>Number of petitions responded to: $responses
<li>Number of signatures emailed government responses: $responded_sigs
</ul>
EOF;
        if (count($petitions_closed)) {
            echo '<h3>Closed petitions with ' . $threshold . ' signatures or more that have not received a response</h3> <ul>';
            foreach ($petitions_closed as $p) {
                echo '<li><a href="?page=pet&amp;petition=', $p['ref'], '">', $p['ref'], '</a></li>';
            }
            echo '</ul>';
        }
    }

    function display() {
        global $pet_time;
        $from = get_http_var('from');
        $to = get_http_var('to');
        
        $multiple = '';
        if (OPTION_SITE_TYPE == 'multiple' && ($site = cobrand_admin_is_site_user()))
            $multiple = "_$site";

        # Overall
        $statsdate = prettify(substr(db_getOne("SELECT whencounted FROM stats order by id desc limit 1"), 0, 19));

        # Petitions
        $petitions = array(
            'offline' => 0, 'online' => 0,
            'unconfirmed'=>0, 'failedconfirm'=>0, 'sentconfirm'=>0,
            'draft'=>0, 'rejectedonce'=>0, 'resubmitted'=>0,
            'rejected'=>0, 'live'=>0, 'finished'=>0,
            'all_confirmed'=>0, 'all_unconfirmed'=>0
        );
        foreach (array_keys($petitions) as $t) {
            $petitions[$t] = db_getOne("SELECT value FROM stats WHERE key = 'petitions_$t$multiple' order by id desc limit 1");
            if (!$petitions[$t]) $petitions[$t] = 0;
        }

        # Signatures
        $signatures = array(
            'confirmed' => 0, 'sent' => 0, 'confirmed_unique' => 0, 'offline' => 0
        );
        foreach (array_keys($signatures) as $t) {
            $signatures[$t] = db_getOne("SELECT value FROM stats WHERE key = 'signatures_$t$multiple' order by id desc limit 1");
            if (!$signatures[$t]) $signatures[$t] = 0;
        }
        $signatures['total'] = $signatures['confirmed'] + $signatures['offline'];
        $average_sigs_per_petition = '-';
        if ($petitions['live'] || $petitions['finished'])
            $average_sigs_per_petition = round($signatures['total'] / ($petitions['live'] + $petitions['finished']), 2);

        # Responses 
        $responses = db_getOne("select count(*) from message where circumstance = 'government-response'");
        $unique_responses = db_getOne("select count(distinct petition_id) from message where circumstance = 'government-response'");

        $wards_summary = array();
        if ($wards = cobrand_admin_wards_for_petition()) {
            $wards_summary = db_getAll('select area_id,count(*) as c from petition_area group by area_id');
            foreach ($wards_summary as $id => $row) {
                $wards_summary[$id] = $row + $wards[$row['area_id']];
            }
            usort($wards_summary, 'sort_by_name');
        }

        $responsible_summary = array();
        if (cobrand_admin_responsible_option()) {
            $responsible_summary = db_getAll("select coalesce(responsible,'') as name,count(*) as c from petition
                where status not in ('unconfirmed', 'failedconfirm', 'sentconfirm')
                group by coalesce(responsible, '')");
            usort($responsible_summary, 'sort_by_name');
        }

        # Percentages
        foreach (array('live', 'finished', 'rejected', 'online', 'offline') as $t) {
            $petitions[$t.'_pc'] = $petitions['all_confirmed']
                ? round($petitions[$t] / $petitions['all_confirmed'] * 100, 1)
                : '-';
        }
        foreach (array('confirmed', 'offline') as $t) {
            $signatures[$t.'_pc'] = $signatures['total']
                ? round($signatures[$t] / $signatures['total'] * 100, 1)
                : '-';
        }

        petition_admin_navigation($this);
        if ($from && $to) {
            $parsed_from = datetime_parse_local_date($from, $pet_time, 'en', 'GB');
            $parsed_to = datetime_parse_local_date($to, $pet_time, 'en', 'GB');
            if ($parsed_from && $parsed_to) {
                $this->date_range($parsed_from, $parsed_to);
            }
        }
        include_once '../templates/admin/admin-stats.php';

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
        $this->noindex = true;
        $this->navname = 'Search';
    }

    function search_signers($q) {
        $return = array('confirmed'=>array(), 'unconfirmed'=>array());
        while ($r = db_fetch_array($q)) {
            if ($r['emailsent'] == 'confirmed')
                $return['confirmed'][] = $r;
            elseif ($r['emailsent'] == 'sent')
                $return['unconfirmed'][] = $r;
        }
        return $return;
    }

    function display() {
        petition_admin_perform_actions();
        $search = strtolower(get_http_var('search'));
        $search_pet = "select petition.id, petition.ref, petition.name, email,
                status, date_trunc('second', creationtime) as creationtime
            from petition LEFT JOIN body ON body_id=body.id
            where status not in ('unconfirmed', 'failedconfirm') ";
        $search_sign = "select signer.id, petition.ref, signer.name, signer.email, emailsent,
                date_trunc('second', signtime) as signtime
            from signer, petition LEFT JOIN body ON body_id=body.id
            where signer.petition_id = petition.id
            and showname = 't' and emailsent in ('sent', 'confirmed') and signer.email!='' ";
        $search_pet .= cobrand_admin_site_restriction();
        $search_sign .= cobrand_admin_site_restriction();
        $out = array();
        if ($search && validate_email($search)) {
            $out['petitions'] = db_getAll($search_pet . "and lower(email) = ?", array($search));
            $q = db_query($search_sign . "and lower(signer.email) = ?", array($search));
            $out['signers'] = $this->search_signers($q);
        } elseif ($search) {
            $out['petitions'] = db_getAll($search_pet . "
                and (petition.name ilike '%'||?||'%' or lower(email) like '%'||?||'%' or lower(petition.ref) = ?)
                order by lower(email)", array($search, $search, $search));
            $q = db_query($search_sign . "
                and (signer.name ilike '%'||?||'%' or lower(signer.email) ilike '%'||?||'%')
                order by emailsent, lower(signer.email)", array($search, $search));
            $out['signers'] = $this->search_signers($q);
        }
        petition_admin_navigation($this, array('search'=>$search));
        include_once '../templates/admin/admin-search.php';
    }
}

class ADMIN_PAGE_PET_OFFLINE {
    function ADMIN_PAGE_PET_OFFLINE () {
        $this->id = "offline";
        $this->navname = "Offline petitions";
    }

    function display() {
        global $pet_today;

        $data = array();
        foreach (array( 'body', 'body_ref', 'pet_content', 'detail', 'ref', 'category', 'offline_signers', 'rawdeadline', 'name', 'email', 'organisation', 'address', 'postcode', 'telephone', 'offline_link', 'offline_location' ) as $var) {
            $data[$var] = get_http_var($var);
        }
        $errors = array();

        if (get_http_var('offline_create')) {
            # Error checking. Bit of overlap with new.php
            if (OPTION_SITE_TYPE == 'multiple') {
                if (!$data['body'] || !$data['body_ref']) {
                    $errors['body'] = _('Please pick who you wish to petition');
                } else {
                    $q = db_query('SELECT ref FROM body WHERE id=? and ref=?', array($data['body'], $data['body_ref']));
                    if (!db_num_rows($q))
                        $errors['body'] = _('Please pick a valid body to petition');
                }
            } else {
                $data['body'] = null;
                $data['body_ref'] = null;
            }

            if (!$data['pet_content'])
                $errors[] = 'Please give the main sentence of the petition';
            $ddd = preg_replace('#\s#', '', $data['detail']);

            if (cobrand_display_category()){
                if (!$data['category'] || !array_key_exists($data['category'], cobrand_categories(cobrand_admin_is_site_user()))) {
                    $errors['category'] = 'Please select a category';
                #} elseif (!cobrand_category_okay($data['category'])) {
                #    $errors['category'] = 'Petitions in that category cannot currently be made (they have to go to a different place).';
                }
            } else {
                $data['category'] = 0; # force no-category
            }

            $disallowed_refs = array('contact', 'translate', 'posters', 'graphs', 'privacy', 'reject');
            if (!$data['ref'])
                $errors[] = 'Please give a short reference';
            elseif (strlen($data['ref']) < 6)
                $errors['ref'] = _('The short name must be at least six characters long');
            elseif (strlen($data['ref']) > 16)
                $errors['ref'] = _('The short name can be at most 16 characters long');
            elseif (in_array(strtolower($data['ref']), $disallowed_refs))
                $errors['ref'] = _('That short name is not allowed.');
            elseif (preg_match('/[^a-z0-9-]/i', $data['ref']))
                $errors['ref'] = _('The short name must only contain letters, numbers, or a hyphen.  Spaces are not allowed.');
            elseif (!preg_match('/[a-z]/i', $data['ref']))
                $errors['ref'] = _('The short name must contain at least one letter.');
            $dupe = db_getOne('select id from petition where lower(ref) = ?', strtolower($data['ref']));
            if ($dupe)
                $errors['ref'] = _('That short name is already taken');

            if (!$data['name'])
                $errors[] = 'Please give the creator name';
            if (!$data['address'])
                $errors[] = 'Please give the creator address';

            $deadline = datetime_parse_local_date($data['rawdeadline'], time(), 'en', 'GB');
            if (!$data['rawdeadline'])
                $errors[] = 'Please give a date';
            elseif ($deadline && !$deadline['error'] && $deadline['iso'] > $pet_today)
                $errors[] = 'Please specify a date in the past.';
            elseif ($deadline && !$deadline['error'])
                $data['deadline'] = $deadline['iso'];
            else
                $errors[] = 'Sorry, that date could not be parsed';

            if (!$data['offline_signers'])
                $errors[] = 'Please give the number of signatures';
            elseif (!ctype_digit($data['offline_signers']))
                $errors[] = 'Please give a figure for the number of signatures';

            if (!$data['postcode'])
                $errors[] = 'Please give the creator postcode';
            elseif (!validate_postcode($data['postcode']))
                $errors[] = 'Please give a valid postcode';

            if (!$errors) {
                db_query('lock table petition in share mode');
                db_query("
                    insert into petition (
                        body_id, content, detail,
                        deadline, rawdeadline, email,
                        name, ref, offline_signers,
                        offline_link, offline_location,
                        organisation, address,
                        postcode, telephone, category,
                        creationtime, status,
                        comments, address_type, org_url
                    ) values (
                        ?, ?, ?, ?,
                        ?, ?,
                        ?, ?, ?, 
                        ?, ?,
                        ?, ?,
                        ?, ?, ?,
                        ms_current_timestamp(), 'finished',
                        '', '', ''
                    )",
                    $data['body'], $data['pet_content'], $data['detail'],
                    $data['deadline'], $data['rawdeadline'], $data['email'],
                    $data['name'], $data['ref'], $data['offline_signers'],
                    $data['offline_link'], $data['offline_location'],
                    $data['organisation'], $data['address'],
                    $data['postcode'], $data['telephone'], $data['category']
                );
                stats_change('cached_petitions_finished', '+1', $data['category'], $data['body_ref']);
                db_commit();
                header('Location: ' . OPTION_ADMIN_URL . '?page=pet&o=finished');
                exit;
            }
        }

        petition_admin_navigation($this);
        include_once '../templates/admin/admin-offline.php';
    }
}

class ADMIN_PAGE_PET_MAIN {
    function ADMIN_PAGE_PET_MAIN () {
        $this->id = "pet";
        $this->navname = "Petitions and signers";
    }

    function petition_header($sort, $status) {
        print '<table><tr>';
        $cols = array(
            'z'=>'Signers<br>(in last day)',
            'r'=>'Petition reference', 
            'a'=>'Petition title', 
            's'=>'Signers', 
            'd'=>'Deadline', 
            'e'=>'Creator', 
            'c'=>'Last Status Change', 
        );
        if ($status == 'archived')
            $cols['m'] = 'Month of archiving';
        foreach ($cols as $s => $col) {
            print '<th>';
            if ($sort != $s && ($s != 'z' || $status == 'live'))
                print '<a href="'.$this->self_link.'&amp;s='.$s.'&amp;o='.$status.'">';
            print $col;
            if ($sort != $s && ($s != 'z' || $status == 'live'))
                print '</a>';
            print '</th>';
        }
        if ($status == 'rejected' || (!$this->cat_change && ($status == 'finished' || $status == 'draft' || $status == 'live')))
            print '<th>Actions</th>';
        print '</tr>';
        print "\n";
    }

    function list_all_petitions() {
        global $global_petition_categories;
        $sort = get_http_var('s');
        $default_sort = 'c';
        if (get_http_var('o') == 'archived') $default_sort = 'm';
        if (!$sort || preg_match('/[^radecszm]/', $sort)) $sort = $default_sort;
        $order = '';
        if ($sort=='r') $order = 'ref';
        elseif ($sort=='a') $order = 'content';
        elseif ($sort=='d') $order = 'deadline desc';
        elseif ($sort=='e') $order = 'name';
        elseif ($sort=='c') $order = 'petition.laststatuschange desc';
        elseif ($sort=='s') $order = 'signers desc';
        elseif ($sort=='z') $order = 'surge desc';
        elseif ($sort=='m') $order = 'archived';

        $page = get_http_var('p'); if (!ctype_digit($page) || $page<0) $page = 0;
        $page_limit = 100;
        $offset = $page * $page_limit;

        $this->cat_change = get_http_var('cats') ? true : false;
        $categories = '';
        foreach ($global_petition_categories as $id => $cat) {
            $categories .= '<option value="' . $id . '">' . $cat;
        }

        $status = get_http_var('o');
        if (!$status || !preg_match('#^(draft|live|rejected|finished|archived)$#', $status)) $status = 'draft';

        $status_query = "status = '$status'";
        if ($status == 'draft')
            $status_query = "(status = 'draft' or status = 'resubmitted')";
        elseif ($status == 'rejected')
            $status_query = "(status = 'rejected' or status = 'rejectedonce')";
        elseif ($status == 'finished' && cobrand_archive_option())
            $status_query = "(status = 'finished' and archived is null)";
        elseif ($status == 'archived')
            $status_query = "(status = 'finished' and archived is not null)";
        
        $status_query .= cobrand_admin_site_restriction();

        $surge = '';
        if ($status == 'live')
            $surge = "(SELECT count(*) FROM signer WHERE showname = 't' and petition_id=petition.id AND signtime > ms_current_timestamp() - interval '1 day' and emailsent = 'confirmed') AS surge,";

        $q = db_query("
            SELECT petition.*, body.name as body_name, body.ref as body_ref,
                date_trunc('second',laststatuschange) AS laststatuschange,
                (ms_current_timestamp() - interval '7 days' > laststatuschange) AS late, 
                (deadline + interval '1 year' >= ms_current_date()) AS response_possible,
                cached_signers AS signers,
                $surge
                message.c AS message_count
            FROM petition
            LEFT JOIN (select petition_id, count(id) as c from message where circumstance='government-response' group by petition_id) message
                ON petition.id = message.petition_id
            LEFT JOIN body ON body.id = petition.body_id
            WHERE $status_query
            " .  ($order ? ' ORDER BY ' . $order : '')
            . ' OFFSET ' . $offset . ' LIMIT ' . $page_limit);
        $found = array();
        while ($r = db_fetch_array($q)) {
            $p = new Petition($r);

            $row = "";
            $row .= '<td>' . (isset($r['surge']) ? $r['surge'] : '') . '</td>';
            $row .= '<td>';
            if ($r['status']=='live' || $r['status']=='finished' || $r['status']=='rejected')
                $row .= '<a href="' . $p->url_main() . '">' . $r['ref'] . '</a>';
            else
                $row .= $r['ref'];
            $row .= '<br>(<a href="'.$this->self_link.'&amp;petition='.$r['ref'].'">admin</a>)';
            $row .= '</td>';
            $row .= '<td>' . trim_characters(htmlspecialchars($r['content']),0,100);
            if ($this->cat_change) {
                $disp_cat = preg_replace('#value="'.$r['category'].'"#', '$0 selected', $categories);
                $row .= '<br><select name="category[' . $r['id'] . ']">' . $disp_cat . '</select>';
            }
            $row .= '</td>';
            $row .= '<td>' . htmlspecialchars($r['signers']);
            if (!is_null($r['offline_signers'])) $row .= ' / ' . htmlspecialchars($r['offline_signers']);
            $row .= '</td>';
            $row .= '<td>' . prettify($r['deadline']) . '</td>';
            $row .= '<td><a href="mailto:' . privacy($r['email']).'">'.
                htmlspecialchars($r['name']).'</a></td>';
            $row .= '<td>'.prettify($r['laststatuschange']).'</td>';
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
                $row .= '<td style="white-space: pre"><form name="petition_admin_approve" method="post" action="'
                    . $this->self_link.'"><input type="hidden" name="petition_id" value="'
                    . $r['id'] . '"><input type="submit" name="reject" value="Reject">';
                if (OPTION_SITE_TYPE == 'multiple') {
                    $row .= ' <input type="submit" name="forward" value="Forward">';
                }
                $row .= '</form>';
                if ($r['status'] == 'resubmitted') {
                    $row .= ' resubmitted';
                }
                $row .= '</td>';
            } elseif (!$this->cat_change && ($status == 'finished' || $status == 'live')) {
                $row .= '<td>';
                if ($r['message_count'] > 1)
                    $row .= 'Responses sent';
                elseif ($r['message_count'])
                    $row .= 'Response sent';
                else {
                    $row .= '<form name="petition_admin_go_respond" method="post" action="'.$this->self_link.'"><input type="hidden" name="petition_id" value="' . $r['id'] . 
                        '">';
                    if ($r['response_possible'] == 't' && !OPTION_RESPONSE_DISABLED) {
                        $row .= '<input type="submit" name="respond" value="Write response">';
                    }
                    if ($status == 'live') {
                        $row .= ' <input type="submit" name="redraft" value="Undo approval">';
                    }
                    $row .= ' <input type="submit" name="remove" value="Remove petition">';
                    $row .= '</form>';
                }
                $row .= '</td>';
            } elseif ($status == 'archived') {
                $row .= '<td>';
                $row .= date('F Y', strtotime($r['archived']));
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
        print '<p>Here you can view existing petitions in the system and deal with them,
for example by approving or rejecting draft petitions, or writing responses to finished
petitions.</p>';

        if ($this->cat_change) { ?>
<form method="post" action="<?=$this->self_link ?>">
<input type="hidden" name="cats" value="1"><input type="hidden" name="o" value="<?=$status ?>">
<p><input type="submit" value="Update all categories">
<a href="<?=$this->self_link ?>;o=<?=$status ?>">Back to normal screen</a></p>
<?      } elseif (OPTION_SITE_NAME == 'number10') {
            print '<p><a href="'.$this->self_link.';o='.$status.';cats=1">Update categories</a></p>';
        }

        $count = db_getOne("SELECT value FROM stats WHERE key = 'petitions_$status' order by id desc limit 1") - 1;

        print '<p>';
        if ($page > 0) {
            print '<a href="'.$this->self_link.';s='.$sort.';o='.$status.';p='.($page-1).'">Previous '.$page_limit.'</a>';
        }
        if ($page > 0 && $page < floor($count/$page_limit)) {
            print ' | ';
        }
        if ($page < floor($count/$page_limit)) {
            print '<a href="'.$this->self_link.';s='.$sort.';o='.$status.';p='.($page+1).'">Next '.$page_limit.'</a>';
        }
        print '</p>';
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

        $sel_query_part = "SELECT petition.*, ";
        if (OPTION_SITE_TYPE == 'multiple') {
            $sel_query_part .= 'body.name as body_name, body.ref as body_ref, ';
        }
        $sel_query_part .= "
                date_trunc('second', laststatuschange) AS laststatuschange,
                date_trunc('second', creationtime) AS creationtime,
                (deadline + interval '1 year' >= ms_current_date()) AS response_possible,
                (SELECT count(*) FROM signer WHERE showname = 't' and petition_id=petition.id AND
                    emailsent = 'confirmed') AS signers_confirmed,
                (SELECT count(*) FROM signer WHERE showname = 't' and petition_id=petition.id AND
                    emailsent = 'sent') AS signers_sent
            FROM petition";
        if (OPTION_SITE_TYPE == 'multiple') {
            $sel_query_part .= ', body WHERE body_id = body.id AND';
        } else {
            $sel_query_part .= ' WHERE';
        }

        $q = db_query("$sel_query_part lower(petition.ref) = ?" . cobrand_admin_site_restriction(), strtolower($petition));
        $pdata = db_fetch_array($q);
        if (!$pdata) {
            printf("Petition '%s' not found", htmlspecialchars($petition));
            return;
        }
        $petition_obj = new Petition($pdata);
#        $petition_obj->render_box(array('showdetails' => true));

        print '<h2>Petition &lsquo;<a href="' . $petition_obj->url_main()
            . '">' . $pdata['ref'] . '</a>&rsquo;';
        if (cobrand_archive_option() && $pdata['archived']) {
            print ' &ndash; Archived';
        }
        print "</h2>";

        # Actions
        print '<div id="petition_admin_actions"> <h2>Petition actions</h2>';
        if (!get_http_var('reject') && ($pdata['status'] == 'draft' || $pdata['status'] == 'resubmitted')) {
            print '
<form name="petition_admin_approve" method="post" action="'.$this->self_link.'">
<p>
<input type="hidden" name="petition_id" value="' . $pdata['id'] . '">
<input type="submit" name="approve" value="Approve">
<input type="submit" name="reject" value="Reject">
</p>
</form>';
        } elseif ($pdata['status'] == 'finished' || $pdata['status'] == 'live') {
            print '<form name="petition_admin_go_respond" method="post" action="'
                . $this->self_link . '"><input type="hidden" name="petition_id" value="' . $pdata['id'] . 
                '">';
            if ($pdata['response_possible'] == 't' && !OPTION_RESPONSE_DISABLED) {
                print '<input type="submit" name="respond" value="Write response">';
            }
            if ($pdata['status'] == 'live')
                print ' <input type="submit" name="redraft" value="Undo approval">';
            elseif (cobrand_archive_option() && !$pdata['archived'])
                print ' <input type="submit" name="archive" value="Archive petition">';
            print ' <input type="submit" name="remove" value="Remove petition">';
            print '</form>';
        } elseif ($pdata['status'] == 'rejected') {
            print '<form name="petition_admin_go_respond" method="post" action="'
                . $this->self_link . '"><input type="hidden" name="petition_id" value="' . $pdata['id'] . 
                '"><input type="submit" name="remove" value="Remove petition">';
            print '</form>';
        }
        if ($pdata['status'] == 'live') {
            print '<form name="petition_admin_change_deadline" method="post" action="' . $this->self_link . '">
<input type="hidden" name="deadline_change" value="1">
<input type="hidden" name="petition_id" value="' . $pdata['id'] . '">
<p>Change deadline: <input type="text" name="deadline" value="';
            print trim(prettify($pdata['deadline']));
            print '">';
            print ' <input type="submit" value="Change">';
            print '</form>';
        }
        if (cobrand_admin_responsible_option()) {
            print '<form method="post" action="' . $this->self_link . '">
<input type="hidden" name="responsible_change" value="1">
<input type="hidden" name="petition_id" value="' . $pdata['id'] . '">
<p>Responsible department: <input type="text" name="responsible" value="';
            print trim(prettify($pdata['responsible']));
            print '">';
            print ' <input type="submit" value="Change">';
            print '</form>';
        }
        if ($wards = cobrand_admin_wards_for_petition()) {
            $rows = db_getAll('select area_id from petition_area where petition_id=?', $pdata['id']);
            $ward_ids = array();
            foreach ($rows as $r) {
                $ward_ids[] = $r['area_id'];
            }
            print '<form method="post" action="' . $this->self_link . '">
<input type="hidden" name="wards_change" value="1">
<input type="hidden" name="petition_id" value="' . $pdata['id'] . '">
<p>If applicable, pick the ward or wards this petition applies to:
<select name="wards[]" multiple size=5 title="-- Pick --">';
            foreach ($wards as $ward) {
                print '<option value="' . $ward['id'] . '"';
                if (in_array($ward['id'], $ward_ids)) print ' selected';
                print '>' . $ward['name'] . '</option>';
            }
            print '</select>';
            print ' <input type="submit" value="Update">';
            print '</form>';
        }

        print '</div>';

        print "<ul><li>Created by: <b>" . htmlspecialchars($pdata['name']) . " &lt;" .  privacy($pdata['email']) . "&gt;</b>, " . $pdata['address'] . ', ' . $pdata['postcode'] . ', ' . $pdata['telephone'];
        if ($pdata['address_type']) {
            print '<li>Address type: ' . $pdata['address_type'];
        }
        if ($pdata['comments']) {
            print '<li>Creator private comments: ' . $pdata['comments'] . '</li>';
        }
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
                . '"><input type="hidden" name="change_rejection_criteria" value="1">For being in the following categories: '
                . $cats_pretty . ' &mdash; <input type="submit" value="Change"></form>';
            print '<li>Extra reason provided by admin: ' . $reason . '</li></ul>';
        }
        print '<li>Deadline: <b>';
        print trim(prettify($pdata['deadline']));
        print '</b> (user entered "' . htmlspecialchars($pdata['rawdeadline']) . '")';
        print '<li>Petition title: <b>' . htmlspecialchars($pdata['content']) . '</b>';
        print '<li>Details of petition: ';
        print $pdata['detail'] ? htmlspecialchars($pdata['detail']) : 'None';
        if (cobrand_display_category()){
            print '<li>Category: ' . htmlspecialchars($petition_obj->data['category']);
        }
        if ($pdata['offline_link']) {
            print '<li>Offline petition link: <a href="' . $pdata['offline_link'] . '">' . $pdata['offline_link'] . '</a></li>';
        }
        if ($pdata['offline_location']) {
            print '<li>Offline petition location: ' . $pdata['offline_location'] . '</li>';
        }
        if ($wards) {
            print '<li>Wards: ';
            $ward_names = array();
            foreach ($ward_ids as $id) {
                $ward_names[] = $wards[$id]['name'];
            }
            print $ward_names ? join(', ', $ward_names) : 'No ward';
        }
        if (cobrand_admin_responsible_option()) {
            print '<li>Responsible: ';
            print $pdata['responsible'] ? $pdata['responsible'] : 'None at present';
        }
        print '</ul>';

        // Admin actions
        print '<h3>Administrator events and notes</h3>';
        print '<p>Here you can review this petition&rsquo;s administration history, and add a note if needed.</p>';
        print '<form name="petition_admin_add_note" method="post" action="'
            . $this->self_link . '"><input type="hidden" name="petition_id" value="' . $pdata['id']
            . '"><label for="add_note">Add note:</label> <input id="add_note" type="text" name="note" value="" size="50">
            <input type="submit" value="Add"></form>';

        $q = db_query('select * from petition_log 
                where petition_id = ? order by order_id', $pdata['id']);

        print '<table cellpadding=4 cellspacing=0 border=0>';
        $n = 0;
        print "<tr><th>Time</th><th>User</th><th>Event/note</th></tr>\n";
        while ($r = db_fetch_array($q)) {
            print "<tr>";
            $n++;
            print "<td>". substr(prettify(substr($r['whenlogged'], 0, 19)), 0, -3) . "</td>";
            print "<td>". ($r['editor'] ? $r['editor'] : "unknown"). "</td>";
            print "<td>". $r['message'] . "</td>";
            print "</tr>\n";
        }
        if ($n == 0) {
            print "<tr><td colspan=\"3\">No events yet.</td></tr>";
        }
        print "</table>";

        if (in_array($pdata['status'], array('live', 'finished'))) {
            // Signers
            print "<h3 id='signers'>Signers (" . $pdata['signers_confirmed']
                . ' confirmed, ' . $pdata['signers_sent'] . " unconfirmed)</h3>";
            print '<form name="petition_admin_offline_signers" method="post" action="' . $this->self_link . '">
<input type="hidden" name="offline_signers_change" value="1">
<input type="hidden" name="petition_id" value="' . $pdata['id'] . '">
<p>Number of offline signatures: ';
            print '<input type="text" name="offline_signers" size=4 value="' . $pdata['offline_signers'] . '">';
            print ' <small>(optional)</small> <input type="submit" value="Update">';
            print '</form>';

            if ($pdata['signers_confirmed'] && cobrand_admin_show_map()) {
?>
<div id="signer_map"></div>
<script>
var map = new OpenLayers.Map("signer_map");
var wms = new OpenLayers.Layer.OSM();
var pois = new OpenLayers.Layer.Text("Signatures", {
    location: "?page=pet&petition=<?=$pdata['ref']?>&locations=1"
});
pois.events.register('loadend', undefined, function(){
    map.zoomToExtent(pois.getDataExtent());
});
map.addLayers([wms, pois]);
var lonLat = new OpenLayers.LonLat( -2, 53.5 ).transform(
    new OpenLayers.Projection("EPSG:4326"), // transform from WGS84
    map.getProjectionObject() // to Spherical Mercator Projection
);
map.setCenter(lonLat, 5);
</script>
<p style="float:right;clear:right;">
<a href="?page=map&amp;ref=<?=$pdata['ref']?>">Larger map, count signatures in a hand-drawn shape</a>
</p>
<?
            }

            $areas = cobrand_admin_areas_of_interest();
            if ($areas && $pdata['signers_confirmed']) {
                print '<div id="signer_areas"> <table><tr><th>Council</th><th>Signatures</th></tr>';
                $summary = db_getAll("select area_id,count(*) as c
                    from signer left join signer_area on signer.id=signer_id
                    where showname='t' and petition_id=? and emailsent = 'confirmed'
                    group by area_id", $pdata['id']);
                $other = 0; $unknown = 0;
                $parents = array(); $children = array();
                foreach ($summary as $area) {
                    $id = $area['area_id'];
                    if (!$id) {
                        $unknown = $area['c'];
                        continue;
                    }
                    if (in_array($id, array_keys($areas))) {
                        if (array_key_exists('parent_area', $areas[$id]) && $areas[$id]['parent_area']) {
                            $children[$areas[$id]['parent_area']][] = $area + array('name' => $areas[$id]['name']);
                        } else {
                            $parents[$id] = $area;
                        }
                        continue;
                    }
                    $area_info = json_decode(file_get_contents("http://mapit.mysociety.org/area/$id"), true);
                    if (in_array($area_info['type'], array('DIS', 'LBO', 'MTD', 'UTA', 'LGD', 'COI'))) {
                        $other += $area['c'];
                        continue;
                    }
                }
                foreach ($parents as $id => $area) {
                    print '<tr><td>' . $areas[$id]['name'] . "</td><td>$area[c]</td></tr>\n";
                    if (!array_key_exists($id, $children)) continue;
                    usort($children[$id], 'sort_by_name');
                    foreach ($children[$id] as $child) {
                        print "<tr><td>&nbsp;&nbsp;$child[name]</td><td>$child[c]</td></tr>\n";
                    }
                }
                if ($other) print '<tr><td><i>Other</i></td><td>' . $other . '</td></tr>';
                if ($unknown) print '<tr><td><i>Unknown</i></td><td>' . $unknown . '</td></tr>';
                print '</table></div>';
            }

            $this->show_signers($petition, $sort, $list_limit, $pdata);
            $this->show_signers($petition, $sort, $list_limit, $pdata, true);
        }
    }

        function show_signers($petition, $sort, $list_limit, $pdata, $removed = false) {
            $query = "SELECT signer.name as signname, signer.email as signemail,
                         date_trunc('second',signtime) AS signtime,
                         signer.id AS signid, emailsent, showname
                       FROM signer
                       WHERE petition_id=? AND emailsent in ('sent', 'confirmed')";
            if ($removed)
                $query .= " AND showname='f'";
            else
                $query .= " AND showname='t'";
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
                    array_push($e, privacy($r['signemail']));
                $e = join("<br>", $e);
                $out[$e] = '<td>';
                if ($r['emailsent'] == 'confirmed') {
                    $out[$e] .= '<input type="checkbox" name="update_signer[]" value="' . $r['signid'] . '"> ';
                    if ($r['showname'] == 't') $out[$e] .= 'Delete';
                    elseif ($r['showname'] == 'f') $out[$e] .= 'Reinstate';
                }
                $out[$e] .= '</td>';
                $out[$e] .= '<td>'.$e.'</td>';
                $out[$e] .= '<td>'.prettify($r['signtime']).'</td>';

            }
            if ($sort == 'e') {
                if (!function_exists('sort_by_domain')) {
                    function sort_by_domain($a, $b) {
                        $aa = stristr($a, '@');
                        $bb = stristr($b, '@');
                        if ($aa==$bb) return 0;
                        return ($aa>$bb) ? 1 : -1;
                    }
                }
                uksort($out, 'sort_by_domain');
            }
            if (count($out)) {
                echo '<form name="petition_admin_signature_';
                if ($removed) echo 'reinstate';
                else echo 'removal';
                echo '" method="post" action="'.$this->self_link.'">';
                echo '<table><tr><td></td>';
                $cols = array('e'=>'Signer', 't'=>'Time');
                foreach ($cols as $s => $col) {
                    print '<th>';
                    if ($sort != $s) print '<a href="'.$this->self_link.'&amp;petition='.$petition.'&amp;s='.$s.'#signers">';
                    print $col;
                    if ($sort != $s) print '</a>';
                    print '</th>';
                }
                print '</tr>';
                $a = 0;
                foreach ($out as $row) {
                    print '<tr'.($a++%2==0?' class="v"':'').'>';
                    print $row;
                    print '</tr>';
                }
                print '</table>';
                if ($removed)
                    echo '<p><input type="hidden" name="reinstate_all" value="1">
<input type="submit" value="Reinstate all ticked"></p></form>';
                else
                    echo '<p><input type="hidden" name="delete_all" value="1">
<input type="submit" value="Remove all ticked"></p></form>';
                if ($list_limit && $c >= $list_limit) {
                    print "<p>... only $list_limit signers shown, "; 
                    print '<a href="'.$this->self_link.'&amp;petition='.$petition.'&amp;l=-1">show all</a>';
                    print '</p>';
                }
            } elseif (!$removed) {
                print '<p>Nobody has signed up to this petition.</p>';
            }
        }

    function display_categories($current = 0) {
        foreach (cobrand_admin_rejection_categories() as $n => $category) {
            print '<br><input type="checkbox" name="rejection_cats[]"';
            if ($current & $n)
                print ' checked';
            print ' value="' . $n;
            print '" id="cat' . $n . '"> <label for="cat' . $n . '">';
            print $category . '</label>';
        }
    }

    function forward($petition_id) {
        $p = new Petition($petition_id);
        $status = $p->status();
        $from_body = $p->body_name();

        if ($status != 'draft' && $status != 'resubmitted') {
            $p->log_event("Bad forwarding");
            db_commit();
            print '<p><em>That petition appears to already have been dealt with.</em></p>';
            return;
        }

        $reason = get_http_var('reason');
        $to_body = get_http_var('to_body');
        $errors = array();
        if (!$reason) $errors[] = 'Please give a reason';
        if (!$to_body) $errors[] = 'Please specify which body to forward the petition to';

        if (get_http_var('submit') && !sizeof($errors)) {
            $p->forward($to_body);
            $p->log_event("Admin forwarded petition from $from_body to $to_body. Reason: $reason");
            $vars = array(
                'reason' => $reason,
                'from_body' => $from_body,
            );
            pet_send_message($petition_id, MSG_ADMIN, MSG_CREATOR, 'forwarded', 'petition-forwarded', $vars);
            pet_send_message($petition_id, MSG_ADMIN, MSG_ADMIN, 'forwarded', 'admin-forwarded', $vars);
            db_commit();
            print '<p><em>That petition has been forwarded.</em></p>';
        } else {
            if (get_http_var('submit') && sizeof($errors))
                print '<div id="errors"><ul><li>' .
                    join('</li><li>' , $errors) . '</li></ul></div>';
?>
<form name="petition_admin_forward" action="<?=$this->self_link?>" accept-charset="utf-8" method="post"
onsubmit="this.submit_button.disabled=true">
<input type="hidden" name="forward" value="1">
<input type="hidden" name="submit" value="1">
<input type="hidden" name="petition_id" value="<?=$petition_id ?>">
<p>You are forwarding the <em><?=$p->ref() ?></em> petition to another body for them to approve or reject.
Do this for petitions for which you are not the appropriate body to contact, but another council is
(if the correct body is e.g. a central government department, instead reject this petition with the outside remit criteria).
</p>

<p><label for="to_body">Please pick the body to forward this petition to:</label>
<br><select id="to_body" name="to_body"><option>-- Pick a body --</option>
<?
    $bodies = db_getAll('select ref,name from body');
    foreach ($bodies as $body) {
        if ($body['ref'] == http_auth_user()) continue;
        print '<option value="' . $body['ref'] . '">' . $body['name'] . '</option>';
    }
?>
</select></p>
<p><label for="reason">Please give the reason for forwarding this petition (this will be forwarded to the petition creator to let them know):</label>
<br><textarea id="reason" name="reason" rows="5" cols="40"></textarea></p>
<p><input type="submit" name="submit_button" value="Forward petition"></p>
</form>
<hr>
<?
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

<script type="text/javascript">
function rejection_text(obj) {
    var s = obj.innerHTML;
    var box = document.getElementById('reject_reason');
    box.value = box.value + s;
}
</script>

<table>
<tr valign="top">
<td width="50%">
<p>Reason for rejection (this will be emailed to the creator and available on the website):
<br><textarea name="reject_reason" id="reject_reason" rows="10" cols="50"></textarea></p>

</td><td width="50%">
<p>Select a line below to copy that text to the rejection reason box:</p>
<ul>

<?
        $autotext = cobrand_admin_rejection_snippets();
        foreach ($autotext as $t) {
            echo '<li><a style="cursor:pointer;" onclick="rejection_text(this);">', $t, '</a>';
        }
?>
</ul>
</td></tr></table>

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
            db_query("
                    UPDATE petition
                    SET status = 'rejectedonce',
                        rejection_first_categories = ?,
                        rejection_first_reason = ?,
                        rejection_hidden_parts = ?,
                        laststatuschange = ms_current_timestamp(),
                        lastupdate = ms_current_timestamp()
                    WHERE id=?", $categories, $reason, $hide, $id);
            $p->log_event("Admin rejected petition for the first time. Categories: $cats_pretty. Reasons: $reason");
            $template = 'admin-rejected-once';
            $circumstance = 'rejected-once';
        } elseif ($status == 'resubmitted') {
            db_query("
                    UPDATE petition
                    SET status = 'rejected',
                        rejection_second_categories = ?,
                        rejection_second_reason = ?,
                        rejection_hidden_parts = ?,
                        laststatuschange = ms_current_timestamp(),
                        lastupdate = ms_current_timestamp()
                    WHERE id = ?", $categories, $reason, $hide, $id);
            memcache_update($id);
            stats_change('cached_petitions_rejected', '+1', $p->category_id(), $p->body_ref());
            $p->log_event("Admin rejected petition for the second time. Categories: $cats_pretty. Reason: $reason");
            $template = 'admin-rejected-again';
            $circumstance = 'rejected-again';
        } else {
            $p->log_event("Bad rejection");
            db_commit();
            print '<p><em>That petition appears to already have been dealt with.</em></p>';
            return;
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
            $p->log_event("Bad response state");
            db_commit();
            print '<p><em>You cannot respond to a petition unless it is live or closed</em></p>';
            return;
        }

        if (OPTION_RESPONSE_DISABLED) {
            print '<p><em>Currently petition responses cannot be sent</em></p>';
            return;
        }

        $allowed_responses = cobrand_allowed_responses();
        $n = db_getOne("select id from message where petition_id = ? and circumstance = 'government-response' and circumstance_count = " . ($allowed_responses-1), $petition_id);
        if (!is_null($n)) {
            print '<p><em>You have already sent ' . $allowed_responses . ' responses to this petition!</em></p>';
            return;
        }

        if (OPTION_SITE_NAME == 'number10') {
            $email_subject = sprintf("Government response to petition '%s'", $p->ref());
        } else {
            $email_subject = sprintf("Response to petition '%s'", $p->ref());
        }
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
            $p->log_event("Admin responded to petition");

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
                    $q_html_mail ? 'admin-html' : 'admin',
                    $q_message_subject, $email));
            }
            db_query("UPDATE petition SET lastupdate=ms_current_timestamp()
                where id=?", $p->id());
            memcache_update($p->id());
            db_commit();
            print '<p><em>Your response has been recorded and will be sent out shortly.</em></p>';
        } else {
            if ($q_n > 0) {
                if (sizeof($errors))
                    print '<div id="errors"><ul><li>' . 
                        join('</li><li>' , $errors) . '</li></ul></div>';
                print '<h2>Preview</h2>';
                $out = pet_create_response_email($q_html_mail ? 'html' : 'plain',
                    $p->url_main(), $q_message_subject, $email);
                if ($q_html_mail) {
                    $out = preg_replace('#^.*?<body>#s', '', $out);
                    $out = preg_replace('#</body>.*$#s', '', $out);
                    print '<div style="max-width: 50em;">' . $out . '</div>';
                } else {
                    print "<pre style='margin-left: 50px; padding-left: 5px; border-left: solid 10px #666666;'>$out</pre>";
                }
            }
?>
<p style="font-size: 150%">You are responding to the petition '<?=$p->ref() ?>'.
This response will be sent to <strong>all signers</strong>, not just the creator,
and <strong>will be displayed on the website</strong>.
To email the creator, you can directly email <a href="mailto:<?=privacy($p->creator_email())?>"><?=privacy($p->creator_email())?></a>.</p>

<?          if (cobrand_admin_allow_html_response()) { ?>
<p>To do links in an HTML mail, write them as e.g. <kbd>[http://www.culture.gov.uk/ Department of Culture]</kbd>.</p>
<?          } ?>

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
<?          if (cobrand_admin_allow_html_response()) { ?>
<p><input type="checkbox" name="html_mail" value="1"<?=($q_html_mail?' checked':'')?>> Send as an HTML email?</p>
<?          } ?>
<input type="submit" name="respond" value="Preview">
<?          if ($q_n > 0 && !sizeof($errors)) { ?>
<input type="submit" name="submit" value="Send">
<?          } ?>
</form>
<hr>
<?
        }
    }

    # Admin function to archive a petition
    function archive($petition_id) {
        $p = new Petition($petition_id);

        $status = $p->status();
        if ($status != 'finished') {
            $p->log_event("Bad response state");
            db_commit();
            print '<p><em>You cannot archive a petition unless it is closed</em></p>';
            return;
        }

        $p->log_event("Admin archived petition");
        db_query("UPDATE petition SET archived=ms_current_timestamp(), lastupdate=ms_current_timestamp()
            where id=?", $p->id());
        stats_change('cached_petitions_finished', '-1', $p->category_id(), $p->body_ref());
        stats_change('cached_petitions_archived', '+1', $p->category_id(), $p->body_ref());
        db_commit();
        print '<p><em>That petition has been archived.</em></p>';
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
            db_query('update petition set deadline=?, lastupdate = ms_current_timestamp()
                where id=?', $new_deadline['iso'], $petition_id);
            memcache_update($petition_id);
            $p->log_event("Admin altered deadline of petition from $current_deadline to $new_deadline[iso]");
            db_commit();
            print '<p><em>Deadline updated</em></p>';
        }
    }

    # Admin function to change the wards associated with a petition
    function change_wards($petition_id) {
        $new_wards = get_http_var('wards');
        $wards = cobrand_admin_wards_for_petition();
        db_query('delete from petition_area where petition_id = ?', $petition_id);
        $ward_names = array();
        foreach ($new_wards as $ward) {
            if (!array_key_exists($ward, $wards)) continue;
            $ward_names[] = $wards[$ward]['name'];
            db_query('insert into petition_area (petition_id, area_id) values (?, ?)', $petition_id, $ward);
        }
        $p = new Petition($petition_id);
        $p->log_event("Admin updated wards to " . join(', ', $ward_names));
        db_commit();
        print '<p><em>Wards updated</em></p>';
    }

    # Admin function to change the thing currently responsible for a petition
    function change_responsible($petition_id) {
        $new_resp = get_http_var('responsible');
        db_query('update petition set responsible=?, lastupdate = ms_current_timestamp()
            where id=?', $new_resp, $petition_id);
        $p = new Petition($petition_id);
        $p->log_event("Admin updated responsible to $new_resp");
        db_commit();
        print '<p><em>Responsible field updated</em></p>';
    }

    # Admin function to update the number of offline signers a petition has
    # Can be both 0 and blank - 0 would imply there was an offline version
    # and it got no signatures, blank would mean no offline version.
    function offline_signers($petition_id) {
        $p = new Petition($petition_id);

        $new = get_http_var('offline_signers');
        if ($new === '') $new = null;
        $old = $p->data['offline_signers'];
        if (is_null($old)) $old = 'n/a';

        $error = null;
        if ($new && !ctype_digit($new))
            $error = 'Please enter a number of signatures';

        if ($error) {
            print "<p><em>$error</em></p>";
        } else {
            db_query('update petition set offline_signers=?, lastupdate = ms_current_timestamp()
                where id=?', $new, $petition_id);
            memcache_update($petition_id);
            if ($new === null) $new = 'n/a';
            $p->log_event("Admin altered number of offline signatures from $old to $new");
            db_commit();
            print '<p><em>Number of offline signatures updated</em></p>';
        }
    }

    # Approve a petition. Note the approval might have been a few days later,
    # so take account of that and calculate a new deadline
    function approve($petition_id) {
        $p = new Petition($petition_id);
        $status = $p->status();
        if ($status == 'draft' || $status == 'resubmitted') {
            db_query("UPDATE petition
                SET status='live',
                deadline=deadline+(ms_current_date()-date_trunc('day', laststatuschange)),
                rejection_hidden_parts = 0,
                laststatuschange = ms_current_timestamp(), lastupdate = ms_current_timestamp()
                WHERE id=?", $petition_id);
            memcache_update($petition_id);
            stats_change('cached_petitions_live', '+1', $p->category_id(), $p->body_ref());
            $p->log_event("Admin approved petition");
        } else {
            $p->log_event("Bad approval");
            db_commit();
            print '<p><em>That petition appears to have already been dealt with.</em></p>';
            return;
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
                $action = 'removed';
                $new_status = 'sentconfirm';
                $message = 'That petition has been removed from the site';
            } elseif ($type == 'redraft') {
                $action = 'redrafted';
                $new_status ='draft';
                db_query('delete from signer where petition_id=?', $p->id());
                db_query('update petition set cached_signers=1 where id=?', $p->id());
                $message = 'That petition has been moved back into the draft state';
            }
            $p->log_event("Admin $action petition with reason '$reason'");
            db_query("update petition set status='$new_status', laststatuschange=ms_current_timestamp(),
                lastupdate=ms_current_timestamp() where id=?", $p->id());
            stats_change("cached_petitions_$status", '-1', $p->category_id(), $p->body_ref());
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
This will remove the petition from the site, and it can then be
rejected through the admin interface as normal.
Only use this if a petition has been approved by mistake.</p>
<p style="font-size:150%"><strong>All current signatories will be deleted.</strong></p>
<p><label for="reason">Please give the reason for moving this petition back to draft, for audit purposes:</label>
<br><textarea id="reason" name="reason" rows="5" cols="40"></textarea></p>
<input type="submit" name="submit" value="Move petition">
<? } elseif ($type == 'remove') { ?>
<p>You are removing the <em><?=$p->ref() ?></em> petition from the petition site.
<br>This should only be done if the petition creator has asked for it to be
removed.
<br>Otherwise, use the "Undo approval" button, so that the petition
can be rejected properly.</p>
<p><label for="reason">Please give the reason for removing this petition, for audit purposes. For example, provide a copy of the request email from the petition creator:</label>
<br><textarea id="reason" name="reason" rows="10" cols="40"></textarea></p>
<input type="submit" name="submit_button" value="Remove petition">
<? } ?>
</form>
<hr>
<?
        }
    }

    function change_rejection_criteria($petition_id) {
        $p = new Petition($petition_id);
        $status = $p->status();
        if ($status != 'rejected' && $status != 'rejectedonce') {
            $p->log_event('Changing criteria of non-rejected petition');
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
            $criteria_pretty = prettify_categories($criteria, false);
            $criteria_new_pretty = prettify_categories($criteria_new, false);
            db_query("UPDATE petition SET $column=?, lastupdate=ms_current_timestamp()
                where id=?", $criteria_new, $petition_id);
            memcache_update($petition_id);
            $p->log_event("Admin changed rejection criteria from [$criteria_pretty] to [$criteria_new_pretty], reason '$reason'");
            db_commit();
            print '<p><em>Petition criteria changed</em></p>';
        } else {
            if (get_http_var('submit') && sizeof($errors))
                print '<div id="errors"><ul><li>' . 
                    join('</li><li>' , $errors) . '</li></ul></div>';
?>
<form method="post" name="admin_change_rejection_criteria" action="<?=$this->self_link?>">
<input type="hidden" name="submit" value="1">
<input type="hidden" name="change_rejection_criteria" value="1">
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

    function add_note($petition_id, $note) {
        $p = new Petition($petition_id);
        $p->log_event($note);
        db_commit();
    }

    function display() {
        db_connect();

        if (get_http_var('locations')) {
            # Yucky to stop it outputting admin header/footers
            while (ob_get_level()) ob_end_clean();
            ob_start('ob_callback');
            print "lat\tlon\n";
            $rows = db_getAll("select latitude, longitude from signer
                where latitude!=0 and emailsent='confirmed' and showname='t'
                and petition_id=(select id from petition where ref=?)", get_http_var('petition'));
            foreach ($rows as $s) {
                print "$s[latitude]\t$s[longitude]\n";
            }
            exit;
        }

        $status = get_http_var('o');
        if ($status && !preg_match('#^(draft|live|rejected|finished|archived)$#', $status)) $status = 'draft';
        if (!count($_POST) && count($_GET) == 1) $status = 'draft'; # Main page for this section, no queries
        petition_admin_navigation($this, array('status'=>$status));

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
        } elseif (get_http_var('archive')) {
            $this->archive($petition_id);
        } elseif (get_http_var('deadline_change')) {
            $this->change_deadline($petition_id);
        } elseif (get_http_var('wards_change')) {
            $this->change_wards($petition_id);
        } elseif (get_http_var('responsible_change')) {
            $this->change_responsible($petition_id);
        } elseif (get_http_var('offline_signers_change')) {
            $this->offline_signers($petition_id);
        } elseif (get_http_var('redraft')) {
            $this->redraft($petition_id, 'redraft');
        } elseif (get_http_var('remove')) {
            $this->redraft($petition_id, 'remove');
        } elseif (get_http_var('forward')) {
            $this->forward($petition_id);
            $petition_id = null; $petition = null;
        } elseif (get_http_var('change_rejection_criteria')) {
            $this->change_rejection_criteria($petition_id);
        } elseif (get_http_var('note')) {
            $this->add_note($petition_id, get_http_var('note'));
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

class ADMIN_PAGE_PET_MAP {
    function ADMIN_PAGE_PET_MAP () {
        $this->id = "map";
        $this->noindex = true;
        $this->navname = "Petition signer map";
    }

    function display() {
        $ref = htmlspecialchars(get_http_var('ref'));
?>
<h2>Petition <?=$ref?> signer map</h2>

<p>Pan and zoom to the appropriate bit of map. Select the pencil icon and then
click points to draw a polygon, double clicking to finish. Alternatively, hold
down shift and free draw a polygon. You can modify an existing shape using the
arrowed modify tool.</p>

<div id="signer_map_large"></div>

<style>
    /* Fix a bug in OpenLayers CSS, looks like */
    .olControlEditingToolbar .olControlModifyFeatureItemInactive {
        background-position: -1px 0px;
    }
    .olControlEditingToolbar .olControlModifyFeatureItemActive {
        background-position: -1px -23px;
    }
</style>
<script>
var map = new OpenLayers.Map("signer_map_large");
var polygonLayer = new OpenLayers.Layer.Vector("Polygon Layer");
var wms = new OpenLayers.Layer.OSM();
var pois = new OpenLayers.Layer.Text("Signatures", {
    location: "?page=pet&petition=<?=$ref?>&locations=1"
});
pois.events.register('loadend', undefined, function(){
    map.zoomToExtent(pois.getDataExtent());
});
map.addLayers([wms, pois, polygonLayer]);

var panel = new OpenLayers.Control.Panel({ displayClass: "olControlEditingToolbar" });
panel.addControls([
    new OpenLayers.Control.Navigation({ title: "Navigate" }),
    new OpenLayers.Control.DrawFeature(
        polygonLayer, OpenLayers.Handler.Polygon, {
            displayClass: "olControlDrawFeaturePoint",
            title: "Draw polygon",
            handlerOptions: { holeModifier: "altKey" }, // Not until 2.11
            featureAdded: count_signatures
        }
    ),
    new OpenLayers.Control.ModifyFeature(
        polygonLayer, {
            displayClass: "olControlModifyFeature",
            title: "Alter polygon"
        }
    )
]);
map.addControl(panel);

var lonLat = new OpenLayers.LonLat( -2, 53.5 ).transform(
    new OpenLayers.Projection("EPSG:4326"), // transform from WGS84
    map.getProjectionObject() // to Spherical Mercator Projection
);
map.setCenter(lonLat, 5);

function count_signatures(poly) {
    // When called from afterfeaturemodified event, the polygon is not directly there.
    if (!poly.CLASS_NAME) poly = poly.feature;
    var inside = 0;
    for (var i = 0; i < pois.features.length; i++) {
        var loc = pois.features[i].lonlat;
        var point = new OpenLayers.Geometry.Point( loc.lon, loc.lat );
        if (poly.geometry.intersects(point))
            inside++;
    }
    if (inside == 1)
        alert("There is one point within that polygon.");
    else
        alert("There are " + inside + " points within that polygon.");
}
polygonLayer.events.register('afterfeaturemodified', undefined, count_signatures);

</script>
<?
    }
}

function petition_admin_perform_actions() {
    $petition_id = null;
    if (get_http_var('delete_all') || get_http_var('confirm_all') || get_http_var('reinstate_all')) {
        $ids = (array)get_http_var('update_signer');
        $sigs_by_petition = array();
        $clean_ids = array();
        foreach ($ids as $signer_id) {
            if (!$signer_id || !ctype_digit($signer_id)) continue;
            $clean_ids[] = $signer_id;
            $petition_id = db_getOne("SELECT petition_id FROM signer WHERE id = $signer_id");
            $sigs_by_petition[$petition_id][] = $signer_id;
        }
        $ids = $clean_ids;
        if (count($ids)) {
            if (get_http_var('delete_all')) {
                db_query('UPDATE signer set showname = false where id in (' . join(',', $ids) . ')');
                $change = '-';
                $log = 'Admin hid signers ';
                print '<p><em>Those signers have been removed.</em></p>';
            } elseif (get_http_var('confirm_all')) {
                db_query("UPDATE signer set emailsent = 'confirmed' where id in (" . join(',', $ids) . ')');
                $change = '+';
                $log = 'Admin confirmed signers ';
                print '<p><em>Those signers have been confirmed.</em></p>';
            } elseif (get_http_var('reinstate_all')) {
                db_query("UPDATE signer set showname = true where id in (" . join(',', $ids) . ')');
                $change = '+';
                $log = 'Admin reinstated signers ';
                print '<p><em>Those signers have been reinstated.</em></p>';
            }
        }
        foreach ($sigs_by_petition as $petition_id => $sigs) {
            db_query("update petition set cached_signers = cached_signers $change " . count($sigs) . ',
                lastupdate = ms_current_timestamp() where id = ?', $petition_id);
            memcache_update($petition_id);
            $p = new Petition($petition_id);
            $p->log_event($log . join(',', $sigs));
        }
        db_commit();
    }

    if (get_http_var('confirm_petition_id')) {
        $petition_id = get_http_var('confirm_petition_id');
        if (ctype_digit($petition_id)) {
            db_query("UPDATE petition set status = 'draft' where id = ?", $petition_id);
            $p = new Petition($petition_id);
            $p->log_event('Admin confirmed petition ' . $petition_id);
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

function petition_admin_navigation($page, $array = array()) {
    $status = isset($array['status']) ? $array['status'] : '';
    # $found = isset($array['found']) ? $array['found'] : 0;
    $search = isset($array['search']) ? $array['search'] : '';
    print '<div id="admin_nav">';
    petition_admin_search_form($search);
    print "<p>View petitions: ";
    $statuses = array(
        'draft' => 'Draft',
        'live' => 'Live',
        'finished' => 'Finished',
        'rejected' => 'Rejected',
    );
    if (cobrand_archive_option()) {
        $statuses = array(
            'draft' => 'Draft',
            'live' => 'Open',
            'finished' => 'Closed - being considered',
            'archived' => 'Closed - no further action',
            'rejected' => 'Rejected',
        );
    }
    $c = 0;
    foreach ($statuses as $k => $v) {
        if ($c++) print ' / ';
        if ($status == $k) print '<strong>' . $v . '</strong>';
        else print '<a href="?page=pet&amp;o=' . $k . '">' . $v . '</a>';
    }
    print "<br><a href='?page=offline'>Create offline petition</a> &middot; <a href='?page=stats'>Statistics</a></p>";
    print '</div>';
    $h_level = (OPTION_SITE_NAME == 'sbdc' || OPTION_SITE_NAME == 'sbdc1') ? 2 : 1;
    print "<h$h_level>Admin interface";
    if ($page->navname != 'Admin interface') print ": $page->navname";
    print "</h$h_level>";
}

function petition_admin_search_form($search='') { ?>
<form name="petition_admin_search" method="get" action="./">
<input type="hidden" name="page" value="petsearch">
Search for user&rsquo;s name/email, or petition reference: <input type="text" name="search" value="<?=htmlspecialchars($search) ?>" size="30">
<input type="submit" value="Search">
</form>
<?
}

function privacy($e) {
    if (OPTION_ADMIN_PUBLIC) return '<em>hidden in public interface</em>';
    return htmlspecialchars($e);
}

$memcache = null;
function memcache_update($id) {
    global $memcache;
    if (!$memcache) {
        $memcache = new Memcache;
        $memcache->connect('localhost', 11211);
    }
    $memcache->set(OPTION_PET_DB_NAME . 'lastupdate:' . $id, time());
}

function sort_by_name($a, $b) {
    $aa = $a['name'];
    $bb = $b['name'];
    if ($aa==$bb) return 0;
    return ($aa>$bb) ? 1 : -1;
}

