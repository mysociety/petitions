<?
// index.php:
// Main page for ePetitions website.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.64 2010-04-27 10:41:17 matthew Exp $

// Load configuration file
require_once "../phplib/pet.php";
require_once "../phplib/cobrand.php";
require_once "../commonlib/phplib/conditional.php";

if (OPTION_SITE_TYPE == 'multiple') {
    $body = '';
    $params = array();
    if (OPTION_SITE_DOMAINS) {
        $body = 'and body.ref = ?';
        $params[] = $site_name;
    }
    $recent = db_getAll("select petition.ref, content,
        body.ref as body_ref, body.name as body_name
    from petition, body
    where status = 'live' and body_id = body.id
    $body
    order by laststatuschange desc limit 5", $params);
    $most = db_getAll("
    select petition.ref, content, cached_signers,
        body.ref as body_ref, body.name as body_name
    from petition, body
    where status = 'live' and body_id = body.id
    $body
    order by cached_signers desc limit 5", $params);
} else {
    $recent = db_getAll("select ref, content from petition
    where status = 'live'
    order by laststatuschange desc limit 5");
    $most = db_getAll("
    select ref, content, cached_signers
    from petition
    where status = 'live'
    order by cached_signers desc limit 5");
}

// Lame: send last-modified now to encourage squid to cache us.
cond_headers(time());
header('Cache-Control: max-age=5');
page_header('Introduction to e-petitions', array(
    'rss' => array(
        'Latest Petitions' => '/rss/list'
    )
));

if (OPTION_SITE_NAME == 'sbdc' || OPTION_SITE_NAME == 'sbdc1' || strpos(OPTION_SITE_NAME, 'surrey')!==false || OPTION_SITE_NAME=='lichfielddc') {
    if (OPTION_SITE_NAME == 'sbdc')
        echo '<h2>Make or sign petitions through this official Borsetshire District Council petitions website</h2>';
    front_actions();
    front_intro_text();
    front_most_popular($most);
    if (OPTION_SITE_NAME == 'sbdc' || OPTION_SITE_NAME == 'sbdc1') {
        print '<div style="float: left; text-align: center; padding-top:0.5em; width: 45%; padding: 5px;">';
        pet_search_form(array('front'=>true));
        print '</div>';
    }
    front_most_recent($recent);
    front_how_it_works();
} else {
    echo '<div id="content_clipboard">';
    front_actions();
    front_intro_text();
    pet_search_form(array('front'=>true));
    if (OPTION_CREATION_DISABLED) {
        page_closed_message(true);
    }
    echo '</div>';
    front_most_recent($recent);
    front_most_popular($most);
    front_how_it_works();
}
page_footer('Home');

# --- 

function front_actions() {
    echo '<div id="petition_actions"> <ul>';
    if (!OPTION_CREATION_DISABLED) {
        echo '<li id="action_create"><a href="/new"><img src="/images/clipboard-add.gif" alt="" class="noborder"
/><br />Create a petition</a></li>';
    }

    echo '<li id="action_view"><a href="/list"><img src="/images/clipboard-write.gif" alt="" class="noborder"
/><br />View petitions</a></li>
</ul>
</div>';
}

function front_intro_text() {
    if (OPTION_SITE_NAME == 'number10') {
        echo '<p>Petitions have long been sent to the Prime Minister by post or delivered to
the Number 10 door in person. You can now both create and sign petitions on
this website too, giving you the opportunity to reach a potentially wider audience
and to deliver your petition directly to Downing Street.</p>';
    } elseif (OPTION_SITE_NAME == 'councils') {
        echo '<p><em>You can now both create and sign petitions to ' . str_replace('the ', 'your ', OPTION_SITE_PETITIONED) . ' on this website,
giving you the opportunity to reach a potentially wider audience and to deliver your petition
directly to ' . OPTION_SITE_PETITIONED . '.</em></p>';
    }
}

function petition_row($petition, $c) {
    print '<li';
    if ($c%2) print ' class="a"';
    print '><a href="/';
    if (OPTION_SITE_TYPE == 'multiple' && !OPTION_SITE_DOMAINS) {
        print $petition['body_ref'] . '/">' . $petition['body_name'] . '</a> to <a href="/';
        print $petition['body_ref'] . '/';
    }
    print $petition['ref'] . '/">';
    print htmlspecialchars($petition['content']) . '</a>';
    if (isset($petition['cached_signers'])) {
        print ' <small>(';
        print $petition['cached_signers'] . ' signature';
        print ($petition['cached_signers'] == 1 ? '' : 's') . ')</small>';
    }
    print '</li>';
}

function front_most_recent($recent) {
    echo "<div id='most_recent'>";
    echo cobrand_main_heading('Most recent petitions');
    # If cross-site in future, will need to display name within each row
    echo '<p>We the undersigned petition ' . OPTION_SITE_PETITIONED . ' to&hellip;</p>';
    echo '<ul>';
    $c = 1;
    foreach ($recent as $petition) {
        petition_row($petition, $c++);
    }
    if (!count($recent)) {
        if (OPTION_CREATION_DISABLED) {
            print '<li><em>There are currently no petitions.</em></li>';
        } else {
            print '<li><em>None</em>; you can <a href="/new">create a petition</a>.</li>';
        }
    }
?>
</ul>
<p align="right"><a href="/list/open?sort=date">More recent petitions</a></p>
</div>
<?
}

function front_most_popular($most) {
    echo '<div id="most_popular">';
    echo cobrand_main_heading('Most popular open petitions');
    echo '<p>We the undersigned petition ' . OPTION_SITE_PETITIONED . ' to&hellip;</p>';
    echo '<ul>';
    $c = 1;
    foreach ($most as $petition) {
        petition_row($petition, $c++);
    }
    if (!count($most)) {
        if (OPTION_CREATION_DISABLED) {
            print '<li><em>There are currently no petitions.</em></li>';
        } else {
            print '<li><em>None</em>; you can <a href="/new">create a petition</a>.</li>';
        }
    }
?>
</ul>
<p align="right"><a href="/list/open?sort=signers">More popular petitions</a></p>
</div>
<?
}

function front_how_it_works() {
    echo '<div id="front_how">';
    echo cobrand_main_heading('How it works');
?>
<p>You can view and sign any <a href="/list">current petitions</a>, and see
<?=OPTION_SITE_NAME=='number10' ? 'the Government' : 'our' ?> response to any
<a href="/list/closed">completed petitions</a>.
<? if (OPTION_SITE_NAME == 'number10') { ?>
If you have signed a petition that has reached more than <?=cobrand_signature_threshold() ?> signatures
by the time it closes, you will be sent a response from
<?=OPTION_SITE_NAME=='number10'?'the Government':OPTION_SITE_PETITIONED?> by email.
<? } ?>
</p>

<p>All petitions that are submitted to this website will be accepted, as long as
they are in accordance with our <a href="/terms">terms and conditions</a>.
The aim is to enable as many people as possible to make their views known.
</p>

<p>To ensure transparency, any petition that cannot be accepted will be listed,
along with the reasons why.
<?
    if (OPTION_SITE_NAME == 'number10') {
?>
A list of <a href="/list/rejected">rejected petitions</a>
is available on this website.
<?
    }
    echo '</p></div>';
}

