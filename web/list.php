<?
// list.php:
// View all petitions.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: list.php,v 1.68 2010-04-27 10:19:34 matthew Exp $

require_once "../phplib/pet.php";
require_once '../phplib/fns.php';
require_once '../phplib/petition.php';
require_once '../phplib/cobrand.php';
require_once '../commonlib/phplib/db.php';
require_once '../commonlib/phplib/importparams.php';
require_once '../commonlib/phplib/urls.php';

define('PAGE_SIZE', 50);

$err = importparams(
            array('offset', '/^(0|[1-9]\d*)$/', '', 0),
            array('sort', '/^(content|deadline|name|signers|creationtime|laststatuschange|date)\/?$/', '', 'default'),
            array('cat', '/^\d+$/', '', 'default'),
            array('type', '/^[a-z_]*$/', '', 'default'),
            array('body', '/^[a-z0-9_-]*$/i', '', '')
        );
if ($err) {
    err(_('Illegal offset or sort parameter passed'), E_USER_NOTICE);
}

$rss = get_http_var('rss') ? true : false;

# No special front page list needed currently.
#if (OPTION_SITE_NAME=='number10' && !$rss && $q_type == 'default' && $q_sort == 'default' && $q_cat == 'default') {
#    list_front();
#    exit;
#}

// Strip any trailing '/'.
$q_sort = preg_replace("#/$#", "", $q_sort);
if ($q_type == 'closed') {
    $status = 'finished';
} elseif (cobrand_archive_front_end() && $q_type == 'archived') {
    $status = 'finished';
} elseif ($q_type == 'rejected') {
    $status = 'rejected';
} else {
    $status = 'live';
    $q_type = 'open';
}
if ($q_sort == "default") {
    $q_sort = $rss ? 'date' : 'signers';
}
if ($q_sort == "creationtime" || $q_sort == 'laststatuschange')
    $q_sort = "date";
if ($q_cat == 'default') $q_cat = null;
if (!array_key_exists($q_cat, cobrand_categories())) $q_cat = null;

# count() is far too slow - many seconds for a count of live petitions :-/
$key = $status;
if ($q_type == 'archived') $key = 'archived';
if (OPTION_SITE_TYPE == 'multiple') {
    if (OPTION_SITE_DOMAINS) {
        $key .= "_$site_name";
    } elseif ($q_body) {
        $key .= "_$q_body";
    }
}
if ($q_cat) $key .= "_$q_cat";
$ntotal = db_getOne("select value from stats where key='cached_petitions_$key'");

# Don't want offset on RSS feeds
if ($rss && $q_offset) {
	header('Location: ' . url_new("/rss/list/$q_type", true, 'type', null, 'offset', null, 'rss', null));
	exit;
}

$sort_phrase = $q_sort;
if ($q_sort == 'date')
    $sort_phrase = 'laststatuschange';
if ($q_sort == 'date' || $q_sort == 'signers') {
    $sort_phrase .= " DESC";
}

$sql_params = array($status);
$qrows = "
    SELECT petition.*, '$pet_today' <= petition.deadline AS open,
        cached_signers+coalesce(offline_signers,0) as signers,
        (select count(*) from message where petition.id = message.petition_id
            and circumstance = 'government-response') as responses
";
if (OPTION_SITE_TYPE == 'multiple') {
    $qrows .= ", body.ref as body_ref, body.name as body_name
            FROM petition, body
            WHERE status = ? AND body.id = body_id ";
    if (OPTION_SITE_DOMAINS) {
        # Only want to show ones for this body
        $qrows .= ' AND body.ref = ?';
        $sql_params[] = $site_name;
    } elseif ($q_body) {
        $qrows .= ' AND body.ref = ?';
        $sql_params[] = $q_body;
    }
} else {
    $qrows .= " FROM petition WHERE status = ? ";
}

if ($q_cat) {
    $sql_params[] = $q_cat;
    $qrows .= "AND category = ? ";
}

if (cobrand_archive_front_end()) {
    if ($q_type == 'archived')
        $qrows .= ' AND archived IS NOT NULL';
    elseif ($q_type == 'closed')
        $qrows .= ' AND archived IS NULL';
}

