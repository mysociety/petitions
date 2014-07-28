#!/usr/bin/perl
#
# Petitions/Page.pm:
# Various HTML stuff for the petitions site.
#
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#
# $Id: Page.pm,v 1.119 2010-03-19 16:38:55 matthew Exp $
#

package Petitions::Page;

use strict;

use Carp;
use Digest::HMAC_SHA1 qw(hmac_sha1);
use Encode;
use Error qw(:try);
use MIME::Base64;
use RABX;
use File::Slurp qw(read_file);

use mySociety::DBHandle qw(dbh);
use mySociety::HTMLUtil;
use mySociety::Web qw(ent);
use mySociety::WatchUpdate;

use Petitions;
use Petitions::Cobrand;

# Work out which site we're on
sub site_name {
    my $site_name;
    if (mySociety::Config::get('SITE_NAME') =~ /,/) { 
        my @sites = split /,/, mySociety::Config::get('SITE_NAME');
        foreach (@sites) {
            if ($ENV{HTTP_HOST} eq "petitions.$_.gov.uk" || $ENV{HTTP_HOST} eq "$_.petitions.mysociety.org" || $ENV{HTTP_HOST} eq "$_.petitions.test.mysociety.org") {
                $site_name = $_;
                last;
            }
        }
        $site_name = $sites[0] unless $site_name;
    } else {
        $site_name = mySociety::Config::get('SITE_NAME');
    }
    return $site_name;
}

