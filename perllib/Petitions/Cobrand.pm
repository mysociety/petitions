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
use utf8;
use mySociety::Config;
use Petitions::Page;

sub admin_email($) {
    my $p = shift;
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
        if $site_name =~ /molevalley/;
    return '';
}

sub signing_check_heading() {
    my $site_name = Petitions::Page::site_name();
    return 'Now check your email!';
}

sub approval_word() {
    my $site_name = Petitions::Page::site_name();
    return 'acceptance' if $site_name eq 'westminster';
}

sub post_signup_text {
    my $site_name = Petitions::Page::site_name();
    return '(Your name will be published to the petition page under ‘current signatories’)' if $site_name eq 'rbwm';
    return '';
}

sub signer_must_be {
    my $p = shift;
    my $site_name = Petitions::Page::site_name();
    if ($site_name eq 'rbwm') {
        return 'You must live within the Royal Borough to sign a petition. ';
    } elsif (my @area = within_area_only()) {
        return "You must live, work or study within $area[0] to sign a petition. ";
    } elsif ($p->{body_area_id} || ($p->{body_name} && $p->{body_name} =~ /council/i) || mySociety::Config::get('SITE_PETITIONED') =~ /council/i) {
        return '';
        #'You need to live, work or study within the council area to sign the petition. ';
    }
    return 'You must be a British citizen or resident to sign the petition. ';
}

sub within_area_only() {
    my $site_name = Petitions::Page::site_name();
    return ('the Royal Borough of Windsor and Maidenhead', 2622) if $site_name eq 'rbwm';
	return ('Runnymede', 2451) if $site_name eq 'runnymede';
    return ('Westminster', 2504) if $site_name eq 'westminster';
    return;
}

sub postcode_exemptions($) {
    my $pc = shift;
    my $site_name = Petitions::Page::site_name();
    return 1 if $site_name eq 'westminster' && uc($pc) eq 'W24LY';
    return 1 if $site_name eq 'rbwm' && uc($pc) eq 'SL42DP';
    return 0;
}

# allow cobrand to override the commonlib postcode (UK) validator
sub is_valid_postcode($) {
    my $postcode = shift;
    my $site_name = Petitions::Page::site_name();
    return mySociety::PostcodeUtil::is_valid_postcode($postcode);
}

sub name_only_text() {
    my $site_name = Petitions::Page::site_name();
    return '';
}

sub ask_for_address() {
    my $site_name = Petitions::Page::site_name();
    return '' if $site_name eq 'westminster';
    return 'Your address (<strong>will not be published</strong> and is only collected to confirm the address is within the borough boundary)' if $site_name eq 'rbwm';
    return 'Your address (will not be published)';
}

sub ask_for_address_type() {
    my $site_name = Petitions::Page::site_name();
    return 1 if $site_name eq 'runnymede' || $site_name eq 'westminster';
}

sub signing_checkbox() {
    my $site_name = Petitions::Page::site_name();
    return 'To see RBWM’s data processing Privacy Notice in relation to e-petitions, please go to the following link: <a href="https://www3.rbwm.gov.uk/downloads/200409/data_protection">https://www3.rbwm.gov.uk/downloads/200409/data_protection</a>' if $site_name eq 'rbwm';
}

sub overseas_dropdown {
    my $site_group = Petitions::Page::site_group();
    if (grep {$site_group eq $_} qw( rbwm runnymede stevenage westminster)) {
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
    return { -id =>'success' };
}

sub button_class() {
    my $site_name = Petitions::Page::site_name();
    return 'button';
}

sub more_detail_class() {
    my $site_name = Petitions::Page::site_name();
    return 'col-md-6 col-sm-12 col-xs-12' if $site_name eq 'surreycc';
    return 'relative_width_47';
}

sub signatories_class() {
    my $site_name = Petitions::Page::site_name();
    return 'col-md-6 col-sm-12 col-xs-12' if $site_name eq 'surreycc';
    return 'relative_width_47';
}

sub signFormRight_class() {
    my $site_name = Petitions::Page::site_name();
    return 'col-md-6 col-sm-12 col-xs-12' if $site_name eq 'surreycc';
    return 'relative_width_47';
}

sub signFormLeft_class() {
    my $site_name = Petitions::Page::site_name();
    return 'col-md-6 col-sm-12 col-xs-12' if $site_name eq 'surreycc';
    return 'relative_width_50';
}

sub do_address_lookup() {
    my $site_name = Petitions::Page::site_name();
    return 0;
}

sub perform_address_lookup($) {
}

sub html_final_changes($$) {
    my ($html, $p) = @_;
    my $site_group = Petitions::Page::site_group();
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