$sql_params[] = PAGE_SIZE;
$qrows .= " ORDER BY $sort_phrase,petition.id LIMIT ? OFFSET $q_offset";
/* PG bug: mustn't quote parameter of offset */

$qrows = db_query($qrows, $sql_params);

$title = '';
if ($q_cat) {
    $title = cobrand_category($q_cat) . ' - ';
}
if ($q_type == 'open') {
    if ($rss)
        $title .= 'New Petitions';
    else
        $title .= "Open petitions";
} elseif (cobrand_archive_front_end() && $q_type == 'closed') {
    $title .= "Closed petitions &ndash; being considered";
} elseif (cobrand_archive_front_end() && $q_type == 'archived') {
    $title .= "Closed petitions &ndash; no further action";
} elseif ($q_type == 'closed') {
    $title .= "Closed petitions";
} elseif ($q_type == 'rejected') {
    $title .= "Rejected petitions";
} else {
    err('Unknown type ' . $q_type);
}
if ($rss) 
    rss_header($title, $title, array());
else {
    $heading = 'View petitions';
    if ($h = cobrand_view_petitions_heading())
        $heading = $h;
    page_header($title, array('id'=>'all',
            'rss'=> array(
                    $title => url_new("/rss/list/$q_type", true, 'offset', null, 'type', null)
             ),
             'h1' => $heading,
    ));
}

if (!$rss) {
    if (cobrand_archive_front_end()) {
        $viewsarray = array('open'=>'Open petitions', 'closed' => 'Closed &ndash; being considered',
            'archived' => 'Closed &ndash; no further action', 'rejected' => 'Rejected petitions');
    } else {
        $viewsarray = array('open'=>'Open petitions', 'closed' => 'Closed petitions',
            'rejected' => 'Rejected petitions');
    }
    $views = '';
    $b = false;
    foreach ($viewsarray as $s => $desc) {
        if ($b) $views .= cobrand_view_petitions_separator();
        if ($q_type == $s) {
            $views .= '<span>' . $desc . '</span>';
        } else {
            $views .= '<a href="' . htmlspecialchars(url_new("/list/$s", true, 'type', null)) . "\">$desc</a>";
        }
        $b = true;
    }
    
    if (OPTION_SITE_TYPE == 'multiple') {
        cobrand_show_body_selector($q_body);
    }

    pet_search_form(array('float'=>true));
    if (OPTION_SITE_NAME == 'sbdc' || OPTION_SITE_NAME == 'sbdc1') print '<h2>View petitions</h2>';
    cobrand_extra_heading('View petitions');

    if ($q_cat) {
        print '<h3>You are viewing petitions in the "' . cobrand_category($q_cat) . '" category</h3>';
    }

    $first = '<span class="greyed">First</span>';
    $prev = '<span class="greyed">Previous</span>';
    $next = '<span class="greyed">Next</span>';
    $last = '<span class="greyed">Last</span>';
    $other = false;
    if ($q_offset > 0) {
        $other = true;
        $n = $q_offset - PAGE_SIZE;
        if ($n < 0) $n = 0;
        $prev = '<a href="' . htmlspecialchars(url_new("/list/$q_type", true, 'offset', $n, 'type', null)) . '">Previous</a>';
        $first = '<a href="' . htmlspecialchars(url_new("/list/$q_type", true, 'offset', 0, 'type', null)) . '">First</a>';
    }
    if ($q_offset + PAGE_SIZE < $ntotal) {
        $other = true;
        $n = $q_offset + PAGE_SIZE;
        $next = '<a href="' . htmlspecialchars(url_new("/list/$q_type", true, 'offset', $n, 'type', null)) . '">Next</a>';
        $last = '<a href="' . htmlspecialchars(url_new("/list/$q_type", true, 'offset', floor(($ntotal-1)/PAGE_SIZE)*PAGE_SIZE, 'type', null)) . '">Last</a>';
    }
    $navlinks = '<p style="clear: both;" class="petition_view_tabs">' . $views . "</p>\n";
    $cat_filter = '';
    if (cobrand_view_petitions_category_filter()) {
        $navlinks .= '<form method="get" action="">';
        $cats = cobrand_categories();
        $cat_filter = 'View category: <select name="cat">';
        foreach ($cats as $k => $v) {
            if ($v == 'None') $v = 'All';
            $cat_filter .= "<option value='$k'";
            if ($q_cat == $k) $cat_filter .= ' selected';
            $cat_filter .= ">$v</option>\n";
        }
        $cat_filter .= "</select> <input type='submit' value='Show'>\n";
    }
    if ($ntotal > 0) {
        $navlinks .= '<p class="list_sort_by">' . _('Sort by'). ': ';
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
            if ($q_sort != $s) $navlinks .= '<a href="' . htmlspecialchars(url_new("/list/$q_type", true, 'sort', $s, 'type', null)) . "\">$desc</a>";
            else $navlinks .= $desc;
            $b = true;
        }
        if ($cat_filter) {
            $navlinks .= ". $cat_filter</p></form>\n";
        } else {
            $navlinks .= "</p>\n";
        }
        $navlinks .= '<p class="banner">';
        if ($other) {
            $navlinks .= "$first | $prev | " . _('Petitions'). ' ' . ($q_offset + 1) . ' &ndash; ' . 
                ($q_offset + PAGE_SIZE > $ntotal ? $ntotal : $q_offset + PAGE_SIZE) . ' of ' .
                $ntotal . " | $next | $last";
        }
        $navlinks .= '</p>';
    } elseif ($cat_filter) {
        $navlinks .= '<p class="list_sort_by">' . $cat_filter . '</p>';
    }
    print $navlinks;
}

