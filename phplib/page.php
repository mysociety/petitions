<?
// page.php:
// Header, footer and other layout parts for pages.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: page.php,v 1.48 2010-04-23 17:15:56 matthew Exp $

# Work out which site we're on
$site_name = null;
$site_group = null;
if (strpos(OPTION_SITE_NAME, ',')) {
    $sites = explode(',', OPTION_SITE_NAME);
    $site_group = $sites[0];
    foreach ($sites as $s) {
        if ($_SERVER['HTTP_HOST'] == "petitions.$s.gov.uk" || $_SERVER['HTTP_HOST'] == "$s.petitions.mysociety.org") {
            $site_name = $s;
            break;
        }
    }
    if (!$site_name) $site_name = $sites[0];
} else {
    $site_name = OPTION_SITE_NAME;
    $site_group = OPTION_SITE_NAME;
}

/* page_header TITLE [PARAMS]
 * Print top part of HTML page, with the given TITLE. This prints up to the
 * start of the "content" <div>.  If PARAMS['nonav'] is true then the top 
 * title and navigation are not displayed, or if PARAMS['noprint'] is true
 * then they are not there if the page is printed. TITLE must be in HTML,
 * with codes already escape. PARAMS['rss'] contains a hash from RSS feed 
 * titles to URLs.
 */
function page_header($title, $params = array()) {
    // The http-equiv in the HTML below doesn't always seem to override HTTP
    // header, so we say that we are UTF-8 in the HTTP header as well (Case
    // where this was required: On my laptop, Apache wasn't setting UTF-8 in
    // header by default as on live server, and FireFox was defaulting to
    // latin-1 -- Francis)
    header('Content-Type: text/html; charset=utf-8');

    // Warn that we are on a testing site
    global $devwarning;
    $devwarning = array();
    if (OPTION_PET_STAGING) {
        $devwarning[] = _('This is a test site for web developers only.');
        $devwarning[] = _('You probably want <a href="http://www.number10.gov.uk">the Prime Minister\'s official site</a>.');
    }
    global $pet_today;
    if ($pet_today != date('Y-m-d')) {
        $devwarning[] = _("Note: On this test site, the date is faked to be") . " $pet_today";
    }
    $devwarning = join('<br />', $devwarning);

    // Add in RSS autodetection links
    $rss_links = '';
    if (array_key_exists('rss', $params)) {
        foreach ($params['rss'] as $rss_title => $rss_url) {
            $rss_links .= '<link rel="alternate" type="application/rss+xml" title="' . $rss_title . '" href="'.$rss_url.'" />' . "\n";
        }
    }

    $title = cobrand_page_title($title);

    // Display header
    global $site_name;
    if (OPTION_CREATION_DISABLED && file_exists('../templates/' . $site_name . '/head-nocreation.html')) {
        $contents = file_get_contents('../templates/' . $site_name . '/head-nocreation.html');
    } else {
        $contents = file_get_contents('../templates/' . $site_name . '/head.html');
    }

    if (OPTION_SITE_NAME == 'number10') {
        $creator = '10 Downing Street, Web Team, admin&#64;number10.gov.uk';
        $desc = 'Petitions to the Prime Minister, 10 Downing Street';
        $contents = str_replace('PARAM_SUBJECTS', '<meta name="dc.subject" content="10 Downing Street" />
<meta name="dc.subject" content="Petitions" />
<meta name="dc.subject" content="Prime Minister" />
<meta name="dc.subject" content="Gordon Brown" />', $contents);
    } else {
        $creator = OPTION_SITE_NAME;
        $desc = 'Petitions to ' . OPTION_SITE_NAME;
    }

    $extra = '';
    $contents = str_replace('PARAM_CREATOR', $creator, $contents);
    $contents = str_replace('PARAM_DESCRIPTION', $desc, $contents);
    $contents = str_replace('PARAM_EXTRA', $extra, $contents);
    $contents = str_replace("PARAM_DC_IDENTIFIER", 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], $contents);
    $contents = str_replace("PARAM_TITLE", $title, $contents);
    $contents = str_replace("PARAM_H1", isset($params['h1']) ? $params['h1'] : $title, $contents);
    $contents = str_replace("PARAM_DEV_WARNING", $devwarning, $contents);
    $contents = str_replace("PARAM_RSS_LINKS", $rss_links, $contents);
    $contents = str_replace('PARAM_DATE', date('j-M-Y'), $contents);

    $body_id = '';
    if (isset($params['admin'])) $body_id = ' id="admin"';
    $contents = str_replace("PARAM_BODYID", $body_id, $contents);

    print $contents;
}

/* page_footer STAT_CODE
 * Print bottom of HTML page. This closes the "content" <div>.  
 */
