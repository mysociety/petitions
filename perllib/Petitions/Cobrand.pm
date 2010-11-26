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
use Petitions::Page;

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
    return ('Islington', 2507) if $site_name eq 'islington';
    return ('Westminster', 2504) if $site_name eq 'westminster';
    #return ('Surrey Heath', 2450) if $site_name eq 'surreyheath';
}

sub ask_for_address() {
    my $site_name = Petitions::Page::site_name();
    return 0 if $site_name eq 'westminster';
    return 1;
}

sub overseas_dropdown {
    my $site_group = Petitions::Page::site_group();
    if ($site_group eq 'westminster' || $site_group eq 'islington') {
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

1;
