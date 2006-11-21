<?
// all.php:
// List all petitions.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: list.php,v 1.30 2006-11-21 13:31:12 matthew Exp $

require_once "../phplib/pet.php";
require_once '../phplib/fns.php';
require_once '../phplib/petition.php';
require_once '../../phplib/db.php';
require_once '../../phplib/importparams.php';

define('PAGE_SIZE', 50);

$err = importparams(
            array('offset', '/^(0|[1-9]\d*)$/', '', 0),
            array('sort', '/^(content|deadline|name|signers|ref|creationtime|laststatuschange)\/?$/', '', 'default'),
            array('type', '/^[a-z_]*$/', '', 'open')
        );
if ($err) {
    err(_('Illegal offset or sort parameter passed'));
}

$rss = get_http_var('rss') ? true : false;

// Strip any trailing '/'.
$q_sort = preg_replace("#/$#", "", $q_sort);
if ($q_type == 'closed') {
    $open = '<';
    $status = 'finished';
} elseif ($q_type == 'rejected') {
    $open = null;
    $status = 'rejected';
} else {
    $open = '>=';
    $status = 'live';
}
if ($q_sort == "default") {
    $q_sort = $rss ? 'laststatuschange' : 'signers';
}
if ($q_sort == "creationtime") $q_sort = "laststatuschange";

$sql_params = array($status);
$query = "SELECT count(petition.id) FROM petition
                WHERE status = ? " .
                ($open ? " AND deadline $open '$pet_today' " : "");
$ntotal = db_getOne($query , $sql_params);
if ($ntotal < $q_offset) {
    $q_offset = $ntotal - PAGE_SIZE;
    if ($q_offset < 0)
        $q_offset = 0;
}

$sort_phrase = $q_sort;
if ($q_sort == 'laststatuschange' || $q_sort == 'signers') {
    $sort_phrase .= " DESC";
}
$sql_params[] = PAGE_SIZE;
$qrows = db_query("
        SELECT petition.*, '$pet_today' <= petition.deadline AS open,
            (SELECT count(*)+1 FROM signer
                WHERE showname and signer.petition_id = petition.id
                    and signer.emailsent = 'confirmed') AS signers,
                message.id as message_id
            FROM petition
            left join message on petition.id = message.petition_id and circumstance = 'government-response'
            WHERE status = ?".
            ($open ? " AND deadline $open '$pet_today' " : ""). 
           "ORDER BY $sort_phrase,petition.id LIMIT ? OFFSET $q_offset", $sql_params);
/* PG bug: mustn't quote parameter of offset */

if ($q_type == 'open') {
    $heading = "Open petitions";
    if ($rss)
        $heading = 'New Petitions';
} elseif ($q_type == 'closed') {
    $heading = "Closed petitions";
} elseif ($q_type == 'rejected') {
    $heading = "Rejected petitions";
} else {
    err('Unknown type ' . $q_type);
}
if ($rss) 
    rss_header($heading, $heading, array());
else {
    page_header($heading, array('id'=>'all',
            'rss'=> array(
                    $heading => '/rss' . $_SERVER['REQUEST_URI']
                    ),
    ));
}