function page_footer($stat_code = '') {
    global $site_name;

    if ($stat_code) {
        $stat_code = 'Petitions.' . $stat_code;
    } else {
        $stat_code = 'Petitions';
    }

    $contents = file_get_contents('../templates/' . $site_name . '/foot.html');
    if (OPTION_SITE_NAME == 'number10') {
        $site_stats = '';
        if (!OPTION_PET_STAGING) {
            $site_stats = file_get_contents('../templates/number10/site-stats.html');
            $site_stats = str_replace("PARAM_STAT_CODE", $stat_code, $site_stats);
        }
        $contents = str_replace("PARAM_SITE_STATS", $site_stats, $contents);
    }

    print $contents;
}

/* page_check_ref REFERENCE
 * Given a petition REFERENCE, check whether it uniquely identifies a petition. If
 * it does, return. Otherwise, fuzzily find possibly matching petitions and
 * show the user a set of possible pages. */
function page_check_ref($ref) {
    if (!is_null(db_getOne("select ref from petition
        where status in ('live','rejected','finished')
            and lower(ref) = ?", strtolower($ref))))
        return;
    page_header(_("We couldn't find that petition"));
#    $s = db_query('select pledge_id from pledge_find_fuzzily(?) limit 5', $ref);
#    if (db_num_rows($s) == 0) {
    printf("<p>We couldn't find any petition with a reference like \"%s\". Try the following: </p>", htmlspecialchars($ref) );
#    } else {
#        printf(p(_("We couldn't find the pledge with reference \"%s\". Did you mean one of these pledges?")), htmlspecialchars($ref) );
#        print '<dl>';
#        while ($r = db_fetch_array($s)) {
#            $p = new Pledge((int)$r['pledge_id']);
#            print '<dt><a href="/'
#                        /* XXX for the moment, just link to pledge index page,
#                         * but we should figure out which page the user
#                         * actually wanted and link to that instead. */
#                        . htmlspecialchars($p->ref()) . '">'
#                        . htmlspecialchars($p->ref()) . '</a>'
#                    . '</dt><dd>'
#                    . $p->h_sentence()
#                    . '</dd>';
#        }
#        print '</dl>';
#        print p(_('If none of those look like what you want, try the following:'));
#    }

    print '<ul>
        <li>' . _('If you typed in the location, check it carefully and try typing it again.') . '</li>
        <li>' . _('Look for the petition on <a href="/list">the list of all petitions</a>.') . '</li></ul>';
#        <li>' . _('Search for the petition you want by entering words below.') . '</ul>';
/*    ?>
<form accept-charset="utf-8" action="/search" method="get" class="pledge">
<label for="s"><?=_('Search for a pledge') ?>:</label>
<input type="text" id="s" name="s" size="15" value=""> <input type="submit" value=<?=_('Go') ?>>
</form>
<? */
    
    page_footer('Bad_ref');
    exit();
}

/* rss_header TITLE DESCRIPTION
 * Display header for RSS versions of page  
 */
function rss_header($title, $description, $params) {
    $self = OPTION_BASE_URL . $_SERVER['REQUEST_URI'];
    $main_page = OPTION_BASE_URL . str_replace('rss/', '', $_SERVER['REQUEST_URI']);
    $main_page = htmlspecialchars($main_page);
    header('Content-Type: application/xml; charset=utf-8');
    header('Cache-Control: max-age=3600');
    print '<?xml version="1.0" encoding="UTF-8"?>';
    if (OPTION_SITE_NAME == 'number10') {
        $site_title = 'Number 10 E-Petitions';
        print '<?xml-stylesheet type="text/css" href="http://www.number10.gov.uk/rss/rss.css"?>';
    } else {
        $site_title = 'E-Petitions';
    }
?>

<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title><?=$site_title ?> - <?=$title?></title>
    <link><?=$main_page?></link>
    <atom:link href="<?=$self?>" rel="self" type="application/rss+xml" />
    <description><?=$description?></description>

<?
}

/* rss_footer ITEMS
 * Display items and footer for RSS versions of page. The items
 * is an array of entries. Each entry is an associative array
 * containing title, link and description
 */
function rss_footer($items) {
?> 
<? foreach ($items as $item) { ?>
    <item>
      <title><?=$item['title']?></title>
      <link><?=$item['link']?></link>
      <description><?=$item['description']?></description>
      <pubDate><?=date('r', strtotime($item['pubdate']))?></pubDate>
      <guid><?=$item['link']?></guid>
    </item>
<? } ?>
  </channel>
</rss>
<?
}

function page_closed_message($front = false) {
    if ($front) echo '<br style="clear:both" />';
    if (is_string(OPTION_CREATION_DISABLED)) {
        echo OPTION_CREATION_DISABLED;
    } else {
        echo '<p>Notice: Submission of new petitions is currently closed.
You can still sign any petition during this time.</p>';
    }
}

