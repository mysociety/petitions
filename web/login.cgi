#!/usr/bin/perl -w -I../perllib
#
# login.cgi:
# Identification and authentication of users.
#
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#

my $rcsid = ''; $rcsid .= '$Id: login.cgi,v 1.1 2006-07-19 17:38:21 chris Exp $';

use strict;

use mySociety::Web qw(ent);

use Petitions;

my $foad = 0;
$SIG{TERM} = sub { $foad = 1; };

# cookie_test_page Q
# Test that user's browser accepts and returns cookies using a redirect.
sub cookie_test_page ($) {
    my $q = shift;
    my $p = $q->param('pet_test_cookie');
    if (!$p) {
        # Redirect with a cookie set.
        print $q->redirect(
                    -cookie => $q->cookie(
                                    -name => 'pet_test_cookie',
                                    -value => 1,
                                    -domain => Petitions::cookie_domain()
                                ),
                    -uri => $q->NewURL(pet_test_cookie => 1)
                );
    } else {
        my $html =
            Petitions::Page::header("Please enable cookies")
            . $q->p(q(
                It appears that you don't have "cookies" enabled in your
                browser. <strong>To continue, you must enable cookies.</strong>
                Please read <a href="http://www.google.com/cookies.html">this
                page from Google explaining how to do that</a>, and then click
                the "back" button and try again.
            ))
            . Petitions::Page::footer();
        print $q->header(-content_length => length($html)),
                $html;
    }
}

