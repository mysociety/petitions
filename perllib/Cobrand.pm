#!/usr/bin/perl
#
# Cobrand.pm:
# Petition cobrand utilities.
#
# Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
# Email: matthew@mysociety.org; WWW: http://www.mysociety.org/
#

package Cobrand;

use strict;
use Petitions::Page;

sub main_heading($) {
    my $text = shift;
    my $site_name = Petitions::Page::site_name();
    return "<h2>$text</h2>" if $site_name eq 'surreycc';
    return "<h3 class='page_title_border'>$text</h3>" if $site_name eq 'number10';
    return "<h3>$text</h3>";
}

1;