if (!$rss) {
?>
<h1><span dir="ltr">E-Petitions</span></h1>
<?
#    print "<h2>$heading</h2>";

    $qs_sort = ($q_sort && $q_sort != 'signers') ? 'sort=' . $q_sort : '';
    $qs_off = ($q_offset) ? 'offset=' . $q_offset : '';

    $viewsarray = array('open'=>'Open petitions', 'closed' => 'Closed petitions',
        'rejected' => 'Rejected petitions');
    $views = '';
    $b = false;
    foreach ($viewsarray as $s => $desc) {
        if ($b) $views .= ' &nbsp; ';
        if ($q_type == $s)
            $views .= '<span>' . $desc . '</span>';
        else
            $views .= "<a href=\"/list/$s" . ($qs_sort ? "?$qs_sort" : '') . "\">$desc</a>";
        $b = true;
    }

    pet_search_form();

    $prev = '<span class="greyed">Previous page</span>'; $next = '<span class="greyed">Next page</span>';
    if ($q_offset > 0) {
        $n = $q_offset - PAGE_SIZE;
        if ($n < 0) $n = 0;
        $prev = "<a href=\"?offset=$n" . ($qs_sort ? "&amp;$qs_sort" : '') . '">Previous page</a>';
    }
    if ($q_offset + PAGE_SIZE < $ntotal) {
        $n = $q_offset + PAGE_SIZE;
        $next = "<a href=\"?offset=$n" . ($qs_sort ? "&amp;$qs_sort" : '') . '">Next page</a>';
    }
    $navlinks = '<p id="petition_view_tabs">' . $views . "</p>\n";
    if ($ntotal > 0) {
        $navlinks .= '<p align="center" style="font-size: 89%">' . _('Sort by'). ': ';
        $arr = array(
                     'laststatuschange'=>_('Start date'), 
                     'deadline'=>_('Deadline'), 
	);
	if ($status != 'rejected') {
		$arr['signers'] = _('Signatures');
	}
        # Removed as not useful (search is better for these): 'ref'=>'Short name',
        # 'title'=>'Title', 'name'=>'Creator'
        $b = false;
        foreach ($arr as $s => $desc) {
            if ($b) $navlinks .= ' | ';
	    $qs = array();
	    if ($s != 'signers') $qs[] = "sort=$s";
	    if ($qs_off) $qs[] = $qs_off;
	    $qs = join('&amp;', $qs);
            if ($q_sort != $s) $navlinks .= "<a href=\"?$qs\">$desc</a>"; else $navlinks .= $desc;
            $b = true;
        }
        $navlinks .= '</p> <p align="center">';
        $navlinks .= $prev . ' | '._('Petitions'). ' ' . ($q_offset + 1) . ' &ndash; ' . 
            ($q_offset + PAGE_SIZE > $ntotal ? $ntotal : $q_offset + PAGE_SIZE) . ' of ' .
            $ntotal . ' | ' . $next;
        $navlinks .= '</p>';
    }
    print $navlinks;
}

$rss_items = array();
if ($ntotal > 0) {
    $c = 1;
    if (!$rss) { ?>
<table cellpadding="3" cellspacing="0" border="0">
<tr><th align="left">We the undersigned petition the Prime Minister to&hellip;</th>
<th>Submitted by</th>
<?      if ($q_type != 'rejected') { ?>
<th>Deadline to sign by</th>
<th>Signatures</th>
<?      } ?>
</tr>
<?  }
    while ($row = db_fetch_array($qrows)) {
        $petition = new Petition($row);
        #$arr = array('class'=>"petition-".$c%2, 'href' => $petition->url_main() );
        #if ($q_type == 'succeeded_closed' || $q_type == 'failed') $arr['closed'] = true;
        if ($rss) {
            $rss_items[] = $petition->rss_entry();
        } else {
            print '<tr';
            if ($c%2) print ' class="a"';
            print '><td>';
            if (!$petition->rejected_show_part('content'))
                print 'Petition details cannot be shown &mdash; ';
            print '<a href="/';
            print $petition->rejected_show_part('ref') ? $petition->ref() : 'reject?id=' . $petition->id();
            print '">';
            if ($petition->rejected_show_part('content')) {
                print $petition->h_content();
            } else {
                print 'more details';
            }
            print '</a>';
            if ($q_type == 'closed' && $petition->data['message_id']) {
                print '<br />(with government response)';
            }
            print '</td><td>' . $petition->h_name() . '</td>';
            if ($q_type != 'rejected') {
                print '<td>' . $petition->h_pretty_deadline() . '</td>';
                print '<td>' . $petition->signers() . '</td>';
            }
            print '</tr>';
            # $petition->h_display_box($arr);
        }
        $c++;
    }
    if (!$rss)
        print '</table>';
    if (!$rss && $ntotal > PAGE_SIZE)
        print "<br style=\"clear: both;\">$navlinks";
} else {
    if (!$rss)
        print '<p>There are currently none.</p>';
}

if ($rss)
    rss_footer($rss_items);
else {
?>
<p align="right"><a href="/rss<?=$_SERVER['REQUEST_URI'] ?>"><img class="noborder" src="/images/rss-icon.gif" alt="<?=_('RSS feed of ') . $heading ?>" /> RSS</a>
| <a href="http://news.bbc.co.uk/1/hi/help/3223484.stm">What is RSS?</a></p>
<?
    page_footer('List.' . $q_type);
}
?>
