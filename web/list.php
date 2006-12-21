<?
// all.php:
// List all petitions.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: list.php,v 1.45 2006-12-21 19:01:20 matthew Exp $

require_once "../phplib/pet.php";
require_once '../phplib/fns.php';
require_once '../phplib/petition.php';
require_once '../../phplib/db.php';
require_once '../../phplib/importparams.php';
require_once '../../phplib/urls.php';

define('PAGE_SIZE', 50);

$err = importparams(
            array('offset', '/^(0|[1-9]\d*)$/', '', 0),
            array('sort', '/^(content|deadline|name|signers|creationtime|laststatuschange|date)\/?$/', '', 'default'),
            array('cat', '/^\d+$/', '', 'default'),
            array('type', '/^[a-z_]*$/', '', 'default')
        );
if ($err) {
    err(_('Illegal offset or sort parameter passed'));
}

$rss = get_http_var('rss') ? true : false;

if (!$rss && $q_type == 'default' && $q_sort == 'default' && $q_cat == 'default') {
    list_front();
    exit;
}

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
    $q_type = 'open';
}
if ($q_sort == "default") {
    $q_sort = $rss ? 'date' : 'signers';
}
if ($q_sort == "creationtime" || $q_sort == 'laststatuschange')
    $q_sort = "date";
if ($q_cat == 'default') $q_cat = null;
if (!array_key_exists($q_cat, $global_petition_categories)) $q_cat = null;

$sql_params = array($status);
if ($q_cat) $sql_params[] = $q_cat;
$query = "SELECT count(petition.id) FROM petition
                WHERE status = ? " .
                ($open ? "AND deadline $open '$pet_today' " : '') .
                ($q_cat ? 'AND category = ? ' : '');
$ntotal = db_getOne($query , $sql_params);
if ($ntotal < $q_offset) {
    $q_offset = $ntotal - PAGE_SIZE;
    if ($q_offset < 0)
        $q_offset = 0;
}

$sort_phrase = $q_sort;
if ($q_sort == 'date')
    $sort_phrase = 'laststatuschange';
if ($q_sort == 'date' || $q_sort == 'signers') {
    $sort_phrase .= " DESC";
}
$sql_params[] = PAGE_SIZE;
$qrows = db_query("
        SELECT petition.*, '$pet_today' <= petition.deadline AS open,
	    cached_signers as signers,
                message.id as message_id
            FROM petition
            left join message on petition.id = message.petition_id and circumstance = 'government-response'
            WHERE status = ?".
            ($open ? " AND deadline $open '$pet_today' " : ""). 
            ($q_cat ? "AND category = ? " : "") .
           "ORDER BY $sort_phrase,petition.id LIMIT ? OFFSET $q_offset", $sql_params);
/* PG bug: mustn't quote parameter of offset */

$heading = '';
if ($q_cat) {
    $heading = $global_petition_categories[$q_cat] . ' - ';
}
if ($q_type == 'open') {
    if ($rss)
        $heading .= 'New Petitions';
    else
        $heading .= "Open petitions";
} elseif ($q_type == 'closed') {
    $heading .= "Closed petitions";
} elseif ($q_type == 'rejected') {
    $heading .= "Rejected petitions";
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

    $viewsarray = array('open'=>'Open petitions', 'closed' => 'Closed petitions',
        'rejected' => 'Rejected petitions');
    $views = '';
    $b = false;
    foreach ($viewsarray as $s => $desc) {
        if ($b) $views .= ' &nbsp; ';
        if ($q_type == $s) {
            $views .= '<span>' . $desc . '</span>';
        } else {
            $views .= "<a href=\"/list/$s" . url_new('', true, 'type', null) . "\">$desc</a>";
	}
        $b = true;
    }

    pet_search_form();

    if ($q_cat) {
        print '<h2><span class="ltr">You are viewing petitions in the "' . $global_petition_categories[$q_cat] . '" category</span></h2>';
    }

    $first = '<span class="greyed">First</span>';
    $prev = '<span class="greyed">Previous</span>';
    $next = '<span class="greyed">Next</span>';
    $last = '<span class="greyed">Last</span>';
    if ($q_offset > 0) {
        $n = $q_offset - PAGE_SIZE;
        if ($n < 0) $n = 0;
        $prev = '<a href="' . url_new('', true, 'offset', $n, 'type', null) . '">Previous</a>';
        $first = '<a href="' . url_new('', true, 'offset', 0, 'type', null) . '">First</a>';
    }
    if ($q_offset + PAGE_SIZE < $ntotal) {
        $n = $q_offset + PAGE_SIZE;
        $next = '<a href="' . url_new('', true, 'offset', $n, 'type', null) . '">Next</a>';
        $last = '<a href="' . url_new('', true, 'offset', floor(($ntotal-1)/PAGE_SIZE)*PAGE_SIZE, 'type', null) . '">Last</a>';
    }
    $navlinks = '<p id="petition_view_tabs">' . $views . "</p>\n";
    if ($ntotal > 0) {
        $navlinks .= '<p align="center" style="font-size: 89%">' . _('Sort by'). ': ';
        $arr = array(
                     'date'=>_('Start date'), 
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
            if ($q_sort != $s) $navlinks .= '<a href="' . url_new('', true, 'sort', $s, 'type', null) . "\">$desc</a>";
	    else $navlinks .= $desc;
            $b = true;
        }
        $navlinks .= '</p> <p align="center">';
        $navlinks .= "$first | $prev | " . _('Petitions'). ' ' . ($q_offset + 1) . ' &ndash; ' . 
            ($q_offset + PAGE_SIZE > $ntotal ? $ntotal : $q_offset + PAGE_SIZE) . ' of ' .
            $ntotal . " | $next | $last";
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
            print $petition->rejected_show_part('ref') ? $petition->ref() . '/' : 'reject?id=' . $petition->id();
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
        print '<p>There are currently no petitions in that category.</p>';
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

function list_front() {
    global $global_petition_categories;
    page_header('List petitions');
?>
<h2><span class="ltr">List petitions...</span></h2>
<ul style="font-size:150%;">
<li><a href="/list/open?sort=deadline">By deadline</a></li>
<li><a href="/list/open?sort=signers">By size</a></li>
<li><a href="/list/open?sort=date">By start date</a></li>
</ul>

<h2><span class="ltr">By category</span></h2>
<?
    print '<ul style="font-size:125%">';
    foreach ($global_petition_categories as $id => $cat) {
        if ($cat == 'None') continue;
        print '<li style="margin-bottom: 0.5em;"><a href="/list/open?cat=' . $id . '">' . $cat . '</a></li>';
    }
    print '</ul>';
    page_footer();
}

?>
