<?
// page.php:
// Header, footer and other layout parts for pages.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: page.php,v 1.30 2008-04-04 14:20:23 matthew Exp $

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
        $devwarning[] = _('You probably want <a href="http://www.pm.gov.uk">the Prime Minister\'s official site</a>.');
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

    // Display header
    $stat_js = '';
    if (!OPTION_PET_STAGING) {
        $stat_js = '<script type="text/javascript" src="http://www.pm.gov.uk/include/js/nedstat.js"></script>';
    }

    global $devwarning;
    $contents = file_get_contents("../templates/website/head.html");
    $creator = '10 Downing Street, Web Team, webmaster@pmo.gov.uk';
    $desc = 'Petitions to the Prime Minister, 10 Downing Street';
    $extra = '';
    $contents = str_replace('PARAM_SUBJECTS', '<meta name="dc.subject" content="10 Downing Street" />
<meta name="dc.subject" content="Petitions" />
<meta name="dc.subject" content="Prime Minister" />
<meta name="dc.subject" content="Tony Blair" />', $contents);
    $contents = str_replace('PARAM_CREATOR', $creator, $contents);
    $contents = str_replace('PARAM_DESCRIPTION', $desc, $contents);
    $contents = str_replace('PARAM_EXTRA', $extra, $contents);
    $contents = str_replace("PARAM_DC_IDENTIFIER", $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], $contents);
    $contents = str_replace("PARAM_TITLE", $title, $contents);
    $contents = str_replace("PARAM_DEV_WARNING", $devwarning, $contents);
    $contents = str_replace("PARAM_STAT_JS", $stat_js, $contents);
    $contents = str_replace("PARAM_RSS_LINKS", $rss_links, $contents);
    print $contents;
}

/* page_footer STAT_CODE
 * Print bottom of HTML page. This closes the "content" <div>.  
 */
function page_footer($stat_code = '') {
    if ($stat_code) {
        $stat_code = 'Petitions.' . $stat_code;
    } else {
        $stat_code = 'Petitions';
    }

    $site_stats = '';
    if (!OPTION_PET_STAGING) {
        $site_stats = file_get_contents('../templates/website/site-stats.html');
        $site_stats = str_replace("PARAM_STAT_CODE", $stat_code, $site_stats);
    }

    $contents = file_get_contents("../templates/website/foot.html");
    $contents = str_replace("PARAM_SITE_STATS", $site_stats, $contents);
    print $contents;

    header('Content-Length: ' . ob_get_length());
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
    $main_page = OPTION_BASE_URL . str_replace('rss/', '', $_SERVER['REQUEST_URI']);
    $main_page = htmlspecialchars($main_page);
    header('Content-Type: application/xml; charset=utf-8');
    print '<?xml version="1.0" encoding="UTF-8"?>';
    print '<?xml-stylesheet type="text/css" href="http://www.pm.gov.uk/rss/rss.css"?>';
?>

<rdf:RDF
 xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
 xmlns="http://purl.org/rss/1.0/"
>

<channel rdf:about="<?=$main_page?>">
<title>Number 10 E-Petitions - <?=$title?></title>
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

function terms_and_conditions() { ?>
<p>
The information in a petition must be submitted in good faith. In
order for the petition service to comply with the law and with
the Civil Service Code, you must not include: </p>

<ul style="line-height:1.4">
<li>Party political material. The Downing Street website is a
Government site. Party political content cannot be published, under the
<a href="http://www.civilservice.gov.uk/civilservicecode">normal rules governing the Civil Service</a>.
Please note, this does not mean it is not permissible to petition on
controversial issues. For example, this party political petition
would not be permitted: "We petition the PM to change his party's policy on education",
but this non-party political version would be:
"We petition the PM to change the government's policy on education".</li>
<li>potentially libellous, false, or defamatory statements;</li>
<li>information which may be protected by an injunction or court order (for
example, the identities of children in custody disputes);</li>
<li>material which is potentially confidential, commercially sensitive, or which
may cause personal distress or loss;</li>
<li>any commercial endorsement, promotion of any product, service or publication;</li>
<li>URLs or web links (we cannot vet the content of external sites, and
therefore cannot link to them from this site);</li>
<li>the names of individual officials of public bodies, unless they
are part of the senior management of those organisations;</li>
<li>the names of family members of elected representatives or
officials of public bodies;</li>
<li>the names of individuals, or information where they may be
identified, in relation to criminal accusations;</li>
<li>language which is offensive, intemperate, or provocative. This not
only includes obvious swear words and insults, but any language to which
people reading it could reasonably take offence (we believe it is
possible to petition for anything, no matter how radical, politely).</li>
</ul>

<p>We reserve the right to reject:</p>
<ul style="line-height:1.4">
<li>petitions that are similar to and/or overlap with an existing petition or petitions;</li>
<li>petitions which ask for things outside the remit or powers of the Prime Minister and Government;</li>
<li>statements that don't actually request any action - ideally start the title of your petition with a verb;</li>
<li>wording that is impossible to understand;</li>
<li>statements that amount to advertisements;</li>
<li>petitions which are intended to be humorous, or which
have no point about government policy (however witty these
are, it is not appropriate to use a publically-funded website
for purely frivolous purposes);</li>
<li>issues for which an e-petition is not the appropriate channel
(for example, correspondence about a personal issue);</li>
<li>Freedom of Information requests. This is not the right channel
for FOI requests; information about the appropriate procedure can be
found at <a href="http://www.ico.gov.uk/">http://www.ico.gov.uk/</a>.</li>
<li>nominations for Honours. These have been accepted in the past but
this is not the appropriate channel; accordingly, from 6 March 2008 we
are rejecting such petitions and directing petitioners to the
<a href="http://www.honours.gov.uk/">Cabinet Office website</a> where
nominations for Honours can be made directly to the appropriate department.</li>
</ul>

<p>We will strive to ensure that petitions that do not meet our
criteria are not accepted, but where a petition is accepted which
contains misleading information we reserve the right to post an
interim response to highlight this point to anyone visiting to 
sign the petition.</p>

<h3><span dir="ltr">Common causes for rejection</span></h3>

<p>Running the petition site, we see a lot of people having petitions
rejected for a handful of very similar reasons. In order to help you
avoid common problems, we've produced this list:</p>

<ul style="line-height:1.4">
<li>We don't accept petitions on individual legal cases such as
deportations because we can never ascertain whether the individual
involved has given permission for their details to be made publicly
known. We advise petitioners to take their concerns on such matters
directly to the Home Office.</li>

<li>Please don't use 'shouting' capital letters excessively as they
can make petitions fall foul of our 'impossible to read' criteria.</li>

<li>We receive a lot of petitions on devolved matters. If your
petition relates to the powers devolved to parts of the UK, such as
the Welsh Assembly or Scottish Parliament, you should approach those
bodies directly as these things are outside the remit of the Prime
Minister.</li>

<li>We also receive petitions about decisions that are clearly private
sector decisions, such as whether to re-introduce a brand of breakfast
cereal. These are also outside the remit of the Prime Minister.</li>

<li>We cannot accept petitions which call upon the PM to "recognize" or
"acknowledge" something, as they do not clearly call for a
recognizable action.</li>

</ul>

<?
}
