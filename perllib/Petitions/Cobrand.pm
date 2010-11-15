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

sub signing_check_heading() {
    my $site_name = Petitions::Page::site_name();
    return 'You Have Signed the Petition' if $site_name eq 'islington';
    return 'Now check your email!';
}

1;