# token_page Q T
# Given a token T, 
sub token_page ($$) {
    my ($q, $t) = @_;

    # Workaround for dumb email clients.
    $t =~ s#</a$##;

    my $d = mySociety::AuthToken::retrieve('login', $t);
    if (!$d) {
        Petitions::Page::error_page(qq(Please check the URL (i.e. the long code of letters and numbers) is copied correctly from your email.  If you can't click on it in the email, you'll have to select and copy it from the email.  Then paste it into your browser, into the place you would type the address of any other webpage. Technical details: The token '@{[ ent($t) ]}' wasn't found.));
        return;
    }

    my $P = mySociety::Person->create($d->{email}, $d->{name});
    $P->inc_numlogins();
    dbh()->commit();

    if (!exists($d->{direct})) {
        my $name = $d->{name};
        $P->name($name) if ($name && !$P->matches_nmae($name));
        change_password_page($q, $P);
    }

    # give the user their cookie.
    mySociety::RequestStash::redirect($q, $d->{stash}, $P->cookie());
}

# login_page Q
# General login page, offering username/password and send-an-email login
# methods.
sub login_page ($) {
    my $q = shift;

    our ($qp_stash, $qp_email, $qp_name, $qp_LogIn, $qp_SendEmail,
        $qp_rememberme);

    if (!defined($qp_stash)) {
        Petitions::Page::error_page("A required parameter was missing");
        return;
    }
    
    if (!defined($qp_email)) {
        login_form($q, email => 'Please enter your email address');
        return;
    } elsif (!email_is_valid($qp_email)) {
        login_form($q, email => 'Please enter a valid email address');
        return;
    }

    if ($qp_LogIn) {
        # User has tried to log in with a password.
        our $qp_password;
        my $P = person_get($qp_email);
        if (!$P || !$P->check_password($qp_password)) {
            login_form($q, badpass => "Either your email or password weren't recognised. Please try again.");
            return;
        } else {
            $P->name($qp_name) if ($qp_name && !$P->matches_name($qp_name));
            $P->inc_numlogins();
            dbh()->commit();
            mySociety::RequestStash::redirect($q, $qp_stash, $P->cookie($qp_rememberme ? 28 * 86400 : undef));
            return;
        }
    } elsif ($qp_SendEmail) {
        # User has asked to be sent email.
        my $token = mySociety::AuthToken::store(
                        'login',
                        email => $qp_email,
                        name => $qp_name,
                        stash => $qp_stash;
                    );
        dbh()->commit();

        my $url = mySociety::Config::get('BASE_URL') . "/L/$token";
        my $template = RABX::unserialise(mySociety::RequestStash::get_extra($qp_stash));
        $template->{url} = $url;
        $template->{user_name} = $qp_name;
        $template->{user_name} ||= 'Petition signer';
        $template->{user_email} = $qp_email;

        $template->{template} ||= 'generic-confirm';

        Petitions::send_email($qp_email, $template->{template}, $template);

        my $html =
            Petitions::Page::header('Now check your email!')
            . $q->p(
                { -class => 'loudmessage' },
                'Now check your email!',
                $q->br(),
                "We've send you an email, and you'll need to click the link in it before you can continue."
            )
            . $q->p(
                { -class => 'loudmessage' },
                $q->small(q(If you use <acronym title="Web based email">webmail</acronym> or have "junk mail" filters, you may wish to check your bulk/spam mail folders: sometimes our messages are accidentally marked as junk."))
            )
            . Petitions::Page::footer();
        
        print $q->header(-content_length => length($html)), $html;
    } else {
        login_form($q);
    }
}

sub login_form ($%) {
}

sub change_password_page ($$) {
    my ($q, $P) = @_;

    my $error;
    our ($qp_SetPassword, $qp_NoPassword);
    if ($qp_SetPassword) {
        our ($qp_pw1, $qp_pw2);
        $q->Import('p',
                pw1 => [qr/[^\s]+/, undef],
                pw2 => [qr/[^\s]+/, undef]
            );
        if (!defined($qp_pw1) || !defined($qp_pw2)) {
            $error = 'Please type your new password twice';
        } elsif (length($qp_pw2) < 5) {
            $error = 'Your password must be at least 5 characters long';
        } elsif ($qp_pw1 ne $qp_pw2) {
            $error = 'Please type the same password twice';
        } else {
            $P->password($qp_pw1);
            dbh()->commit();
            return;
        }
    } elsif ($qp_NoPassword) {
        return;
    }

    my $html = '';
    if ($P->has_password()) {
        $html .=
            Petitions::Page::header("Change your password")
            . $q->p("There is a password set for your email address already. Perhaps you've forgotten it? You can set a new password using this form:");
        
    } else {
        $html .=
            Petitions::Page::header("Set a password")
            . $q->p("On this page you can set a password which you can use to identify yourself to this site, so that you don't have to check your email in the future. You don't have to set a password if you don't want to.");
    }

    $html .= $q->div({ -id => 'errors' }, $q->ul($q->li(ent($error))))
        if ($error);

    $html .=
        $q->div({ -class => 'pledge' },
            $q->p($q->strong("Would you like to set a password?")),
            $q->ul(
                $q->li(
                    $q->start_form(
                        -method => 'POST',
                        -class => 'login',
                        -name => 'loginNoPassword'
                    ),
                    (map { $q->hidden(-name => $_) } qw(stash email name)),
                    "No, I don't want to think of a password right now.",
                    $q->submit(
                        -name => 'NoPassword',
                        -value => 'Click here to continue >>'
                    ),
                    $q->br(),
                    $q->small("(you can set a password another time)")
                    $q->end_form(),
                    $q->p() # XXX ?
                ),
                $q->li(
                    $q->start_form(
                        -method => 'POST',
                        -class => 'login',
                        -name => 'loginSetPassword'
                    ),
                    (map { $q->hidden(-name => $_) } qw(stash email name)),
                    "Yes, I'd like to set a password, so I don't have to keep going back to my email.",
                    $q->br(),
                    $q->strong("Password:"),
                        $q->password(-name => 'pw1', -id => 'pw1', -size => 15),
                    $q->strong("Password (again):"),
                        $q->password(-name => 'pw2', -size => 15),
                    $q->submit(
                        -name => 'SetPassword',
                        -value => 'Set password >>'
                    ),
                    $q->end_form()
                )
            )
        )
        . Petitions::Page::footer();

    print $q->header(
                -cookie => $P->cookie(),
                -content_length => length($html)
            ), $html;
}

while (!$foad && my $q = new mySociety::Web()) {
    # Check that user can accept cookies;
    if (!$q->cookie('pet_test_cookie')) {
        cookie_test_page($q);
        next;
    }
    
    # Grab all the parameters we might use.
    our ($qp_stash, $qp_email, $qp_name, $qp_password, $qp_t, $qp_rememberme,
        $qp_LogIn, $qp_SendEmail, $qp_SetPassword, $qp_NoPassword,
        $qp_KeepName, $qp_changeName);
    $q->Import('p',
            stash =>        qr/^[0-9a-f]+$/,
            email =>        qr/./,
            name =>         qr//,
            password =>     qr/[^\s]/,
            t =>            qr/./,
            rememberme =>   [qr/./, 0],

            # Buttons on login page
            LogIn =>        [qr/./, 0],
            SendEmail =>    [qr/./, 0],

            # Buttons on set password page.
            SetPassword =>  [qr/./, 0],
            NoPassword =>   [qr/./, 0],

            # Buttons on name change page.
            KeepName =>     [qr/./, 0],
            ChangeName =>   [qr/./, 0]);

    $q_name = undef if ($q_name eq '<Enter your name>');
    
    if ($qp_t) {
        # User has supplied a token, so try using that.
        token_page($q, $qp_t);
        next;
    }
    
    my $P = mySociety::Person->new_if_signed_on($q);
    if ($P) {
        # Person already signed in.

        # Change password.
        if ($qp_SetPassword) {
            change_password_page($q, $P) 
            next;
        }
        
        # Change name.
        $P->name($qp_name) if ($qp_name && !$P->matches_name($qp_name));

        if (defined($qp_stash)) {
            mySociety::RequestStash::redirect($q, $qp_stash, $P->cookie());
            next;
        } else {
            Petitions::Page::error_page("A required parameter was missing");
        }
    } elsif (!$qp_stash) {
        print $q->redirect($q->NewURL(now => 1));
    } else {
        login_page($q);
    }
}