# Work out which group of sites we're on
sub site_group {
    my $site_group;
    if (mySociety::Config::get('SITE_NAME') =~ /,/) {
        my @sites = split /,/, mySociety::Config::get('SITE_NAME');
        $site_group = $sites[0];
    } else {
        $site_group = mySociety::Config::get('SITE_NAME');
    }
    return $site_group;
}

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
    exit(0);
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
        my @d = ( 'This is a test site for web developers only.' );
        my $today = Petitions::DB::today();
        push(@d, "Note: on this test site, the date is faked to be $today")
            if ($today ne POSIX::strftime('%Y-%m-%d', localtime()));
        $devwarning = join($q->br(), @d);
    }

    # html header shared with PHP
    my $site_name = site_name();
    my $out = read_file('../templates/' . $site_name . '/head.html');
    utf8::decode($out); # binmode argument on read_file simply just sets O_BINARY
    if (!$out) {
        warn "Couldn't find ../templates/" . $site_name . "/head.html";
        return "";
    }
    my $ent_url = ent($q->url());
    (my $ent_url_no_http = $ent_url) =~ s{http://}{};
    my $ent_title = ent($title);
    my $creator = $params{creator} || mySociety::Config::get('SITE_PETITIONED');
    my $description = $params{description} || 'Petitions to ' . mySociety::Config::get('SITE_PETITIONED');
    my $subjects = '';
    if ($params{category}) {
        $subjects = '<meta name="dc.subject" scheme="eGMS.IPSV" content="' . $params{category} . '" />';
        $out =~ s/(<meta name="keywords" content="[^"]*)(" \/>)/$1, $params{category}$2/;
    } else {
        $subjects = '<meta name="dc.subject" content="10 Downing Street" />
<meta name="dc.subject" content="Petitions" />
<meta name="dc.subject" content="Prime Minister" />';
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
    $out =~ s/PARAM_DOMAIN_PATH/$ent_url_no_http/;
    $out =~ s/PARAM_TITLE/$ent_title/g;
    $out =~ s/PARAM_H1/$ent_title/g;
    $out =~ s/PARAM_DEV_WARNING/$devwarning/;
    $out =~ s/PARAM_RSS_LINKS//g;
    $out =~ s/PARAM_BODYID//g;

    my $date = POSIX::strftime('%e-%b-%Y', localtime());
    $out =~ s/PARAM_DATE/$date/;

    # Currently, no need to follow links from CGI-generated pages -
    # no need to index list of names either
    $out =~ s/index,follow/noindex,nofollow/;
    return $out;
}

=item footer Q STAT_CODE

=cut
sub footer ($$) {
    my ($q, $stat_code) = @_;
    
    my $site_name = site_name();
    my $out = read_file('../templates/' . $site_name . '/foot.html');
    utf8::decode($out);
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
    $details = '<a href="#detail" id="to_detail">More details</a>'
        if (exists($params{detail}) && $p->{detail} && Petitions::show_part($p, 'detail'));
    return
        $q->div({ -class => 'petition_box' },
            $q->p({ -style => 'margin-top: 0' },
                (exists($params{href}) ? qq(<a href="@{[ ent($params{href}) ]}">) : ''),
                Petitions::sentence($p, 1),
                (exists($params{href}) ? '</a>' : ''),
                $details
            ),
            $q->p({ -class => 'banner' }, $meta)
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
        body_id => $p->{body_id},
        body_area_id => $p->{body_area_id},
        body_ref => $p->{body_ref},
        body_name => $p->{body_name},
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

    my $must;
    if (my @area = Petitions::Cobrand::within_area_only()) {
        $must = "You must live, work or study within $area[0] to sign a petition. ";
    } elsif ($p->{body_area_id} || ($p->{body_name} && $p->{body_name} =~ /council/i) || mySociety::Config::get('SITE_PETITIONED') =~ /council/i) {
        $must = '';
        #$must = 'You need to live, work or study within the council area to sign the petition. ';
    } else {
        $must = 'You must be a British citizen or resident to sign the petition. ';
    }

    my $overseas = Petitions::Cobrand::overseas_dropdown();
    my $expat = '';
    if (@$overseas) {
        $expat = $q->p( '<label class="wide" for="overseas">Or, if you\'re an
        expatriate, you\'re in an overseas territory, a Crown dependency or in
the Armed Forces without a postcode, please select from this list:</label>', 
            $q->popup_menu(-name=>'overseas', -id=>'overseas', -style=>'width:100%', -values=> $overseas)
        );
    }

    my $address = '';
    if (Petitions::Cobrand::do_address_lookup()) {
        if ($q->scratch()->{address_lookup}) {
            $address = $q->p( '<label class="wide" for="address">Your address (will not be published):</label><br />',
                $q->popup_menu(-name=>'address', -id=>'address', -style=>'width:100%', -values=> $q->scratch()->{address_lookup})
            );
        }
    } elsif (my $address_label = Petitions::Cobrand::ask_for_address()) {
        $address = $q->p( '<label class="wide" for="address">' . $address_label . ':</label><br />',
            $q->textarea(-name => 'address', -id => 'address', -cols => 30, -rows => 4, -style => 'width:95%', -aria_required => 'true')
        );
    }

    my $address_type = '';
    if (Petitions::Cobrand::ask_for_address_type()) {
        my $q_address_type = $q->param('address_type') || '';
        my $checked_home = $q_address_type eq 'home' ? ' checked' : '';
        my $checked_work = $q_address_type eq 'work' ? ' checked' : '';
        my $checked_study = $q_address_type eq 'study' ? ' checked' : '';
        $address_type .= $q->p(
            $q->span({-class => 'label'}, 'This is where you:'),
            "<span class='address-type-choices'>
            <input type='radio' id='address_type_home' name='address_type' value='home'$checked_home>
            <label class='wide' for='address_type_home'>Live</label>
            <input type='radio' id='address_type_work' name='address_type' value='work'$checked_work>
            <label class='wide' for='address_type_work'>Work</label>
            <input type='radio' id='address_type_study' name='address_type' value='study'$checked_study>
            <label class='wide' for='address_type_study'>Study</label>
            </span>"
        );
    }

    my $postcode_label = 'Your postcode:';

    my $action = Petitions::url($p->{body_ref}, $p->{ref}) . "sign";
    my $body_ref = '';
    if (mySociety::Config::get('SITE_TYPE') eq 'multiple') {
        $body_ref = '<input type="hidden" name="body" value="' . ent($p->{body_ref}) . '" />';
    }

    my $submit = 'Sign and submit';
    if (Petitions::Cobrand::do_address_lookup()) {
        unless ($q->scratch()->{address_lookup}) {
            $submit = 'Look up address';
        }
    }

    my $button_class = Petitions::Cobrand::button_class();
    my $signFormLeft_class = Petitions::Cobrand::signFormLeft_class();
    my $signFormRight_class = Petitions::Cobrand::signFormRight_class();

    my $name_only = Petitions::Cobrand::name_only_text()
        || 'Please enter your name only; signatures containing other text may be removed by the petitions team.';

    return
        $q->start_form(-id => 'signForm', -name => 'signForm', -method => 'POST', -action => $action)
        . qq(<input type="hidden" name="add_signatory" value="1" />)
        . qq(<input type="hidden" name="ref" value="@{[ ent($p->{ref}) ]}" />)
        . $body_ref
        . qq(<input type="hidden" name="ser" value="@{[ ent($ser) ]}" />)
        . $q->div({ -id => 'signFormLeft', -class => $signFormLeft_class },
          $q->p( $must . $name_only ),
          $q->p("I, ",
                $q->textfield(
                    -name => 'name', -id => 'name', -size => 20, -aria_required => 'true'
                ),
                ", sign up to the petition."
            )
        . $q->p( '<label for="email">Your email:</label>',
                $q->textfield(-name => 'email', -size => 20, -id => 'email', -aria_required => 'true'))
        . $q->p( '<label for="email2">Confirm email:</label>',
                $q->textfield(-name => 'email2', -size => 20, -id => 'email2', -aria_required => 'true', -autocomplete => 'off'))
        . $q->p({ -id => 'ms-email2-note-signup'}, $q->strong('Your email will not be published,'), 'and is collected only to confirm your account and to keep you informed of response to this petition.')
        )
        . $q->div({-id => 'signFormRight', -class => $signFormRight_class },
          $address,
          $q->p( '<label for="postcode">' . $postcode_label . '</label>', 
                $q->textfield(-name => 'postcode', -size => 10, -id => 'postcode')
        ),
        $address_type,
        $expat,
        $q->p( { -id => 'signatureSubmit', -class => 'leading' },
            $q->submit(-class => $button_class, -name => 'submit', -value => $submit)
        )
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
        my $title = 'Petition update';
        $title .= ' ' . (@responses-$i) if @responses > 1;
        $title .= ' from the council, ' . Petitions::pretty_date($responsedate);
        $title .= ', while petition was still open' if ($responsedate lt $p->{deadline});
        $out .= Petitions::Cobrand::main_heading($title) .
            mySociety::HTMLUtil::nl2br(mySociety::HTMLUtil::ms_make_clickable(ent($response)));
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
    my $remit = 'It was outside the remit or powers of ' . mySociety::Config::get('SITE_PETITIONED');
    my %categories = (
        1 => 'It contained party political material',
        2 => 'It contained potentially libellous, false, or defamatory statements',
        4 => 'It contained information which may be protected by an injunction or court order',
        8 => 'It contained material which is potentially confidential, commercially sensitive, or which may cause personal distress or loss',
        16 => 'It contained the names of individual officials of public bodies, not part of the senior management of those organisations',
        32 => 'It contained the names of family members of elected representatives or officials of public bodies',
        64 => 'It contained the names of individuals, or information where they may be identified, in relation to criminal accusations',
        128 => 'It contained language which is offensive, intemperate, or provocative',
        256 => 'It contained wording that needed to be amended, or is impossible to understand',
        512 => 'It doesn\'t actually request any action',
        1024 => 'It was commercial endorsement, promotion of a product, service or publication, or statements that amounted to adverts',
        2048 => 'It was similar to and/or overlaps with an existing petition or petitions',
        4096 => $remit,
        8192 => 'It contained false or incomplete name or address information',
        16384 => 'It was an issue for which an e-petition is not the appropriate channel',
        32768 => 'It was intended to be humorous, or have no point about government policy',
        65536 => 'It contained links to websites',
        131072 => 'It is currently being administered via another process', # for Bassetlaw council
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
    $out .= $q->p('Additional information about this rejection:<br />' . $reject_reason)
        if $reject_reason;
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

    my $signatories_class = Petitions::Cobrand::signatories_class();
    my $html =
        $q->start_div({-id => 'signatories', -class => $signatories_class })
            . Petitions::Cobrand::main_heading('<a name="signers"></a>Current signatories');

    if ($p->{signers} == 1 && !$q->param('signed')) {
        $html .=
            $q->p("So far, only @{[ ent($p->{name}) ]}, the petition creator, has signed this petition.")
            . $q->end_div();
        return $html;
    }
    
    my $st;
    my $showall = $q->param('showall') ? 1 : 0;      # ugh
    my $reverse = 0;
    if ($p->{cached_signers} > MAX_PAGE_SIGNERS && !$showall) {
        $html .=
            $q->p("Because there are so many signatories, only the most recent",
                MAX_PAGE_SIGNERS, "are shown on this page.");
        $reverse = 1;
        if ($p->{open}) {
            $st = dbh()->prepare("
                select name from signer
                where petition_id = ? and showname = 't' and emailsent = 'confirmed'
                order by signtime desc
                limit @{[ MAX_PAGE_SIGNERS ]}");
            $st->execute($p->{id});
        } else {
            $st = dbh()->prepare("
                select name from signer
                where petition_id = ? and showname = 't' and emailsent = 'confirmed'
                    and signtime < (select deadline+1 from petition where id = ?)
                order by signtime desc
                limit @{[ MAX_PAGE_SIGNERS ]}");
            $st->execute($p->{id}, $p->{id});
        }
    } else {
        $html .=
            $q->p("@{[ ent($p->{name}) ]}, the petition creator, joined by:");
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

    if (defined $p->{offline_signers}) {
        my $signers = $p->{offline_signers};
        $signers =~ s/(\d+?(?=(?>(?:\d{3})+)(?!\d))|\G\d{3}(?=\d))/$1,/g;
        $html .= '<li>' . $signers . ' offline signature' .
            ($p->{offline_signers}==1?'':'s') . '</li>';
    }

    $html .= '</ul>';
    
    if ($p->{cached_signers} > MAX_PAGE_SIGNERS && !$showall) {
        $html .=
            $q->p("Because there are so many signatories, only the most recent",
                MAX_PAGE_SIGNERS, "are shown on this page.");
        $html .= $q->p($q->a({ -href => "?showall=1" }, "Show all signatories"))
            unless $p->{ref} eq 'traveltax' || $p->{ref} eq 'RedArrows2012' || $p->{ref} eq 'Lowerduty30';
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
