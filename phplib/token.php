<?php
/*
 * token.php:
 * Token (encrypted, signed IDs) support for PHP.
 * 
 * Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: token.php,v 1.9 2010-03-12 00:06:38 matthew Exp $
 * 
 */

require_once '../commonlib/phplib/db.php';
require_once '../commonlib/phplib/random.php';
require_once '../commonlib/phplib/BaseN.php';

/* token_make WHAT ID
 * Make a token identifying the given ID (of a petition or signer). WHAT
 * indicates what is identified and the context in which it is identified; 'p'
 * means a petition for confirmation; 's' means a signer for confirmation; and
 * 'e' means a petition for editing after a first rejection.*/
function token_make($what, $id) {
    if ($what != 'p' && $what != 's' && $what != 'e')
        err("WHAT must be 'p', 's' or 'e'");
    if (!isset($id) || !is_integer($id) || $id <= 0)
        err("ID must be a positive integer");

    /* see explanation of format in Petitions.pm */
    $s1 = unpack('C', urandom_bytes(1));
    $s1 = $s1[1];
    $s1 &= 0x3f;
    if ($what == 's')
        $s1 |= 0x40;
    elseif ($what == 'e')
        $s1 |= 0x80;

    $plaintext = pack('C', $s1) . urandom_bytes(3) . pack('N', $id);

    $hmac = mhash(MHASH_SHA1, $plaintext, db_secret());

    $ciphertext = mcrypt_encrypt(
                        MCRYPT_BLOWFISH,
                        substr(mhash(MHASH_SHA1, db_secret()), 0, 8),
                        $plaintext,
                        MCRYPT_MODE_ECB,
                        "\0\0\0\0\0\0\0\0");

    return basen_encodefast(62, $ciphertext . substr($hmac, 0, 7));
}

/* token_check TOKEN
 * Check the validity of a TOKEN. Returns an array giving the WHAT and ID that
 * were passed to token_make; or if the TOKEN is invalid, an array of two
 * nulls. */
function token_check($token) {
    if (!isset($token))
        err("TOKEN must be set");

    $data = basen_decodefast(62, $token);

    $ciphertext = substr($data, 0, 8);
    $hmac7 = substr($data, 8, 7);

    $plaintext = mcrypt_decrypt(
                    MCRYPT_BLOWFISH,
                    substr(mhash(MHASH_SHA1, db_secret()), 0, 8),
                    $ciphertext,
                    MCRYPT_MODE_ECB,
                    "\0\0\0\0\0\0\0\0");

    $hmac = mhash(MHASH_SHA1, $plaintext, db_secret());
    if (substr($hmac, 0, 7) != $hmac7)
        return array(null, null);

    $s1 = unpack('C', $plaintext);
    $s1 = $s1[1];

    if (($s1 & 0xc0) == 0xc0)   /* reserved for future expansion */
        return array(null, null);

    if ($s1 & 0x40)
        $what = 's';
    elseif ($s1 & 0x80)
        $what = 'e';
    else
        $what = 'p';

    $id = unpack('N', substr($plaintext, 4, 4));
    $id = $id[1];

    return array($what, $id);
}

?>