$rss_items = array();
if ($ntotal > 0) {
    $c = 1;
    if (!$rss) {
        $petitioned = (OPTION_SITE_TYPE == 'one') ? OPTION_SITE_PETITIONED : '';
?>
<table class="petition-list-table" cellpadding="3" cellspacing="0" border="0">
<tr><th class="long">We the undersigned petition <?=$petitioned ?> to&hellip;</th>
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
            if (OPTION_SITE_TYPE == 'multiple' && !OPTION_SITE_DOMAINS) {
                print $petition->body_ref() . '/';
            }
            print $petition->rejected_show_part('ref') ? $petition->ref() . '/' : 'reject?id=' . $petition->id();
            print '">';
            if ($petition->rejected_show_part('content')) {
                print $petition->h_content(true);
            } else {
                print 'more details';
            }
            print '</a>';
            if ($petition->data['responses']) {
                print ' (with response';
                if ($petition->data['responses'] > 1) print 's';
                print ')';
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
        print "<br style=\"clear: both;\" />$navlinks";
} else {
    if (!$rss)
        print '<p>There are currently no petitions in this category.</p>';
}

if ($rss)
    rss_footer($rss_items);
else {
?>
<p class="leading" id="ms-petition-list-rss"><a href="<?=url_new("/rss/list/$q_type", true, 'offset', null, 'type', null) ?>"><img class="noborder" src="/images/rss-icon.gif" alt="<?=_('RSS feed of ') . $title ?>" /> RSS</a>
| <a href="<?=cobrand_rss_explanation_link() ?>">What is RSS?</a></p>
<?
    page_footer('List.' . $q_type);
}

function list_front() {
    page_header('View petitions');
?>
<h2 class="page_title_border">View petitions...</h2>
<ul style="font-size:150%;">
<li><a href="/list/open?sort=deadline">By deadline</a></li>
<li><a href="/list/open?sort=signers">By size</a></li>
<li><a href="/list/open?sort=date">By start date</a></li>
</ul>

<h2 class="page_title_border">By category</h2>
<?
    print '<ul style="font-size:125%">';
    foreach (cobrand_categories() as $id => $cat) {
        if ($cat == 'None') continue;
        print '<li style="margin-bottom: 0.5em;"><a href="/list/open?cat=' . $id . '">' . $cat . '</a></li>';
    }
    print '</ul>';
    page_footer();
}

?>
