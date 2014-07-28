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
    select petition.ref, content,
        cached_signers + coalesce(offline_signers, 0) as signers,
        body.ref as body_ref, body.name as body_name
    from petition, body
    where status = 'live' and body_id = body.id
    $body
    order by signers desc limit 5", $params);
} else {
    $recent = db_getAll("select ref, content from petition
    where status = 'live'
    order by laststatuschange desc limit 5");
    $most = db_getAll("
    select ref, content,
        cached_signers + coalesce(offline_signers, 0) as signers
    from petition
    where status = 'live'
    order by signers desc limit 5");
}

// Lame: send last-modified now to encourage cacheing.
cond_headers(time());
header('Cache-Control: max-age=5');
page_header('Introduction to e-petitions', array(
    'rss' => array(
        'Latest Petitions' => '/rss/list'
    )
));

if (cobrand_creation_disabled()) {
	page_closed_message(true);
};

if (OPTION_SITE_NAME != 'number10') {
    if (OPTION_SITE_NAME == 'sbdc')
        echo '<h2>Make or sign petitions through this official Borsetshire District Council petitions website</h2>';
    front_actions();
    front_intro_text();
    if (!cobrand_creation_disabled()) {
        front_most_popular($most);
        if (OPTION_SITE_NAME == 'sbdc' || OPTION_SITE_NAME == 'sbdc1') {
            print '<div style="float: left; text-align: center; padding-top:0.5em; width: 45%; padding: 5px;">';
            pet_search_form(array('front'=>true));
            print '</div>';
        }
        front_most_recent($recent);
        front_how_it_works();
    }
} else {
    echo '<div id="content_clipboard">';
    front_actions();
    front_intro_text();
    pet_search_form(array('front'=>true));
    if (cobrand_creation_disabled()) {
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
    echo '<div id="petition_actions" class="' . cobrand_petition_actions_class() . '"> <ul>';
    if (! cobrand_creation_disabled()) {
        echo '<li id="action_create"><a href="/new"' . cobrand_create_button_title() . '><img src="/images/clipboard-add.gif" alt="" class="noborder"
/><br />Create a petition</a></li>';
    }

    echo '<li id="action_view"><a href="/list"' . cobrand_view_button_title() . '><img src="/images/clipboard-write.gif" alt="" class="noborder"
/><br />View petitions</a></li>
</ul>
</div>';
}

function front_intro_text() {
    if (OPTION_SITE_NAME == 'councils') {
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
    if (isset($petition['signers'])) {
        print ' <small>(';
        print $petition['signers'] . ' signature';
        print ($petition['signers'] == 1 ? '' : 's') . ')</small>';
    }
    print '</li>';
}

function front_most_recent($recent) {
    echo "<div id='most_recent' class='" . cobrand_most_recent_class() . "'>";
    echo cobrand_main_heading('Most recent petitions');
    # If cross-site in future, will need to display name within each row
    if (count($recent)) {
        echo '<p>We the undersigned petition ' . OPTION_SITE_PETITIONED . ' to&hellip;</p>';
    }
    echo '<ul>';
    $c = 1;
    foreach ($recent as $petition) {
        petition_row($petition, $c++);
    }
    if (!count($recent)) {
        if (cobrand_creation_disabled()) {
            print '<li><em>There are currently no petitions.</em></li>';
        } else {
            print '<li><em>None</em>; you can <a href="/new">create a petition</a>.</li>';
        }
    }
?>
</ul>
<p class="leading"><a href="/list/open?sort=date">More recent petitions</a></p>
</div>
<?
}

function front_most_popular($most) {
    echo '<div id="most_popular" class="' . cobrand_most_popular_class() . '">';
    echo cobrand_main_heading('Most popular open petitions');
    if (count($most)) {
        echo '<p>We the undersigned petition ' . OPTION_SITE_PETITIONED . ' to&hellip;</p>';
    }
    echo '<ul>';
    $c = 1;
    foreach ($most as $petition) {
        petition_row($petition, $c++);
    }
    if (!count($most)) {
        if (cobrand_creation_disabled()) {
            print '<li><em>There are currently no petitions.</em></li>';
        } else {
            print '<li><em>None</em>; you can <a href="/new">create a petition</a>.</li>';
        }
    }
?>
</ul>
<p class="leading"><a href="/list/open?sort=signers">More popular petitions</a></p>
</div>
<?
}

function front_how_it_works() {
    echo '<div id="front_how" class="' . cobrand_front_how_class() . '">';
    echo cobrand_main_heading('How it works');
    cobrand_how_it_works_start();
?>
<p>All petitions that are submitted to this website will be accepted, as long as
they are in accordance with our <? cobrand_extra_terms_link() ?>
<a href="/terms"><?=cobrand_terms_text()?></a>.
The aim is to enable as many people as possible to make their views known.
</p>

<p>To ensure transparency, any petition that cannot be accepted will be listed,
along with the reasons why.

<?
    cobrand_how_it_works_extra();
    echo '</p></div>';
}

