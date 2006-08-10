<?
// page.php:
// Header, footer and other layout parts for pages.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: page.php,v 1.10 2006-08-10 13:53:52 chris Exp $

/* page_header TITLE [PARAMS]
 * Print top part of HTML page, with the given TITLE. This prints up to the
 * start of the "content" <div>.  If PARAMS['nonav'] is true then the top 
 * title and navigation are not displayed, or if PARAMS['noprint'] is true
 * then they are not there if the page is printed. TITLE must be in HTML,
 * with codes already escape */
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
        $devwarning[] = _('You probably want <a href="http://www.pm.gov.uk">the Prime Minister\'s official site</a>.');
    }
    global $pet_today;
    if ($pet_today != date('Y-m-d')) {
        $devwarning[] = _("Note: On this test site, the date is faked to be") . " $pet_today";
    }
    $devwarning = join('<br>', $devwarning);

    include "../templates/website/head.php";

/*
    if ($P) {
        print '<p id="signedon" class="noprint">';
        print _('Hello, ');
        if ($P->has_name())
            print htmlspecialchars($P->name);
        else 
            print htmlspecialchars($P->email);
        print ' <small>(<a href="/logout">';
        print _('this isn\'t you?  click here');
        print '</a>)</small></p>';
    }
*/
}

/* page_footer PARAMS
 * Print bottom of HTML page. This closes the "content" <div>.  
 * If PARAMS['nonav'] is true then the footer navigation is not displayed. 
 * If PARAMS['nolocalsignup'] is true then no local signup form is showed.
 */
function page_footer($params = array()) {
    include "../templates/website/foot.php";
    header('Content-Length: ' . ob_get_length());
}

/* page_check_ref REFERENCE
 * Given a pledge REFERENCE, check whether it uniquely identifies a pledge. If
 * it does, return. Otherwise, fuzzily find possibly matching pledges and
 * show the user a set of possible pages. */
function page_check_ref($ref) {
    if (!is_null(db_getOne("select ref from petition where status in ('live','rejected','finished') and ref = ?", $ref)))
        return;
    else if (!is_null(db_getOne("select ref from petition where status in ('live','rejected','finished') and ref ilike ?", $ref)))
        /* XXX should redirect to the page with the correctly-capitalised
         * ref so that we never do the slow query */
        return;
    page_header(_("We couldn't find that petition"));
#    $s = db_query('select pledge_id from pledge_find_fuzzily(?) limit 5', $ref);
#    if (db_num_rows($s) == 0) {
    printf("<p>We couldn't find any pledge with a reference like \"%s\". Try the following: </p>", htmlspecialchars($ref) );
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
        <li>' . _('Look for the pledge on <a href="/list">the list of all petitions</a>.') . '</li></ul>';
#        <li>' . _('Search for the petition you want by entering words below.') . '</ul>';
/*    ?>
<form accept-charset="utf-8" action="/search" method="get" class="pledge">
<label for="s"><?=_('Search for a pledge') ?>:</label>
<input type="text" id="s" name="s" size="15" value=""> <input type="submit" value=<?=_('Go') ?>>
</form>
<? */
    
    page_footer();
    exit();
}

/* rss_header TITLE DESCRIPTION
 * Display header for RSS versions of page  
 */
function rss_header($title, $description, $params) {
    $main_page = str_replace('rss/', '', $_SERVER['REQUEST_URI']);
    header('Content-Type: application/xml; charset=utf-8');
    print '<?xml version="1.0" encoding="UTF-8"?>';
?>

<rdf:RDF
 xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
 xmlns="http://purl.org/rss/1.0/"
>

<channel rdf:about="<?=$main_page?>">
<title><?=$title?> - Number 10 E-Petitions</title>
<link><?=$main_page?></link>
<description><?=$description?></description>

<?
}

/* rss_footer ITEMS
 * Display items and footer for RSS versions of page. The items
 * is an array of entries. Each entry is an associative array
 * containing title, link and description
 */
function rss_footer($items) {
?> <items> <rdf:Seq>
<?  foreach ($items as $item) { ?>
  <rdf:li rdf:resource="<?=$item['link']?>" />
<? } ?>
 </rdf:Seq>
</items>
</channel>
<? foreach ($items as $item) { ?>
<item rdf:about="<?=$item['link']?>">
<title><?=$item['title']?></title>
<link><?=$item['link']?></link>
<description><?=$item['description']?></description>
</item>
<? } ?>
</rdf:RDF>
<?
}

?>
