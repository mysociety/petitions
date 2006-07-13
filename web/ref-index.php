<?
// ref-index.php:
// Main petition page, for URLs http://petitions.number10.gov.uk/REF
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: ref-index.php,v 1.3 2006-07-13 14:15:44 matthew Exp $

require_once '../conf/general';
require_once '../phplib/page.php';
require_once '../../phplib/db.php';
require_once '../phplib/pet.php';
require_once '../phplib/petition.php';

define('MAX_PAGE_SIGNERS', '500');

$ref = get_http_var('ref');
page_check_ref($ref);
$p  = new Petition($ref);

$title = $p->sentence(array('html'=>true));
page_header($title, array(
            'ref'=>$p->ref(),
        ));
draw_status_plaque($p);
$p->h_display_box();
if ($p->status() == 'live')
    petition_sign_box();
if ($p->status() != 'rejected')
    draw_signatories($p);
else
    reject_box();
page_footer();

function reject_box() { ?>
<p>This pledge has been <strong>rejected</strong>.</p>
<? }

function draw_status_plaque($p) {
    if (!$p->open()) {
        print '<p id="finished">' . _('This petition is now closed, as its deadline has passed.') . '</p>';
    }
}

function draw_spreadword($p) { ?>
    <div id="spreadword">
<?  if (!$p->finished()) {
        print '<h2>' . _('Spread the word on and offline') . '</h2>';
    } else {
        print '<h2>' . _('Things to do with this petition') . '</h2>';
    }
    print '<ul>';
    if (!$p->finished()) { ?>
    <li> <?="Email petition to your friends" ?></li>
    <? } ?>
    <li> <a href="" title="<?=_('Only if you made this petition') ?>"><?=_('Send message to signers') ?></a> <? print _('(creator only)');
    print '</ul>';
    print '</div>';
}

function draw_signatories($p) {
    $nsigners = db_getOne('select count(id) from signer where petition_id = ?', $p->id());
    ?>
    <div id="signatories">
<?
    print '<h2><a name="signers">' . _('Current signatories') . '</a></h2>';

    if ($nsigners == 0) {

        print '<p>'
                . sprintf(_('So far, only %s, the Petition Creator, has signed this petition.'), htmlspecialchars($p->creator_name()))
                . '</p></div>';
        return;
    }

    $limit = 0;
    $showall_para = '';
    $showall_nav = '';
    if ($nsigners > MAX_PAGE_SIGNERS) {
        if (!get_http_var('showall')) {
            $showall_para = sprintf(_("Because there are so many signers, only the most recent %d are shown on this page."), MAX_PAGE_SIGNERS);

            $showall_nav = sprintf("<a href=\"/%s?showall=1\">&gt;&gt; "
                    . htmlspecialchars(_("Show all signers"))
                    . "</a>",
                    htmlspecialchars($p->ref()));
            $limit = 1;

            print "<p>$showall_para</p>";
        }
    }
   
    $out = '';

    if (!$limit)
        $out = '<p>'
                . sprintf(_('%s, the Petition Creator, joined by:'), htmlspecialchars($p->creator_name()))
                . '</p>';

    $out .= "<ul>";
  
    $anon = 0;

    $query = "SELECT * FROM signer WHERE petition_id = ? ORDER BY id";
    if ($limit) {
        $query .= " LIMIT " . MAX_PAGE_SIGNERS . " OFFSET " . ($nsigners - MAX_PAGE_SIGNERS);
    }
    $q = db_query($query, $p->id());
    while ($r = db_fetch_array($q)) {
        $showname = ($r['showname'] == 't');
        if ($showname) {
            if (isset($r['name'])) {
                $out .= '<li>'
                        . htmlspecialchars($r['name'])
                        . '</li>';
            }
        } else {
            $anon++;
        }
    }
    print $out;
    if ($anon) {
        $extra = '';
        if ($anon)
            $extra .= sprintf(ngettext('%d person who did not want to give their name', '%d people who did not want to give their names', $anon), $anon);
        print "<li>$extra</li>";
    }
    print '</ul>';
    if ($showall_para) {
        print "<p>$showall_nav</p>";
        print "<p>$showall_para</p>";
    }
    print '<p>';
    print '</div>';
}

?>
