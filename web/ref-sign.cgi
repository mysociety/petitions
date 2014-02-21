#!/usr/bin/perl -w -I../perllib -I../commonlib/perllib
#
# ref-sign.cgi:
# Signup form for petitions site. Also process confirmation mail links.
#
# Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#

my $rcsid = ''; $rcsid .= '$Id: ref-sign.cgi,v 1.58 2010-03-31 15:29:34 matthew Exp $';

use strict;

use Digest::HMAC_SHA1 qw(hmac_sha1 hmac_sha1_hex);
use Error qw(:try);
use MIME::Base64;
use utf8;
use POSIX;

use mySociety::Config;
BEGIN {
    mySociety::Config::set_file("../conf/general");
}
use mySociety::DBHandle qw(dbh);
use mySociety::Web qw(ent);
use mySociety::PostcodeUtil;
use mySociety::EmailUtil;
use mySociety::MaPit;

use Petitions;
use Petitions::Page;
use Petitions::RPC;
use Petitions::Cobrand;

sub i_check_email ($) {
    my $email = shift;
    $email =~ s/^\s+//;
    $email =~ s/\s+$//;
    $email = undef unless mySociety::EmailUtil::is_valid_email($email);
    return $email;
}

# use Data::Dumper;

# signup_page Q REF
# Generate the signup page for REF.
use Time::HiRes qw(sleep time);
sub signup_page ($$) {
    my mySociety::Web $q = shift;
    my $p = shift;

    my $qp_body; # need body to selectively determine if signing is disabled
    if (mySociety::Config::get('SITE_TYPE') eq 'multiple') {
        if (mySociety::Config::get('SITE_DOMAINS')) {
            $qp_body = Petitions::Page::site_name();
        } else {
            $qp_body = $q->ParamValidate(body => qr/^[A-Za-z0-9-]+$/);
        }
    }

    if (my $disabled = Petitions::Cobrand::signing_disabled($qp_body)) {
        Petitions::Page::error_page($q, $disabled);
        return;
    }
    
    # Check the deadline of the petition.
    my $today = POSIX::strftime('%Y-%m-%d', localtime(time()));
    # ... the whole point of ref-sign.cgi is to not use the database - so only
    # look up debug date from the database if we are on a staging site, not on
    # the real site. Otherwise, use local date (line above).
    $today = Petitions::DB::today() if (mySociety::Config::get('PET_STAGING')); # XXX not sure staging is best check for this
    #warn "today is $today";
    if ($today gt $p->{deadline}) {
        Petitions::Page::error_page($q, sprintf("Sorry, but that petition is now closed. %s is later than %s!", $today, $p->{deadline}));
        return;
    }
    
    my $qp_name = $q->param('name');
    my ($qp_email, $qp_email2);
    if ($q->param('e-mail')) {
        $qp_email = $q->param('e-mail');
        $qp_email2 = $q->param('e-mail2');
    } else {
        $qp_email = $q->param('email');
        $qp_email2 = $q->param('email2');
    }
    $qp_email = i_check_email($qp_email);
    $qp_email2 = i_check_email($qp_email2);
    my $qp_address = $q->param('address') || '';
    $qp_address = '' if $qp_address eq '-- Select --';
    my $qp_address_type = $q->param('address_type') || '';
    my $qp_postcode = $q->param('postcode');
    $qp_postcode =~ s/[^a-z0-9]//ig;
    $qp_postcode = undef unless Petitions::Cobrand::is_valid_postcode($qp_postcode);
    my $qp_overseas = $q->param('overseas');
    $qp_overseas = undef if $qp_overseas && $qp_overseas eq '-- Select --';

    my %errors;
    $errors{name} = 'Please enter your name'
        if (!$qp_name || $qp_name eq '<Enter your name>');
    $errors{name} = 'Your name is too long' if (length($qp_name) > 100);
    $errors{name} = 'Your name cannot contain a web address' if ($qp_name =~ m#http://|www\.#);
    if (!$qp_email) {
        $errors{email} = 'Please enter a valid email address';
    } elsif (!$qp_email2) {
        $errors{email2} = 'Please enter your email address again for confirmation';
    } else {
        my ($local1, $domain1) = $qp_email =~ /^(.*?)\@(.*)$/;
        my ($local2, $domain2) = $qp_email2 =~ /^(.*?)\@(.*)$/;
        $errors{email2} = 'The two email addresses do not match'
            if ($local1 ne $local2 || lc($domain1) ne lc($domain2));
    }

    if (Petitions::Cobrand::ask_for_address_type()) {
        if (!$qp_address_type || $qp_address_type !~ /^(home|work|study)$/) {
            $errors{address_type} = 'Please specify your address type';
        }
    }

    $errors{postcode} = 'Please enter a valid postcode, or choose from the drop-down'
        if !$qp_postcode && !$qp_overseas;
    $errors{postcode} = 'You can\'t both put a postcode and pick an option from the drop-down.'
        if (defined($qp_postcode) && defined($qp_overseas));

    if ($qp_postcode && (my ($area, $area_id) = Petitions::Cobrand::within_area_only())) {
        my $mapit = mySociety::MaPit::call('postcode', $qp_postcode);
        if ($mapit->{error}) {
            $errors{postcode} = 'Sorry, that postcode was not recognised.';
        } else {
            my $in_mapit = grep { $area_id == $_ } keys %{$mapit->{areas}};
            unless ($in_mapit || Petitions::Cobrand::postcode_exemptions($qp_postcode)) {
                $errors{postcode} = "You must live, work or study within $area to sign a petition.";
            }
        }
    }

    if (Petitions::Cobrand::do_address_lookup()) {
        if (!$errors{postcode} && (%errors || !$qp_address)) {
            my %out = Petitions::Cobrand::perform_address_lookup($qp_postcode);
            $errors{postcode} = $out{errors} if $out{errors};
            if ($out{data}) {
                unshift @{$out{data}}, '-- Select --';
                $q->scratch()->{address_lookup} = $out{data};
                $errors{address} = 'Thanks, now please pick your address from the drop-down.'
                    if !$qp_address;
            }
        }
    } else {
        $errors{address} = 'Please enter your address'
            if (!$qp_address && Petitions::Cobrand::ask_for_address());
    }

    my $title = 'Signature addition';
    my $contents = '';
    if (!keys(%errors)) {
        # Success. Add the signature, assuming that we can.
        # XXX ref-index will have checked that the petition is valid to sign
        # before sending us here, but ser isn't timestamped so we should
        # re-check here. But mustn't use the database, obviously.
        my $s = 'ok';
        if ($s eq 'finished') {
            $contents = $q->p('Sorry, but that petition has finished, so you cannot sign it.');
        } elsif ($s eq 'none') {
            $contents = $q->p("We couldn't find that petition.");
        } else {
            # Either OK or already signed. We must not give away the fact that
            # any particular person has already signed a petition, so act the
            # same in each case.
            if (Petitions::RPC::do_rpc({
                            ref => $p->{ref},
                            email => $qp_email,
                            name => $qp_name,
                            address => $qp_address,
                            address_type => $qp_address_type,
                            postcode => $qp_postcode,
                            overseas => $qp_overseas
                        })) {
                $title = Petitions::Cobrand::signing_check_heading();
                $contents .= Petitions::Cobrand::extra_heading($title);
                $contents .=
                    ($title eq 'Now check your email!' ? '' : $q->p($q->strong('Now check your email!')))
                    . $q->p({-class => 'noprint loudmessage'},
                        "Thank you. We have sent you an email. To add your signature to the petition, you need to click the link in this email."
                    );
                $contents .=
                    $q->p({-class => 'noprint loudmessage'},
                    q(If you don't receive the email and you use web-based
                    email or have "junk mail" filters, please check
                    your bulk/spam mail folders, in case the message
                    went there by mistake.)
                );
            } else {
                $errors{busy} =
                        "Sorry, but we weren't able to add your signature to
                        the petition, because our site is extremely busy at
                        the moment. Please try again in a few minutes' time.";
                print STDERR "no RPC response signing $p->{ref}\n";
            }
        }
    }
    my $html = Petitions::Page::header($q, $title);
    $html .= $contents;
    
    if (keys(%errors)) {
        my $div_attr = { -id => 'errors' };
        $div_attr = { -class => 'scc-error' } if Petitions::Page::site_name() eq 'surreycc';
        $html .=
            $q->div($div_attr,
                $q->ul(
                    $q->li([
                        map { ent($_) } values(%errors)
                    ])
                )
            )
            . Petitions::Page::display_box($q, $p)
            . Petitions::Page::sign_box($q, $p);
    }
    $html .= Petitions::Page::footer($q, 'Sign.' . $p->{ref});

    $html = Petitions::Cobrand::html_final_changes($html, $p);
    utf8::encode($html);
    print $q->header(-content_length => length($html)), $html;
}

# confirm_page Q BODY_REF REF TOKEN
# Given a confirm TOKEN, validate a signup or petition creation.
sub confirm_page ($$$$) {
    my ($q, $body_ref, $ref, $token) = @_;
    
    my $html = '';
    my ($what, $id);
    if (!(($what, $id) = Petitions::Token::check($token))) {
        # Token was not valid.
        $html = Petitions::Page::header($q, "Something is wrong")
                . $q->p("
                    Unfortunately, we couldn't understand the signup link that
                    you've used. If you typed the link in manually, please
                    re-check that you've got it absolutely right.")
                . Petitions::Page::footer($q, 'Bad_confirm_link');
    } elsif ($what eq 'e') {
        # Edit rejected petition for resubmission. Redirect to /new.
        print $q->redirect("/new?token=$token");
    } elsif (Petitions::RPC::do_rpc({
                    confirm => $what,
                    id => $id
                })) {

        if ($what eq 'p') {
            # Display message about petition creation.
            my $message = 'the team';
            $html = Petitions::Page::header($q, "Petition created")
                    . $q->p({ -class => 'noprint loudmessage' },
                        "Thank you for creating your petition.");
            if (mySociety::Config::get('SITE_APPROVAL')) {
                my $approval = 'approval';
                if (my $a = Petitions::Cobrand::approval_word()) {
                    $approval = $a;
                }
                $html .=
                    $q->p({ -class => 'noprint loudmessage' }, "
                        It has been entered on our system and will now go to
                        $message for $approval.");
            } else {
                $html .=
                    $q->p({ -class => 'noprint loudmessage' }, "
                        It is now available on our site.");
            }
            $html .= $q->p({ -class => 'noprint loudmessage' },
                $q->a({-href => '/'}, 'Petitions home'));
            $html .= Petitions::Page::footer($q, 'Confirm_Petition');
        } else {
            # Redirect so that the token isn't left in the browser. But use
            # a signed timestamp so that a "?signed=..." URL can't be
            # distributed and used to mislead people into thinking they've
            # signed when they haven't. (This is a hack, and I am ashamed.)
            #
            # XXX a slightly better hack would be to somehow pass the signer's
            # name or signer_id to ref-index, so that it could say "Thank you
            # Fred Bloggs, you've now signed the petition"; that would make it
            # even clearer to a third party that simply visiting the link
            # hasn't signed them up. Might implement this if we can be
            # bothered but it's a little awkward and this should be good
            # enough.
            my $t = sprintf('%x', int(time()) - 1_000_000_000);
            $t .= "." . substr(hmac_sha1_hex($t, Petitions::DB::secret()), 0, 6);
            my $url = "/$ref/?signed=$t";
            $url = "/$body_ref$url" if $body_ref && !mySociety::Config::get('SITE_DOMAINS');
            print $q->redirect($url);
            return;
        }
    } else {
        my $desc;
        if ($what eq 'p') {
            $desc = "the creation of your petition";
        } else {
            $desc = "your signature";
        }
        $html = Petitions::Page::header($q, "Sorry, we couldn't confirm $desc")
                . $q->p("
                    Unfortunately, we weren't able to confirm $desc,
                    because our site is extremely busy at the moment.
                    Please try again in a few minutes' time.")
                . Petitions::Page::footer($q, 'Confirm_something_busy');
        print STDERR "no RPC response confirming '$desc'\n";
    }

    utf8::encode($html);
    print $q->header(-content_length => length($html)), $html;
}

# main
sub main () {
    my $q = shift;

    my $qp_body;
    if (mySociety::Config::get('SITE_TYPE') eq 'multiple') {
        if (mySociety::Config::get('SITE_DOMAINS')) {
            $qp_body = Petitions::Page::site_name();
        } else {
            $qp_body = $q->ParamValidate(body => qr/^[A-Za-z0-9-]+$/);
        }
        if (!defined($qp_body)) {
            print $q->redirect("/");
            return;
        }
    }

    my $qp_ref = $q->ParamValidate(ref => qr/^[A-Za-z0-9-]{6,16}$/);
    if (!defined($qp_ref)) {
        print $q->redirect("/");
        return;
    }

    # Confirm page.
    my $qp_token = $q->ParamValidate(token => qr/^[A-Za-z0-9_\$'\/-]+$/);
    if (defined($qp_token)) {
        confirm_page($q, $qp_body, $qp_ref, $qp_token);
        return;
    }

    # This page is only ever invoked for a POST form, so redirect to the
    # petition page if this is a GET.
    if ($q->request_method() ne 'POST') {
        #warn "bad method";
        my $url = "/$qp_ref/";
        $url = "/$qp_body$url" if $qp_body && !mySociety::Config::get('SITE_DOMAINS');
        print $q->redirect($url);
        return;
    }

    # Get the serialised petition data from the query and check it.
    my $qp_ser = $q->ParamValidate(ser => qr#^[A-za-z0-9/+=]+$#);
    if (!defined($qp_ser)) {
        warn "missing/invalid serialised data in POST request";
        print $q->redirect("/$qp_ref/");
        return;
    }
    my $ser = decode_base64($qp_ser);
    if (substr($ser, -20) ne hmac_sha1(substr($ser, 0, length($ser) - 20), Petitions::DB::secret())) {
        warn "bad signature in serialised data '$qp_ser'";
        print $q->redirect("/$qp_ref/");
        return;
    }

    my $p = RABX::unserialise(substr($ser, 0, length($ser) - 20));
    signup_page($q, $p);
}

# Awful. We need lots of processes to handle lots of concurrent signups (since
# they need to wait while petsignupd batches requests and acks them) but we
# mustn't have lots of database connections (because they're expensive). So do
# a dummy query to force Petitions::DB to cache the secret, which we need to
# validate incoming data, and then disconnect.
my $secret = Petitions::DB::secret();
dbh()->disconnect();

Petitions::Page::do_fastcgi(\&main);

exit(0);

