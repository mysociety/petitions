#!/usr/bin/perl -I../perllib -I../../perllib
#
# Petitions/HTMLEmail.pm:
# HTML email stuff for the petitions site.
#
# Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
# Email: matthew@mysociety.org; WWW: http://www.mysociety.org/
#
# $Id: HTMLEmail.pm,v 1.4 2007-04-16 10:09:42 matthew Exp $
#

package Petitions::HTMLEmail;

use strict;
use mySociety::Email;
use File::Slurp;
use Net::SMTP;
use POSIX qw(strftime);
use Text::Wrap qw();

# construct_email TEXT SUBJECT
# Given constituent parts, construct an HTML email
sub construct_email {
    my ($text, $subject) = @_;
    my $plain = Petitions::HTMLEmail::create_plain($text);
    my $html = Petitions::HTMLEmail::create_html($text, $subject);
    $plain = construct_part($plain, 0);
    $html = construct_part($html, 1);
    my $boundary = '----=_NextPart_000_6C92_50657469.74696F6E';
    # my $id = strftime('%Y-%m-%d.%H-%M-%S.message@id', localtime());
    # my $date = strftime('%a, %d %b %Y %H:%M:%S %z', localtime());
    my $email = <<EOF;
Subject: $subject
MIME-Version: 1.0
Content-Type: multipart/alternative;
        boundary="$boundary"

This is a multi-part message in MIME format.

--$boundary
$plain

--$boundary
$html

--$boundary--
EOF
    return $email;
}

# creeate_html TEXT
# Given some TEXT in the right format, create the HTML contents
sub create_html {
    my ($in, $subject) = @_;
    $_ = $in;
    my ($first, $text, $further) = /^(.*?)\n\n+(.*?)(?:\n\n+Further information\n\n+(.*?)\n*)?$/s;
    $first = '<p style="font-family:vera,verdana;color:#666;border:1px solid #999;display:block;padding:10px;margin:2px;background:#fff;font-weight:bold;">' . "\n" . $first . "\n</p>";
    my $p_text = '<p style="color:#111;font-family:vera,verdana;font-size:11pt;margin-left:5px;margin-right:5px;">';
    my $style_further = ' style="color:#111;font-family:vera,verdana;font-size:11pt;padding-left:5px;padding-right:5px;margin-top:0;padding-top:5px">';
    my $li_further = "<li$style_further";
    my $p_further = "<p$style_further";
    $text =~ s/([^\n]\n)([^\n])/$1<br>$2/g;
    $text =~ s/\n\n+/<\/p>\n\n$p_text\n/g;
    $text = "$p_text\n$text</p>";
    $text = convert_links($text);
    my $html = <<EOF;
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>$subject</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<body>
<div style="background:#fff;text-align:center;font-family:verdana ! important;">
<div style="width:80%;background-color:#f1efef;text-align:left;margin-left:auto;margin-right:auto;font-size:11pt;padding-bottom:11pt;">
<h1 style="background:#4a95c2;color:#fff;font-family:verdana;font-size:20px;padding:5px;">
$subject
</h1>

$first

$text

</div>
EOF
    if ($further) {
        $further =~ s/\n\n+/<\/li>\n\n$li_further\n/g;
        $further =~ s/^(.*)$li_further(.*?)$/$1<\/ul>$p_further$2/s;
        $further = "<ul>$li_further\n$further</p>";
        $further = convert_links($further);
        $html .= <<EOF;
<div style="width:80%;background-color:#c8dfed;text-align:left;margin-left:auto;margin-right:auto;font-size:11pt;margin-top:10px">
<h2 style="background:#4a95c2;color:#fff;font-family:verdana;font-size:16px;padding:5px;margin-bottom:0">Further information</h2>

$further

</div>
EOF
    }
    $html .= <<EOF;
</div>
</body>
</html>
EOF
    $html = wrap_text($html);
    return $html;
}

sub convert_links {
    $_ = shift;
    my $a = '<a style="color:#4a95c2;font-family:vera,verdana;font-size:11pt;font-weight:bold;text-decoration:none;"'
        . "\nhref=";
    s/\[([^ ]*\@[^ ]*)\]/$a"mailto:$1">$1<\/a>/gs;
    s/\[([^ ]*)\]/$a"$1">$1<\/a>/gs;
    s/\[([^ ]*) (.*?)\]/$a"$1">$2<\/a>/gs;
    return $_;
}

# create_plain TEXT
# Given some text, wordwraps it and converts the links
sub create_plain {
    $_ = shift;
    s/\[([^ ]*)\]/$1/gs;
    s/\[([^ ]*) ([^]]*?)\]((?:\.|;|,)?)$/"$2 - $1".($3?" $3":'')/egsm;
    s/\[([^ ]*) (.*?)\]/$2 - $1 -/gs;
    $_ = wrap_text($_);
    return $_;
}

sub wrap_text {
    $_ = shift;
    local($Text::Wrap::columns = 76);
    local($Text::Wrap::huge = 'overflow');
    local($Text::Wrap::unexpand = 0);
    s/<([^>]+?) /<$1*/g;
    $_ = Text::Wrap::wrap('', '', $_);
    s/<([^>]+?)\*/<$1 /g;
    s/^[ \t]+$//mg;
    return $_;
}

# construct_part BODY IS-HTML
# Given already wrapped text or HTML, does correct MIME quoting if needed,
# and returns a multipart
sub construct_part {
    my ($body, $ishtml) = @_;
    my($enc, %hdr);
    ($enc, $body) = mySociety::Email::encode_string($body);
    $hdr{'Content-Type'} = "text/plain; charset=\"$enc\"";
    if ($enc eq 'us-ascii') {
        $hdr{'Content-Transfer-Encoding'} = '7bit';
    } else {
        $hdr{'Content-Transfer-Encoding'} = 'quoted-printable';
        $body = mySociety::Email::encode_qp($body, "\n");
    }

    my $out = '';
    foreach (keys %hdr) {
        my $h = $hdr{$_};
        $h =~ s/\r?\n/ /gs;
        $out .= "$_: $h\n";
    }
    $out .= "\n" . $body;
    $out =~ s/text\/plain/text\/html/ if $ishtml;
    $out =~ s/iso-8859-1/us-ascii/ if $out =~ /us-ascii/;
    return $out;
}

1;
