<?
// search.php:
// Search for a petition.
//
// Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: search.php,v 1.11 2007-02-05 18:10:19 matthew Exp $

require_once "../phplib/pet.php";
require_once '../phplib/fns.php';
require_once '../phplib/petition.php';
require_once '../../phplib/db.php';
require_once '../../phplib/importparams.php';

$search = trim(get_http_var('q', true));
if (!$search) $search = trim(get_http_var('s', true));
$rss = get_http_var('rss') ? true : false;
$rss_items = array();
$petitions_output = array();
$heading = sprintf(_("Search results for '%s'"), htmlspecialchars($search));
if ($rss)
    rss_header($heading, $heading, array());
else {
    page_header($heading, array('id'=>'all',
            'rss'=> array(
                    $heading => '/rss' . $_SERVER['REQUEST_URI']
                    ),
    ));
}
search($search);
if ($rss) {
    function compare_creationtime($a, $b) {
        return strcmp($b['creationtime'], $a['creationtime']);
    }
    array_unique($rss_items);
    usort($rss_items, "compare_creationtime");
    rss_footer($rss_items);
}
else
    page_footer();

function search($search) {
    global $pb_today, $rss, $rss_items, $petitions_output;
    $success = 0;

    $create = get_http_var('create');

    if (!$rss) {
        // Blank searches
        if ($search == _('<Enter town or keyword>'))
            $search = "";
        if (!$search) {
            print "<p>"._('You can search for:')."</p>";
            print "<ul>";
            print "<li>"._("<strong>Any words</strong>, to find petitions containing those words")."</li>";
            //print li(_("The name of <strong>a person</strong>, to find petitions they made or signed"));
            print "</ul>";
            return;
        }
    }
       
    // Link to RSS feed
    if (!$rss) {
        global $heading;
?><p align="right"><a href="/rss<?=$_SERVER['REQUEST_URI'] ?>"><img class="noborder" src="/images/rss-icon.gif" alt="<?=_('RSS feed of search results for') . " '".htmlspecialchars($search)."'" ?>" /> RSS</a>
| <a href="http://news.bbc.co.uk/1/hi/help/3223484.stm">What is RSS?</a></p>
<?

    }

    // General query
    global $pet_today;
    $petition_select = "SELECT petition.*, '$pet_today' <= petition.deadline AS open,
        cached_signers ";

    // Exact petition reference match
/*    if (!$rss) {
        $q = db_query("$petition_select FROM petition
                    WHERE (status = 'live' OR status = 'finished') AND ref ILIKE ?", $search);
        if (db_num_rows($q)) {
            $success = 1;
            $r = db_fetch_array($q);
            $pledge = new Pledge($r);
            print sprintf(p(_('Result <strong>exactly matching</strong> petition <strong>%s</strong>:')), htmlspecialchars($search) );
            print '<ul><li>';
            print $pledge->summary(array('html'=>true, 'href'=>$r['ref']));
            $petitions_output[$r['ref']] = 1;
            print '</li></ul>';
        }
    }
*/

    // Searching for text in petitions - stored in strings $live, $finished printed later
    $q = db_query($petition_select . ' FROM petition
                WHERE (status = \'live\' OR status = \'finished\')
                    AND (content ILIKE \'%\' || ? || \'%\' OR 
                         detail ILIKE \'%\' || ? || \'%\' OR 
                         ref ILIKE \'%\' || ? || \'%\')
                ORDER BY deadline DESC', array($search, $search, $search));
                    #AND lower(ref) <> ?
    $finished = ''; $live = '';
    if (db_num_rows($q)) {
        $success = 1;
        while ($r = db_fetch_array($q)) {
            if (array_key_exists($r['ref'], $petitions_output))
                continue;
            $petition = new Petition($r);
            $petitions_output[$r['ref']] = 1;
        
            $text = '<li';
            #if ($c%2) $text .= ' class="a"';
            $text .= '><a href="/' . $r['ref'] . '/">';
            $text .= htmlspecialchars($r['content']) . '</a> <small>(';
            $text .= $r['cached_signers'] . ' signature';
            $text .= ($r['cached_signers'] == 1 ? '' : 's') . ')</small></li>';
            $text .= '</li>';
            #$c++; 

            if ($r['status']=='live') {
                $live .= $text;
            } elseif ($r['status']=='finished') {
                $finished .= $text;
            } else {
                err('unexpected status type found');
            }
            if ($rss && $r['status'] == 'live') {
                $rss_items[] = $petition->rss_entry();
            }
        }
    }

    // No more search types that go into RSS
    if ($rss)
        return;

    // Live petitions
    if ($live) {
        if ($create) {
            print '<p>The following open petitions matched <strong>' . htmlspecialchars($search) . '</strong>:</p>';
        } else {
            printf("<p>"._('Results for <strong>open petitions</strong> matching <strong>%s</strong>:')."</p>", htmlspecialchars($search) );
        }
        print '<ul>' . $live . '</ul>';
    }

    // Finished petitions
    if ($finished) {
        print sprintf("<p>"._('Results for <strong>closed petitions</strong> matching <strong>%s</strong>:')."</p>", htmlspecialchars($search) );
        print '<ul>' . $finished . '</ul>';
    }

    // Signers and creators (NOT person table, as we only search for publically visible names)
/*    $people = array();
    $q = db_query('SELECT ref, title, name FROM pledges WHERE pin IS NULL AND name ILIKE \'%\' || ? || \'%\' ORDER BY name', $search);
    while ($r = db_fetch_array($q)) {
        $people[$r['name']][] = array($r['ref'], $r['title'], 'creator');
    }
    $q = db_query('SELECT ref, title, signers.name FROM signers,pledges WHERE showname AND pin IS NULL AND signers.pledge_id = pledges.id AND signers.name ILIKE \'%\' || ? || \'%\' ORDER BY name', $search);
    while ($r = db_fetch_array($q)) {
        $people[$r['name']][] = array($r['ref'], $r['title'], 'signer');
    }
    if (sizeof($people)) {
        $success = 1;
        print sprintf(p(_('Results for <strong>people</strong> matching <strong>%s</strong>:')), htmlspecialchars($search) );
        print '<dl>';
        uksort($people, 'strcoll');
        foreach ($people as $name => $array) {
            print '<dt><b>'.htmlspecialchars($name). '</b></dt> <dd>';
            foreach ($array as $item) {
                print '<dd>';
                print '<a href="/' . $item[0] . '">' . $item[1] . '</a>';
                if ($item[2] == 'creator') print _(" (creator)");
                print '</dd>';
            }
        }
        print '</dl>';
    } */

    if (!$success) {
        if ($create)
            printf("<p>"._('We could not find any petitions that matched "%s".')."</p>", htmlspecialchars($search) );
        else
            printf("<p>"._('Sorry, we could not find any petitions that matched "%s".')."</p>", htmlspecialchars($search) );
    }

    if ($create) {
        $blurb = $success ? 'If you still wish to go ahead and create your petition' : 'Go ahead and create your petition';
        print <<<EOF
<form action="/new" method="post" name="newpetition">
<input type="hidden" name="tostepmain" value="1" />
<p>$blurb:
<input type="submit" value="Create petition" />
</p>
</form>
<h2><span class="ltr">Search again</span></h2>
EOF;
    } else {
        print '<h2><span class="ltr">Search E-Petitions</span></h2>';
    }
    pet_search_form($create);
}

?>
