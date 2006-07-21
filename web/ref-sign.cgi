#!/usr/bin/perl -w -I../perllib -I../../perllib
#
# ref-sign.cgi:
# Signup form for petitions site.
#
# Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#

my $rcsid = ''; $rcsid .= '$Id: ref-sign.cgi,v 1.3 2006-07-21 17:25:08 chris Exp $';

use strict;

use mySociety::Config;
BEGIN {
    mySociety::Config::set_file("../conf/general");
}
use mySociety::DBHandle qw(dbh);
use mySociety::Web qw(ent);

use Petitions;
use Petitions::Page;

sub i_check_email ($$) {
    return mySociety::Util::is_valid_email($_[1]);
}

sub i_check_postcode ($$) {
    return mySociety::Util::is_valid_postcode($_[1]);
}

my $foad = 0;
$SIG{TERM} = sub { $foad = 1; };
while (!$foad && (my $q = new mySociety::Web())) {
    our $qp_ref;
    $q->Import('p', ref => [qr/^[A-Za-z0-9-]{6,16}$/, undef]);
    my $ref = Petitions::DB::check_ref($qp_ref);
    if (!defined($ref)) {
        Petitions::Page::bad_ref_page($q, $qp_ref);
        next;
    }

    # Perhaps redirect to canonical ref if non-canonical was given.
    if ($qp_ref ne $ref && $q->request_method() =~ /^(GET|HEAD)$/) {
        print $q->redirect("/$ref/sign?" . $q->query_string());
        next;
    }

    my $html =
        Petitions::Page::header($q, 'Signature addition');

    our ($qp_name, $qp_email, $qp_email2, $qp_address, $qp_postcode);
    $q->Import('p',
            name =>     [qr/./, undef],
            email =>    [\&i_check_email, undef],
            email2 =>   [\&i_check_email, undef],
            address =>  [qr/./, undef],
            postcode => [\&i_check_postcode, undef]
        );

    my %errors;
    $errors{name} = 'Please enter your name'
        if (!$qp_name || $qp_name eq '<Enter your name>');
    if (!$qp_email) {
        $errors{email} = 'Please enter a valid email address';
    } elsif (!$qp_email2 || $qp_email ne $qp_email2) {
        $errors{email2} = 'The two email addresses do not match';
    }
    $errors{address} = 'Please enter your address'
        if (!$qp_address);
    $errors{postcode} = 'Please enter a valid postcode, such as OX1 3DR'
        if (!$qp_postcode);
    
    my $p = Petitions::DB::get($ref);
    if (!keys(%errors)) {
        # Success. Add the signature, assuming that we can.
        my $s = Petitions::DB::is_valid_to_sign($p->{id}, $qp_email);
        if ($s eq 'finished') {
            $html .= $q->p('Sorry, but that petition has finished, so you cannot sign it.');
        } elsif ($s eq 'none') {
            $html .= $q->p("We couldn't find that petition.");
        } else {
            # Either OK or already signed. We must not give away the fact that
            # any particular person has already signed a petition, so act the
            # same in each case.
            my $id = undef;
            if ($s eq 'ok') {
                # It's still possible for this to fail, if two signatures are
                # attempted in very close succession, so ignore errors.
                local dbh()->{RaiseError};
                my $didaddsignature;
                dbh()->do('
                        insert into signer
                            (petition_id, email, name, address, postcode,
                            showname, signtime)
                        values (?, ?, ?, ?, ?, true,
                            ms_current_timestamp())', {},
                        $p->{id}, $qp_email, $qp_name, $qp_address,
                        $qp_postcode)
                    and $didaddsignature = 1
                    or dbh()->rollback();   # just in case
                $id = dbh()->selectrow_array('
                        select id from signer
                        where petition_id = ? and email = ?', {},
                        $p->{id}, $qp_email);
                # XXX if the user has already signed but not confirmed, reset
                # their email status so that another mail gets sent. The first
                # might have been lost.
                # if ($didaddsignature) { ... }
                dbh()->commit();
            }

            $html .=
                $q->p({-class => 'noprint loudmessage', -align => 'center'},
                    'Now check your email!'
                );
        }
    } else {
        $html .=
            $q->div({ -id => 'errors' },
                $q->ul(
                    $q->li([
                        map { ent($_) } values(%errors)
                    ])
                )
            )
            . Petitions::Page::display_box($q, $p)
            . Petitions::Page::sign_box($q, $p);
    }
    $html .= Petitions::Page::footer($q);

    print $q->header(-content_length => length($html)), $html;
}
