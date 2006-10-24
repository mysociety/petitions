<?
// all.php:
// List all petitions.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: list.php,v 1.16 2006-10-24 10:30:26 matthew Exp $

require_once "../phplib/pet.php";
require_once '../phplib/fns.php';
require_once '../phplib/petition.php';
require_once '../../phplib/db.php';
require_once '../../phplib/importparams.php';

define('PAGE_SIZE', 50);

$err = importparams(
            array('offset', '/^(0|[1-9]\d*)$/', '', 0),
            array('sort', '/^(content|deadline|name|ref|creationtime)\/?$/', '', 'default'),
            array('type', '/^[a-z_]*$/', '', 'open')
        );
if ($err) {
    err(_('Illegal offset or sort parameter passed'));
}

$rss = get_http_var('rss') ? true : false;

// Strip any trailing '/'.
$original_sort = preg_replace("#/$#", "", $q_sort);
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
if ($q_sort == "default") $q_sort = "creationtime";

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
if ($q_sort == 'creationtime' || $q_sort == 'created' || $q_sort == 'whensucceeded') {
    $sort_phrase .= " DESC";
}
$sql_params[] = PAGE_SIZE;
$qrows = db_query("
        SELECT petition.*, '$pet_today' <= petition.deadline AS open,
            (SELECT count(*) FROM signer
                WHERE showname and signer.petition_id = petition.id
                    and signer.emailsent = 'confirmed') AS signers
            FROM petition
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

    $viewsarray = array('open'=>'Open petitions', 'closed' => 'Closed petitions',
        'rejected' => 'Rejected petitions');
    $views = "";
    $b = false;
    foreach ($viewsarray as $s => $desc) {
        if ($b) $views .= ' &nbsp; ';
        if ($q_type == $s)
            $views .= '<span>' . $desc . '</span>';
        else
            $views .= "<a href=\"/list/$s\">$desc</a>";
        $b = true;
    }

    $sort = ($q_sort) ? '&amp;sort=' . $q_sort : '';
    $off = ($q_offset) ? '&amp;offset=' . $q_offset : '';
    $prev = '<span class="greyed">Previous page</span>'; $next = '<span class="greyed">Next page</span>';
    if ($q_offset > 0) {
        $n = $q_offset - PAGE_SIZE;
        if ($n < 0) $n = 0;
        $prev = "<a href=\"?offset=$n$sort\">Previous page</a>";
    }
    if ($q_offset + PAGE_SIZE < $ntotal) {
        $n = $q_offset + PAGE_SIZE;
        $next = "<a href=\"?offset=$n$sort\">Next page</a>";
    }
    $navlinks = '<p id="petition_view_tabs">' . $views . "</p>\n";
    if ($ntotal > 0) {
        $navlinks .= '<p align="center" style="font-size: 89%">' . _('Sort by'). ': ';
        $arr = array(
                     'creationtime'=>_('Start date'), 
                     'deadline'=>_('Deadline'), 
                     );
        # Removed as not useful (search is better for these): 'ref'=>'Short name',
        # 'title'=>'Title', 'name'=>'Creator'
        $b = false;
        foreach ($arr as $s => $desc) {
            if ($b) $navlinks .= ' | ';
            if ($q_sort != $s) $navlinks .= "<a href=\"?sort=$s$off\">$desc</a>"; else $navlinks .= $desc;
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
<th>Submitted&nbsp;by</th>
<th>Deadline&nbsp;to&nbsp;sign&nbsp;by</th>
<?      if ($q_type != 'rejected') { ?>
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
        } elseif ($petition->rejected_show_nothing()) {
            print '<tr';
            if ($c%2) print ' class="a"';
            print '><td colspan="4">Petition details cannot be shown &mdash; <a href="/reject?id=' . $petition->id(). '">more details</a></td></tr>';
        } else {
            print '<tr';
            if ($c%2) print ' class="a"';
            print '><td><a href="/' . $petition->ref() . '">';
            print $petition->h_content() . '</a></td>';
            print '<td>' . $petition->h_name() . '</td>';
            print '<td>' . $petition->h_pretty_deadline() . '</td>';
            if ($q_type != 'rejected')
                print '<td>' . $petition->signers() . '</td>';
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
