<?
// all.php:
// List all pledges.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: list.php,v 1.1 2006-06-27 22:40:29 matthew Exp $

require_once "../phplib/pet.php";
require_once '../phplib/fns.php';
require_once '../phplib/petition.php';
require_once '../../phplib/db.php';
require_once '../../phplib/importparams.php';

define('PAGE_SIZE', 50);

$err = importparams(
            array('offset', '/^(0|[1-9]\d*)$/', '', 0),
            array('sort', '/^(title|date|name|ref|creationtime)\/?$/', '', 'default'),
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
    $status = 'live';
    if ($q_sort == "default") $q_sort = "creationtime";
} elseif ($q_type == 'rejected') {
    $open = null;
    $status = 'rejected';
    if ($q_sort == "default") $q_sort = "creationtime";
} else {
    $open = '>=';
    $status = 'live';
    if ($q_sort == "default") $q_sort = $rss ? "creationtime" : "creationtime";
}

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
            (SELECT count(*) FROM signer WHERE signer.petition_id = petition.id) AS signers,
            person.email AS email
            FROM petition
            LEFT JOIN person ON person.id = petition.person_id
            WHERE status = ?".
            ($open ? " AND deadline $open '$pet_today' " : ""). 
           "ORDER BY $sort_phrase,petition.id LIMIT ? OFFSET $q_offset", $sql_params);
/* PG bug: mustn't quote parameter of offset */

if ($q_type == 'open') {
    $heading = _("Open petitions");
    if ($rss)
        $heading = _('New Petitions');
} elseif ($q_type == 'closed') {
    $heading = _("Closed petitions");
} elseif ($q_type == 'rejected') {
    $heading = _("Rejected petitions");
} else {
    err('Unknown type ' . $q_type);
}
if ($rss) 
    rss_header($heading, $heading, array());
else {
    page_header($heading, array('id'=>'all',
            'rss'=> array(
#                    $heading => pb_domain_url(array('explicit'=>true, 'path'=>'/rss'.$_SERVER['REQUEST_URI']))
                    ),
    ));
}

if (!$rss) {
?><a href="<? #pb_domain_url(array('explicit'=>true, 'path'=>"/rss".$_SERVER['REQUEST_URI']))?>"><img align="right" border="0" src="/rss.gif" alt="<?=_('RSS feed of ') . $heading ?>"></a><?
    print "<h2>$heading</h2>";

    #pb_print_filter_link_main_general('align="center"');

    $viewsarray = array('open'=>_('Open petitions'), 'closed' => _('Closed petitions'),
        'rejected' => 'Rejected petitions');
    $views = "";
    $b = false;
    foreach ($viewsarray as $s => $desc) {
        if ($b) $views .= ' | ';
        if ($q_type != $s) $views .= "<a href=\"/list/$s\">$desc</a>"; else $views .= $desc;
	$b = true;
    }

    $sort = ($q_sort) ? '&amp;sort=' . $q_sort : '';
    $off = ($q_offset) ? '&amp;offset=' . $q_offset : '';
    $prev = '<span class="greyed">&laquo; '._('Previous page').'</span>'; $next = '<span class="greyed">'._('Next page').' &raquo;</span>';
    if ($q_offset > 0) {
        $n = $q_offset - PAGE_SIZE;
        if ($n < 0) $n = 0;
        $prev = "<a href=\"?offset=$n$sort\">&laquo; "._('Previous page')."</a>";
    }
    if ($q_offset + PAGE_SIZE < $ntotal) {
        $n = $q_offset + PAGE_SIZE;
        $next = "<a href=\"?offset=$n$sort\">"._('Next page')." &raquo;</a>";
    }
    $navlinks = '<p align="center">' . $views . "</p>\n";
    if ($ntotal > 0) {
        $navlinks .= '<p align="center" style="font-size: 89%">' . _('Sort by'). ': ';
        $arr = array(
                     'creationtime'=>_('Start date'), 
                     'date'=>_('Deadline'), 
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
        $navlinks .= $prev . ' | '._('Pledges'). ' ' . ($q_offset + 1) . ' &ndash; ' . 
            ($q_offset + PAGE_SIZE > $ntotal ? $ntotal : $q_offset + PAGE_SIZE) . ' of ' .
            $ntotal . ' | ' . $next;
        $navlinks .= '</p>';
    }
    print $navlinks;
}

$rss_items = array();
if ($ntotal > 0) {
    $c = 0;
    $lastcategory = 'none';
    while ($row = db_fetch_array($qrows)) {
        $pledge = new Petition($row);
        if ($q_sort == "category") {
            $categories = $pledge->categories();
            $thiscategory = array_pop($categories);
            if ($thiscategory == null) 
                $thiscategory = _("Miscellaneous");
            if ($lastcategory <> $thiscategory) {
                if (!$rss)
                    print "<h2 style=\"clear:both\">"._($thiscategory)."</h2>";
                $c = 0;
                $lastcategory = $thiscategory;
            }
        }
        $arr = array('class'=>"pledge-".$c%2); # , 'href' => $pledge->url_main() );
        if ($q_type == 'succeeded_closed' || $q_type == 'failed') $arr['closed'] = true;
        if ($rss)
            $rss_items[] = $pledge->rss_entry();
        else
            $pledge->h_display_box($arr);
        $c++;
    }
    if (!$rss && $ntotal > PAGE_SIZE)
        print "<br style=\"clear: both;\">$navlinks";
} else {
    if (!$rss)
        print '<p>There are currently none.</p>';
}

if ($rss)
    rss_footer($rss_items);
else
    page_footer();

?>
