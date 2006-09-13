<?
// acceptance.php:
// Aceeptance policy for ePetitions
//
// Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: acceptance.php,v 1.3 2006-09-13 15:27:55 matthew Exp $

// Load configuration file
require_once "../phplib/pet.php";

page_header(_('Acceptance Policy'));
?>

<h1><span dir="ltr">Acceptance Policy</span></h1>

<p>Petitions will only be rejected if they meet one or more
of the following criteria:</p>

<ul>

<li>Party political material&mdash;the Downing Street website is a
Government site, so party political content is not appropriate under the
normal rules governing the Civil Service;</li>

<li>false or defamatory statements;</li>

<li>information protected by an injunction or court order (for
example, the identities of children in custody disputes);</li>

<li>material which is commercially sensitive, confidential or which
may cause personal distress or loss;</li>

<li>the names of individual officials of public bodies, unless part
of the senior management of those organisations;</li>

<li>the names of family members of officials of public bodies, or
elected representatives;</li>

<li>the names of individuals, or information where they may be
identified, in relation to criminal accusations;</li>

<li>offensive language, such as obvious swear words or language that
is intemperate, inflammatory, or provocative, or to which people
reading it could reasonably take offence;</li>

<li>it isn't clear what the petition is asking signers to endorse;</li>

<li>it doesn't actually ask for an action;</li>

<li>attempting to market a product irrelevent to the role and office of the PM.</li>

</ul>

<?
page_footer();
