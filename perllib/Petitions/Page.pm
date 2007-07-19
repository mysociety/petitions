#!/usr/bin/perl
#
# Petitions/Page.pm:
# Various HTML stuff for the petitions site.
#
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#
# $Id: Page.pm,v 1.90 2007-07-19 14:07:23 matthew Exp $
#

package Petitions::Page;

use strict;

use Carp;
use Digest::HMAC_SHA1 qw(hmac_sha1);
use MIME::Base64;
use RABX;
use File::Slurp qw(read_file);

use mySociety::DBHandle qw(dbh);
use mySociety::Web qw(ent);
use mySociety::WatchUpdate;

use Petitions;

sub do_fastcgi {
    my $func = shift;

    try {
        my $W = new mySociety::WatchUpdate();
        while (my $q = new mySociety::Web()) {
            &$func($q);
            $W->exit_if_changed();
        }
    } catch Error::Simple with {
        my $E = shift;
        my $msg = sprintf('%s:%d: %s', $E->file(), $E->line(), $E->text());
        warn "caught fatal exception: $msg";
        warn "aborting";
        ent($msg);
        print "Status: 500\nContent-Type: text/html; charset=iso-8859-1\n\n",
                q(<html><head><title>Sorry! Something's gone wrong.</title></head></html>),
                q(<body>),
                q(<h1>Sorry! Something's gone wrong.</h1>),
                q(<p>Please try again later, or <a href="mailto:team@mysociety.org">email us</a> to let us know.</p>),
                q(<hr>),
                q(<p>The text of the error was:</p>),
                qq(<blockquote class="errortext">$msg</blockquote>),
                q(</body></html);
    };
}

=item header Q TITLE [PARAM VALUE ...]

Return HTML for the top of the page, given the TITLE text and optional PARAMs.

=cut
sub header ($$%) {
    my ($q, $title, %params) = @_;
    
    my %permitted_params = map { $_ => 1 } qw(creator description category status signers deadline);
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
    $js = '<script type="text/javascript" src="http://www.pm.gov.uk/include/js/nedstat.js"></script>' unless (mySociety::Config::get('PET_STAGING'));
    my $creator = $params{creator} || '10 Downing Street, Web Team, webmaster@pmo.gov.uk';
    my $description = $params{description} || 'Petitions to the Prime Minister, 10 Downing Street';
    my $subjects = '';
    if ($params{category}) {
        $subjects = '<meta name="dc.subject" scheme="eGMS.IPSV" content="' . $params{category} . '" />';
        $out =~ s/(<meta name="keywords" content="[^"]*)(" \/>)/$1, $params{category}$2/;
    } else {
        $subjects = '<meta name="dc.subject" content="10 Downing Street" />
<meta name="dc.subject" content="Petitions" />
<meta name="dc.subject" content="Prime Minister" />
<meta name="dc.subject" content="Tony Blair" />';
    }
    my $extra = '';
    $extra .= '<meta name="eGMS.status" content="' . $params{status} . '" />' if $params{status};
    $extra .= '<meta name="quantSignatures" content="' . $params{signers} . '" />' if $params{signers};
    $extra .= '<meta name="closingDate" content="' . $params{deadline} . '" />' if $params{deadline};
    $out =~ s/PARAM_CREATOR/$creator/;
    $out =~ s/PARAM_DESCRIPTION/$description/;
    $out =~ s/PARAM_EXTRA/$extra/;
    $out =~ s/PARAM_SUBJECTS/$subjects/;
    $out =~ s/PARAM_DC_IDENTIFIER/$ent_url/;
    $out =~ s/PARAM_TITLE/$ent_title/g;
    $out =~ s/PARAM_DEV_WARNING/$devwarning/;
    $out =~ s/PARAM_STAT_JS/$js/g;
    $out =~ s/PARAM_RSS_LINKS//g;
    # Currently, no need to follow links from CGI-generated pages -
    # no need to index list of names either
    $out =~ s/index,follow/noindex,nofollow/;
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
    my $org = Petitions::show_part($p, 'organisation') ? ent($p->{organisation}) : '';
    my $org_url = Petitions::show_part($p, 'org_url') ? ent($p->{org_url}) : '';
    if ($org) {
        if ($org_url) {
            $org_url = "http://$org_url" unless $org_url =~ /^http:\/\//;
            $org = '<a rel="nofollow" href="' . $org_url . '">' . $org . '</a>';
        }
        $org = ' of ' . $org;
    } elsif ($org_url) {
        $org_url = "http://$org_url" unless $org_url =~ /^http:\/\//;
        $org = '<a rel="nofollow" href="' . $org_url . '">' . $org_url . '</a>';
        $org = ', ' . $org;
    }
    my $name = Petitions::show_part($p, 'name') ? ent($p->{name}) : '&lt;Name cannot be shown&gt;';
    my $meta = 'Submitted by ' . $name . $org;
    if ($p->{status} ne 'rejected') {
        my $signers = $p->{signers};
        $signers =~ s/(\d+?(?=(?>(?:\d{3})+)(?!\d))|\G\d{3}(?=\d))/$1,/g;
        $meta .= ' &ndash; ' . 
                $q->strong('Deadline to sign up by: ') . Petitions::pretty_deadline($p, 1) .
                (defined($p->{signers})
                    ? ' &ndash; ' . $q->strong('Signatures:') . '&nbsp;' . $signers
                    : '');
    }
    my $details = '';
    $details = '<a href="#detail"><small>More details</small></a>'
        if (exists($params{detail}) && $p->{detail} && Petitions::show_part($p, 'detail'));
    return
        $q->div({ -class => 'petition_box' },
            $q->p({ -style => 'margin-top: 0' },
                (exists($params{href}) ? qq(<a href="@{[ ent($params{href}) ]}">) : ''),
                Petitions::sentence($p, 1),
                (exists($params{href}) ? '</a>' : ''),
                $details
            ),
            $q->p({ -align => 'center' }, $meta)
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

    my $safe_p = {
        deadline => $p->{deadline},
        'ref' => $p->{ref},
        organisation => $p->{organisation},
        org_url => $p->{org_url},
        name => $p->{name},
        status => $p->{status},
        signers => $p->{signers},
        content => $p->{content},
        rejection_hidden_parts => $p->{rejection_hidden_parts},
    };
    $safe_p->{salt} = "\0\0\0\0";
    my $buf = RABX::serialise($safe_p);
    my $ser = encode_base64($buf . hmac_sha1($buf, Petitions::DB::secret()), '');
    delete($safe_p->{salt});

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
                $q->textfield(-name => 'email', -size => 25, -id => 'email'))
        . $q->p( '<label for="email2">Confirm email:</label>',
                $q->textfield(-name => 'email2', -size => 25, -id => 'email2'))
        . $q->p($q->small($q->strong('Your email will not be published,'), 'and is collected only to confirm your account and to keep you informed of response to this petition.'))
        )
        . $q->div({-id => 'signFormRight' },
          $q->p( 'You must be a British citizen or resident to sign the petition.'),
          $q->p( '<label class="wide" for="address">Your address (will not be published):</label><br />',
                $q->textarea(-name => 'address', -id => 'address', -cols => 30, -rows => 4, -style => 'width:100%') ),
          $q->p( '<label for="postcode">UK postcode:</label>', 
                $q->textfield(-name => 'postcode', -size => 10, -id => 'postcode')
        ),
        $q->p( '<label class="wide" for="overseas">Or, if you\'re an
        expatriate, you\'re in an overseas territory, a Crown dependency or in
the Armed Forces without a postcode, please select from this list:</label>', 
            $q->popup_menu(-name=>'overseas', -id=>'overseas', -style=>'width:100%', -values=>[
                '-- Select --',
                'Expatriate',
                'Armed Forces',
                'Anguilla',
                'Ascension Island',
                'Bermuda',
                'British Antarctic Territory',
                'British Indian Ocean Territory',
                'British Virgin Islands',
                'Cayman Islands',
                'Channel Islands',
                'Falkland Islands',
                'Gibraltar',
                'Isle of Man',
                'Montserrat',
                'Pitcairn Island',
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
    my $responses = $p->{response};
    $responses =~ s#\n\nPetition info: http://.*##;
    # XXX: Should be in HTMLEmail.pm, copied from there
    $responses =~ s/\[([^ ]*)\]/$1/gs;
    $responses =~ s/\[([^ ]*) ([^]]*?)\]((?:\.|;|,)?)$/"$2 - $1".($3?" $3":'')/egsm;
    $responses =~ s/\[([^ ]*) (.*?)\]/$2 - $1 -/gs;
    my @responses = split /\|\|\|/, $responses;
    my @responsetimes = split /\|\|\|/, $p->{responsetime};
    my $out = '<div id="response">';
    for (my $i=0; $i<@responses; $i++) {
        my $response = $responses[$i];
        (my $responsedate = $responsetimes[$i]) =~ s/ .*//;
        my $title = 'Government Response';
        $title .= ' ' . ($i+1) if @responses > 1;
        $title .= ', ' . Petitions::pretty_date($responsedate);
        $title .= ', while petition was still open' if ($responsedate <= $p->{deadline});
        $out .= $q->h2($q->span({-class => 'ltr'}, $title)) .
            mySociety::Util::nl2br(mySociety::Util::ms_make_clickable(ent($response)));
    }
    $out .= '</div>';
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
        1 => 'It contained party political material',
        2 => 'It contained potentially libellous, false, or defamatory statements',
        4 => 'It contained information which may be protected by an injunction or court order',
        8 => 'It contained material which is potentially confidential, commercially sensitive, or which may cause personal distress or loss',
        16 => 'It contained the names of individual officials of public bodies, not part of the senior management of those organisations',
        32 => 'It contained the names of family members of elected representatives or officials of public bodies',
        64 => 'It contained the names of individuals, or information where they may be identified, in relation to criminal accusations',
        128 => 'It contained language which is offensive, intemperate, or provocative',
        256 => 'It contained wording that is impossible to understand',
        512 => 'It doesn\'t actually request any action',
        1024 => 'It was commercial endorsement, promotion of a product, service or publication, or statements that amounted to adverts',
        2048 => 'It was similar to and/or overlaps with an existing petition or petitions',
        4096 => 'It was outside the remit or powers of the Prime Minister and Government',
        8192 => 'It contained false name or address information',
        16384 => 'It was an issue for which an e-petition is not the appropriate channel',
        32768 => 'It was intended to be humorous, or have no point about government policy',
        65536 => 'It contained links to websites',
        # XXX also change in phplib/petition.php
    );

    my $reject_reason = $p->{rejection_second_reason};
    my $reject_cats = $p->{rejection_second_categories} + 0; # Need it as an integer

    my $out = $q->p('This petition has been <strong>rejected</strong> because:');
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

    if ($p->{signers} == 1) {
        $html .=
            $q->p("So far, only @{[ ent($p->{name}) ]}, the Petition Creator, has signed this petition.")
            . $q->end_div();
        return $html;
    }
    
    my $st;
    my $showall = $q->param('showall') ? 1 : 0;      # ugh
    my $reverse = 0;
    if ($p->{signers} > MAX_PAGE_SIGNERS && !$showall) {
        $html .=
            $q->p("Because there are so many signatories, only the most recent",
                MAX_PAGE_SIGNERS, "are shown on this page.");
        $st = dbh()->prepare("
                select name from signer
                where petition_id = ? and showname = 't' and emailsent = 'confirmed'
                    and signtime < (select deadline+1 from petition where id = ?)
                order by signtime desc
                limit @{[ MAX_PAGE_SIGNERS ]}");
        $reverse = 1;
        $st->execute($p->{id}, $p->{id});
    } else {
        $html .=
            $q->p("@{[ ent($p->{name}) ]}, the Petition Creator, joined by:");
        $st = dbh()->prepare("
                select name from signer
                where petition_id = ? and showname = 't' and emailsent = 'confirmed'
                order by signtime");
        $st->execute($p->{id});
    }

    $html .= '<ul>';

    if ($reverse) {
        my @names;
        while (($_) = $st->fetchrow_array()) {
            push(@names, ent($_));
        }
        @names = reverse(@names);
        foreach (@names) {
            $html .= "<li>$_</li>";
        }
    } else {
        while (($_) = $st->fetchrow_array()) {
            $html .= "<li>" . ent($_) . "</li>";
        }
    }

    $html .= '</ul>';
    
    if ($p->{signers} > MAX_PAGE_SIGNERS && !$showall) {
        $html .=
            $q->p("Because there are so many signatories, only the most recent",
                MAX_PAGE_SIGNERS, "are shown on this page.");
        $html .= $q->p($q->a({ -href => "?showall=1" }, "Show all signatories"))
            unless $p->{ref} eq 'traveltax';
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
