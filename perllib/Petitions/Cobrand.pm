#!/usr/bin/perl
#
# Petitions/Cobrand.pm:
# Petition cobrand utilities.
#
# Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
# Email: matthew@mysociety.org; WWW: http://www.mysociety.org/
#

package Petitions::Cobrand;

use strict;
use mySociety::Config;
use Petitions::Page;
if (mySociety::Config::get('SITE_NAME') eq 'islington') {
    use LWP::Simple;
    use mySociety::PostcodeUtil;
    use URI::Escape;
}

sub admin_email($) {
    my $p = shift;
    return 'petitions@' . $p->{body_ref} . '.gov.uk'
        if $p->{body_ref} && $p->{body_ref} eq 'elmbridge';
    return $p->{body_ref} . '@' . mySociety::Config::get('EMAIL_DOMAIN')
        if mySociety::Config::get('SITE_TYPE') eq 'multiple';
    return mySociety::Config::get('CONTACT_EMAIL');
}

sub main_heading($) {
    my $text = shift;
    my $site_name = Petitions::Page::site_name();
    return "<h2>$text</h2>" if $site_name eq 'surreycc';
    return "<h3>$text</h3>";
}

# Currently used on check your email signing page to supply
# a heading for those templates that don't have one by default.
sub extra_heading($) {
    my $text = shift;
    my $site_name = Petitions::Page::site_name();
    return "<h2>$text</h2>"
        if $site_name =~ /tandridge|molevalley|lichfielddc|spelthorne|reigate-banstead|nottinghamshire/;
    return '';
}

sub signing_check_heading() {
    my $site_name = Petitions::Page::site_name();
    return 'You Have Signed the Petition' if $site_name eq 'islington';
    return 'Now check your email!';
}

sub approval_word() {
    my $site_name = Petitions::Page::site_name();
    return 'acceptance' if $site_name eq 'westminster';
}

sub within_area_only() {
    my $site_name = Petitions::Page::site_name();
    return ('Guildford', 2452) if $site_name eq 'guildford';
    return ('Islington', 2507) if $site_name eq 'islington';
    return ('Runnymede', 2451) if $site_name eq 'runnymede';
    return ('Salford', 2534) if $site_name eq 'salford';
    return ('Westminster', 2504) if $site_name eq 'westminster';
    return;
}

sub postcode_exemptions($) {
    my $pc = shift;
    my $site_name = Petitions::Page::site_name();
    return 1 if $site_name eq 'westminster' && uc($pc) eq 'W24LY';
    return 0;
}

sub name_only_text() {
    my $site_name = Petitions::Page::site_name();
    return '<strong>Please enter your full name</strong>. Signatures containing
        incomplete names, false names or other text may be removed by the
        petitions team.' if $site_name eq 'islington';
    return '';
}

sub ask_for_address() {
    my $site_name = Petitions::Page::site_name();
    return '' if $site_name eq 'westminster';
    return 'Your home, work or study address (this must be a Salford address, this will not be published)'
        if $site_name eq 'salford';
    return 'Your address (will not be published)';
}

sub ask_for_address_type() {
    my $site_name = Petitions::Page::site_name();
    return 1 if  $site_name eq 'runnymede' || $site_name eq 'salford' || $site_name eq 'westminster';
}

sub overseas_dropdown {
    my $site_name = Petitions::Page::site_name();
    my $site_group = Petitions::Page::site_group();
    if ($site_group eq 'westminster' || $site_group eq 'islington' || $site_group eq 'rbwm' || $site_group eq 'stevenage' || $site_group eq 'salford'
         or $site_name eq 'runnymede') {
        return []; # No drop-down
    } elsif ($site_group eq 'surreycc') {
        return [
            '-- Select --',
            'Armed Forces',
            'Non UK address',
        ];
    } else {
        return [
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
        ];
    }
}

sub success_attr() {
    my $site_name = Petitions::Page::site_name();
    return { -class => 'success' } if $site_name eq 'salford';
    return { -id =>'success' };
}

sub button_class() {
    my $site_name = Petitions::Page::site_name();
    return 'addButton' if $site_name eq 'salford';
    return 'button';
}

sub do_address_lookup() {
    my $site_name = Petitions::Page::site_name();
    return 1 if $site_name eq 'islington';
    return 0;
}

sub perform_address_lookup($) {
    my $pc = shift;
    $pc = mySociety::PostcodeUtil::canonicalise_postcode($pc);
    $pc = uri_escape($pc);
    my $f = get("http://webgis.islington.gov.uk/Website/WebServices/LLPGSearch/LLPGSearchService.asmx/LLPGSearch?searchTerms=$pc");
    my %out;
    if (!$f) {
        $out{errors} = 'Sorry, the Islington address lookup is currently not working. Please try again later.';
    } elsif ($f =~ /<errorDescription>(.*?)<\/errorDescription>/) {
        $out{errors} = $1;
        if ($out{errors} =~ /^There were no addresses matched with these search terms/) {
            $out{errors} = 'Sorry, that postcode does not appear to be within Islington';
        }
    } else {
        @{$out{data}} = ($f =~ /<CATADDRESS>(.*?)<\/CATADDRESS>/g);
    }
    return %out;
}

sub html_final_changes($$) {
    my ($html, $p) = @_;
    my $site_group = Petitions::Page::site_group();
    $html =~ s/email/e-mail/g if $p->{body_ref} && $p->{body_ref} eq 'spelthorne';
    $html =~ s/<input([^>]*?type=['"]text)/<input class="field"$1/g if $site_group eq 'lichfielddc';
    return $html;
}

# Called from cron, so only use site_group
sub archive_front_end() {
    my $site_group = Petitions::Page::site_group();
    return 1 if $site_group eq 'hounslow';
    return 0;
}

# allow specific councils to completely override normal domain settings:
# this is rare (currently only applies if SITE_DOMAINS is true)
sub custom_domain($) {
    my $body = shift;
    return 'http://bassetlaw.petitions.mysociety.org' if $body eq 'bassetlaw';
    return "http://petitions.$body.gov.uk";
}

# determine if signing is disabled by inspecting SIGNING_DISABLED
# If this is a multi-body site, then we can't just return SIGNING_DISABLED but must inspect it first.
#   (Note: OPTION_SIGNING_DISABLED may be pure HTML (for a single site), but 
#          for multi-body sites it's a list of sitenames that gets returned as a short HTML notice here)
sub signing_disabled($) {
    my $body = shift;
    if (mySociety::Config::get('SIGNING_DISABLED')) {
        if (mySociety::Config::get('SITE_TYPE') eq 'multiple') { # this is a multi-body installation, only disable if site_name is explicitly mentioned
            my @disabled_bodies = split(/[\s,]+/, mySociety::Config::get('SIGNING_DISABLED'));
            if (grep {$_ eq $body} @disabled_bodies) {
                return "Petition signing is currently disabled"; # default message: customise here if needed
            } else {
                return 0;
            }
        }
    } 
    return mySociety::Config::get('SIGNING_DISABLED');
}

1;
