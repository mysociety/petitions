#!/usr/bin/perl
#
# Petitions/Page.pm:
# Various HTML stuff for the petitions site.
#
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#
# $Id: Page.pm,v 1.48 2006-11-10 12:40:26 matthew Exp $
#

package Petitions::Page;

use strict;

use Carp;
use Digest::HMAC_SHA1 qw(hmac_sha1);
use MIME::Base64;
use RABX;
use File::Slurp qw(read_file);

use mySociety::DBHandle qw(dbh);
use mySociety::Util qw(random_bytes);
use mySociety::Web qw(ent);

use Petitions;

=item header Q TITLE [PARAM VALUE ...]

Return HTML for the top of the page, given the TITLE text and optional PARAMs.

=cut
sub header ($$%) {
    my ($q, $title, %params) = @_;
    
    my %permitted_params = map { $_ => 1 } qw();
    foreach (keys %params) {
        croak "bad parameter '$_'" if (!exists($permitted_params{$_}));
    }

    my $devwarning = '';;
    if (mySociety::Config::get('PET_STAGING')) {
        my @d = (
                'This is a test site for web developers only.',
                q(You probably want <a href="http://www.pm.gov.uk">the Prime Minister's official site</a>.)
            );
        my $today = Petitions::DB::today();
        push(@d, "Note: on this test site, the date is faked to be $today")
            if ($today ne POSIX::strftime('%Y-%m-%d', localtime()));
        $devwarning = join($q->br(), @d);
    }

    # html header shared with PHP
    my $out = read_file("../templates/website/head.html");
    if (!$out) {
        warn "Couldn't find ../templates/website/head.html";
        return "";
    }
    my $ent_url = ent($q->url());
    my $ent_title = ent($title);
    my $js = '';
    $js = '<script type="text/javascript" src="http://www.number10.gov.uk/include/js/nedstat.js"></script>' unless (mySociety::Config::get('PET_STAGING'));
    $out =~ s/PARAM_DC_IDENTIFIER/$ent_url/g;
    $out =~ s/PARAM_TITLE/$ent_title/g;
    $out =~ s/PARAM_DEV_WARNING/$devwarning/g;
    $out =~ s/PARAM_STAT_JS/$js/g;
    $out =~ s/PARAM_RSS_LINKS//g;

    return $out;
}

=item footer Q STAT_CODE

=cut
sub footer ($$) {
    my ($q, $stat_code) = @_;
    if ($stat_code) {
        $stat_code = "Petitions.$stat_code";
    } else {
        $stat_code = 'Petitions';
    }
    
    # html footer, shared with PHP
    my $site_stats = "";
    if (!mySociety::Config::get('PET_STAGING')) {
        $site_stats = read_file("../templates/website/site-stats.html");# || die "couldn't open site-stats.html: $!";
        $site_stats =~ s/PARAM_STAT_CODE/$stat_code/g;
    }
    my $out = read_file("../templates/website/foot.html");# || die("couldn't open foot.html: $!");
    $out =~ s/PARAM_SITE_STATS/$site_stats/g;

    return $out;
}

=item error_page Q MESSAGE

=cut
sub error_page ($$) {
    my ($q, $message) = @_;
    my $html = header($q, "Error")
            . $q->p($message)
            . footer($q, 'Error');
    print $q->header(-content_length => length($html)), $html;
}

=item bad_ref_page Q REF

Emit a helpful error page for a bad or undefined petition reference REF.

=cut
sub bad_ref_page ($$) {
    my ($q, $ref) = @_;
    my $html =
        header($q, "We couldn't find that petition");

    if (defined($ref)) {
        $html .= $q->p(qq(We couldn't find any petition with a reference like "@{[ ent($ref) ]}". Please try the following:));
    } else {
        $html .= $q->p(qq(We're not sure which petition you're looking for. Please try the following:));
    }

    $html .=
        $q->ul(
            $q->li([
            q(If you typed in the location, check it carefully and try typing it again.),
            q(Look for the petition on <a href="/list">the list of all petitions</a>.)
            ])
        );    
    
    $html .= footer($q, 'Bad_ref');
 
    print $q->header(-content_length => length($html)), $html;
}

=item display_box Q PETITION PARAMS

Return a div displaying the given PETITION (ref or hash of fields to values). PARAMS

=cut
sub display_box ($$%) {
    my ($q, $p, %params) = @_;
    if (!ref($p)) {
        my $ref = $p;
        $p = Petitions::DB::get($ref)
            or croak "bad ref '$ref' in display_box";
    }
    my $org = '';
    if ($p->{organisation}) {
        $org = ent($p->{organisation});
        my $org_url = ent($p->{org_url});
        $org_url = "http://$org_url" unless $org_url =~ /^http:\/\//;
        $org = '<a href="' . $org_url . '">' . $org . '</a>' if $p->{org_url};
        $org = ' of ' . $org;
    }
    return
        $q->div({ -class => 'petition_box' },
            $q->p({ -style => 'margin-top: 0' },
                (exists($params{href}) ? qq(<a href="@{[ ent($params{href}) ]}">) : ''),
                Petitions::sentence($p, 1),
                (exists($params{href}) ? '</a>' : '')
            ),
            $q->p({ -align => 'center' },
                'Submitted by ', ent($p->{name}), $org, ' &ndash; ',
                $q->strong('Deadline to sign up by:'), Petitions::pretty_deadline($p, 1),
                (defined($p->{signers})
                    ? (' &ndash; ', $q->strong('Signatures:') . '&nbsp;' . $p->{signers})
                    : ())
            )
        );

}

=item sign_box Q PETITION

Return a signup form for the given PETITION (ref or hash of fields to values).

=cut
sub sign_box ($$) {
    my ($q, $p) = @_;
    if (!ref($p)) {
        my $ref = $p;
        $p = Petitions::DB::get($p)
            or croak "bad ref '$ref' in sign_box";
    }

    $p->{salt} = random_bytes(4);
    my $buf = RABX::serialise($p);
    my $ser = encode_base64($buf . hmac_sha1($buf, Petitions::DB::secret()), '');
    delete($p->{salt});

    return
        $q->start_form(-id => 'signForm', -name => 'signForm', -method => 'POST', -action => "/$p->{ref}/sign")
        . qq(<input type="hidden" name="add_signatory" value="1" />)
        . qq(<input type="hidden" name="ref" value="@{[ ent($p->{ref}) ]}" />)
        . qq(<input type="hidden" name="ser" value="@{[ ent($ser) ]}" />)
#        . $q->h2($q->span({-class => 'ltr'}, 'Sign up now'))
        . $q->div({ -id => 'signFormLeft' }, 
          $q->p("I, ",
                $q->textfield(
                    -name => 'name', -id => 'name', -size => 20
                ),
                " sign up to the petition."
            )
        . $q->p( '<label for="email">Your email:</label>',
                $q->textfield(-name => 'email', -size => 30, -id => 'email'))
        . $q->p( '<label for="email2">Confirm email:</label>',
                $q->textfield(-name => 'email2', -size => 30, -id => 'email2'),
                $q->br(),
            $q->small($q->strong('Your email will not be published,'), 'and is collected only to confirm your account and to keep you informed of response to this petition.')
        ) )
        . $q->div({-id => 'signFormRight' },
          $q->p( 'You must be a British citizen to sign the petition.'),
          $q->p( '<label class="wide" for="address">Your address (will not be published):</label><br />',
                $q->textarea(-name => 'address', -id => 'address', -cols => 30, -rows => 4) ),
          $q->p( '<label for="postcode">Your postcode:</label>', 
                $q->textfield(-name => 'postcode', -size => 10, -id => 'postcode')
        ),
        $q->p( '<label class="wide" for="overseas">Or, if you\'re
in an overseas territory or Crown dependency and don\'t have a postcode,
please select it from the list:</label>', 
            $q->popup_menu(-name=>'overseas', -id=>'overseas', -values=>[
                '-- Select --',
                'Ascension Island',
                'Bermuda',
                'British Antarctic Territory',
                'Cayman Islands',
                'Channel Islands',
                'Falkland Islands',
                'Gibraltar',
                'Isle of Man',
                'Montserrat',
                'St Helena',
                'S. Georgia and the S. Sandwich Islands',
                'Tristan da Cunha',
                'Turks and Caicos Islands',
                ])
        )
        )
        . $q->p( {-style => 'clear: both', -align => 'right'},
            $q->submit(-name => 'submit', -value => 'Sign')
        )
        . $q->end_form();
}

=item response_box Q PETITION

=cut

sub response_box ($$) {
    my ($q, $p) = @_;
    my $out = $q->div({-id => 'response'},
        $q->h2($q->span({-class => 'ltr'}, 'Government Response')),
        $p->{response} # Presumably will need formatting! XXX
    );
    return $out;
}

=item reject_box Q PETITION

=cut
sub reject_box ($$) {
    my ($q, $p) = @_;
    if (!ref($p)) {
        my $ref = $p;
        $p = Petitions::DB::get($p)
            or croak "bad ref '$ref' in reject_box";
    }
 
    # Must keep this synchronised with constraint in schema, and list in phplib/petition.php
    my %categories = (
        1 => 'Party political material',
        2 => 'False or defamatory statements',
        4 => 'Information protected by an injunction or court order',
        8 => 'Material which is commercially sensitive, confidential or which may cause personal distress or loss',
        16 => 'Names of individual officials of public bodies, unless part of the senior management of those organisations',
        32 => 'Names of family members of officials of public bodies, or elected representatives',
        64 => 'Names of individuals, or information where they may be identified, in relation to criminal accusations',
        128 => 'Offensive language, such as obvious swear words or language that is intemperate, inflammatory, or provocative, or to which people reading it could reasonably take offence',
        256 => 'Isn\'t clear what the petition is asking signers to endorse',
        512 => 'Doesn\'t actually ask for an action',
        1024 => 'Attempting to market a product irrelevent to the role and office of the PM',
        2048 => 'Identical to an existing petition',
        4096 => 'Outside the remit or powers of the Prime Minister and Government',
        # XXX also change in phplib/page.php
    );

    my $reject_reason = $p->{rejection_second_reason};
    my $reject_cats = $p->{rejection_second_categories} + 0; # Need it as an integer

    my $out = $q->p('This petition has been <strong>rejected</strong>, for being in the following categories:');
    $out .= '<ul>';
    foreach my $k (sort keys %categories) {
        $out .= $q->li($categories{$k}) if ($reject_cats & $k);
    }
    $out .= "</ul>\n";
    $out .= $q->p('Additional information about this rejection:<br />' . $reject_reason);
    return $out;
}

=item signatories_box Q PETITION

=cut
use constant MAX_PAGE_SIGNERS => 500;
sub signatories_box ($$) {
    my ($q, $p) = @_;
    if (!ref($p)) {
        my $ref = $p;
        $p = Petitions::DB::get($p)
            or croak "bad ref '$ref' in signatories_box";
    }

    my $html =
        $q->start_div({-id => 'signatories'})
            . $q->h2($q->span({-class => 'ltr'}, '<a name="signers"></a>Current signatories'));

    if ($p->{signers} == 0) {
        $html .=
            $q->p("So far, only @{[ ent($p->{name}) ]}, the Petition Creator, has signed this petition.")
            . $q->end_div();
        return $html;
    }
    
    my $st;
    my $showall = $q->param('showall') ? 1 : 0;      # ugh
    if ($p->{signers} > MAX_PAGE_SIGNERS && !$showall) {
        $html .=
            $q->p("Because there are so many signers, only the most recent",
                MAX_PAGE_SIGNERS, "are shown on this page.");
        $st = dbh()->prepare("
                select name from signer
                where petition_id = ? and showname and emailsent = 'confirmed'
                order by signtime
                limit @{[ MAX_PAGE_SIGNERS ]}
                offset @{[ ($p->{signers} - MAX_PAGE_SIGNERS) ]}");
    } else {
        $html .=
            $q->p("@{[ ent($p->{name}) ]}, the Petition Creator, joined by:");
        $st = dbh()->prepare("
                select name from signer
                where petition_id = ? and showname and emailsent = 'confirmed'
                order by signtime");
    }

    $html .= '<ul>';
    $st->execute($p->{id});
    while (my ($name) = $st->fetchrow_array()) {
        $html .= $q->li(ent($name));
    }
    $html .= '</ul>';

    if ($p->{signers} > MAX_PAGE_SIGNERS && !$showall) {
        $html .=
            $q->p("Because there are so many signers, only the most recent",
                MAX_PAGE_SIGNERS, "are shown on this page.")
            . $q->p($q->a({ -href => "?showall=1" },
                    "Show all signers"));
    }

    $html .= "</div>";
    return $html;
}

=item spreadword_box Q PETITION

=cut
sub spreadword_box ($$) {
    my ($q, $p) = @_;
    if (!ref($p)) {
        my $ref = $p;
        $p = Petitions::DB::get($p)
            or croak "bad ref '$ref' in spreadword_box";
    }

    if ($p->{open}) {
        return
            $q->div({ -id => 'spreadword' },
                $q->h2('Spread the word on and offline'),
                $q->ul($q->li([
                    'Email petition to your friends',
                    $q->a({
                            -href => '',
                            -title => 'Only if you made this petition'
                        },
                        'Send message to signers')
                    ])
                )
            );
    } else {
        return
            $q->div({ -id => 'spreadword' },
                $q->h2('Spread the word on and offline'),
                $q->ul($q->li([
                    $q->a({
                            -href => '',
                            -title => 'Only if you made this petition'
                        },
                        'Send message to signers')
                    ])
                )
            );
    }
}

1;
