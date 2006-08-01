#!/usr/bin/perl -w -I../perllib -I../../perllib
#
# ref-sign.cgi:
# Signup form for petitions site. Also process confirmation mail links.
#
# Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#

my $rcsid = ''; $rcsid .= '$Id: ref-sign.cgi,v 1.6 2006-08-01 01:37:28 chris Exp $';

use strict;

use mySociety::Config;
BEGIN {
    mySociety::Config::set_file("../conf/general");
}
use mySociety::DBHandle qw(dbh);
use mySociety::Web qw(ent);
use mySociety::WatchUpdate;

use Petitions;
use Petitions::Page;
use Petitions::RPC;

my $W = new mySociety::WatchUpdate();

sub i_check_email ($$) {
    return mySociety::Util::is_valid_email($_[1]);
}

sub i_check_postcode ($$) {
    return mySociety::Util::is_valid_postcode($_[1]);
}

use Data::Dumper;

# signup_page Q REF
# Generate the signup page for REF.
sub signup_page ($$) {
    my mySociety::Web $q = shift;
    my $ref = shift;
    
    my $html =
        Petitions::Page::header($q, 'Signature addition');

    my $qp_name = $q->param('name');
    my $qp_email = $q->ParamValidate(email => \&i_check_email);
    my $qp_email2 = $q->ParamValidate(email2 => \&i_check_email);
    my $qp_address = $q->param('name');
    my $qp_postcode = $q->ParamValidate(postcode => \&i_check_postcode);

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

    our $p;
    $p = Petitions::DB::get($ref, 1) if (!$p || $p->{ref} ne $ref);
    if (!keys(%errors)) {
        # Success. Add the signature, assuming that we can.
        my $s = Petitions::DB::is_valid_to_sign($p->{id}, $qp_email);
        dbh()->commit();    # finish transaction
        if ($s eq 'finished') {
            $html .= $q->p('Sorry, but that petition has finished, so you cannot sign it.');
        } elsif ($s eq 'none') {
            $html .= $q->p("We couldn't find that petition.");
        } else {
            # Either OK or already signed. We must not give away the fact that
            # any particular person has already signed a petition, so act the
            # same in each case.
            Petitions::RPC::sign_petition({
                        ref => $p->{ref},
                        email => $qp_email,
                        name => $qp_name,
                        address => $qp_address,
                        postcode => $qp_postcode
                    });

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

# confirm_page Q TOKEN
# Given a confirm TOKEN, validate a signup.
sub confirm_page ($$) {
    my ($q, $token) = @_;
    
    my $html = '';
    my ($what, $id);
    if (!(($what, $id) = Petitions::Token::check($token))) {
        # Token was not valid.
        $html = Petitions::Page::header($q, "Something is wrong")
                . $q->p("
                    Unfortunately, we couldn't understand the signup link that
                    you've used. If you typed the link in manually, please
                    re-check that you've got it absolutely right.")
                . Petitions::Page::footer($q);
    } else {
        # if ($what ne 'p') ...
        # Confirm signer.
        dbh()->do("update signer set emailsent = 'confirmed' where id = ?", {}, $id);
        dbh()->commit();

        my $ref = dbh()->selectrow_array('
                    select ref from petition, signer
                    where signer.petition_id = petition.id
                        and signer.id = ?', {},
                    $id);

        # Now we should redirect so that the token URL isn't left in the browser.
        print $q->redirect("/$ref?signed=1");
    }
}

my $foad = 0;
$SIG{TERM} = sub { $foad = 1; };
while (!$foad && (my $q = new mySociety::Web())) {
    my $qp_token = $q->ParamValidate(token => qr/^[A-Za-z0-9_-]+$/);
    if (defined($qp_token)) {
        confirm_page($q, $qp_token);
        next;
    }

    my $qp_ref = $q->ParamValidate(ref => qr/^[A-Za-z0-9-]{6,16}$/);
    # This page is only ever invoked for a POST form, so redirect to the
    # petition page if this is a GET.
    if ($q->request_method() ne 'POST') {
        print $q->redirect("/$qp_ref");
        next;
    }

    my $ref = Petitions::DB::check_ref($qp_ref);
    if (!defined($ref)) {
        Petitions::Page::bad_ref_page($q, $qp_ref);
        next;
    }

    signup_page($q, $ref);

    $W->exit_if_changed();
}
