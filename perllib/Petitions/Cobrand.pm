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
    return "<h3 class='page_title_border'>$text</h3>" if $site_name eq 'number10';
    return "<h3>$text</h3>";
}

# Currently used on check your email signing page to supply
# a heading for those templates that don't have one by default.
sub extra_heading($) {
    my $text = shift;
    my $site_name = Petitions::Page::site_name();
    return "<h2>$text</h2>"
        if $site_name =~ /tandridge|molevalley|lichfielddc|number10|spelthorne|reigate-banstead|nottinghamshire/;
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
    return ('the Royal Borough of Windsor and Maidenhead', 2622) if $site_name eq 'rbwm';
    return ('Salford', 2534) if $site_name eq 'salford';
    return ('Westminster', 2504) if $site_name eq 'westminster';
    return;
}

sub ask_for_address() {
    my $site_name = Petitions::Page::site_name();
    return 0 if $site_name eq 'westminster';
    return 1;
}

sub ask_for_address_type() {
    my $site_name = Petitions::Page::site_name();
    return 1 if $site_name eq 'westminster' || $site_name eq 'salford';
}

sub overseas_dropdown {
    my $site_group = Petitions::Page::site_group();
    if ($site_group eq 'westminster' || $site_group eq 'islington' || $site_group eq 'rbwm' || $site_group eq 'stevenage' || $site_group eq 'salford') {
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

1;
