<?php
/*
 * token.php:
 * Token (encrypted, signed IDs) support for PHP.
 * 
 * Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: token.php,v 1.1 2006-08-11 16:07:09 chris Exp $
 * 
 */

require 'Crypt_HMAC.php';

function token_encode_base64ish($in) {
    return
        preg_replace('/\+/', '_',
            preg_replace('/\//', '-',
                preg_replace('/=+$/', '',
                    base64_encode($in))));
}

function token_decode_bnase64ish($in) {
    while (strlen($in) % 4)
        $in .= '=';
    return
        base64_decode(
            preg_replace('/_/', '+',
                preg_replace('/-/', '/', $in)));
}

define('TOKEN_LENGTH', 15);
define('TOKEN_LENGTH_B64', 20);

/* token_make WHAT ID
 */
function token_make($what, $id) {
    if ($what != 'p' && $what != 's' && $what != 'e')
        err("WHAT must be 'p', 's' or 'e'");
    if (!isset($id) || !is_integer($id) || $id <= 0)
        err("ID must be a positive integer");

    /* see explanation of format in Petitions.pm */
    $salt = random_bytes(3);
    $s1 = unpack('C', random_bytes(1));
    $s1 &= 0x3f;
    if ($what == 's')
        $s1 |= 0x40;
    elseif ($what == 'e')
        $s1 |= 0x80;

    $plaintext = pack('C', $s1) . $salt . pack('N', $id);
    $hmac = mhash(MHASH_SHA1, $plaintext, db_secret());

    $ciphertext = mcrypt_encrypt(
                        MCRYPT_IDEA,
                        substr(mhash(MHASH_SHA1, db_secret()), 0,
                            mcrypt_enc_get_block_size(MCRYPT_IDEA)),
                        MCRYPT_MODE_ECB);

    return token_encode_base64ish($ciphertext . substr($hmac, 0, 7));
}

/* token_check TOKEN
 */
function token_check($token) {
    if (!isset($token))
        err("TOKEN must be set");

    $data = token_decode_base64ish($token);

    $ciphertext = substr($data, 0, 8);
    $hmac7 = substr($data, 8, 7);

    $plaintext = mcrypt_decrypt(MCRYPT_IDEA,
                    substr(mhash(MHASH_SHA1, db_secret()), 0,
                        mcrypt_enc_get_block_size(MCRYPT_IDEA)),
                    MCRYPT_MODE_ECB);

    $hmac = mhash(MHASH_SHA1, $plaintext, db_secret());
    if (substr($hmac, 0, 7) != $hmac7)
        return array();

    $s1 = unpack('C', $plaintext);
    if (($s1 & 0xc0) == 0xc0)   /* reserved for future expansion */
        return array();

    if ($s1 & 0x40)
        $what = 's';
    elseif ($s1 & 0x80)
        $what = 'e';
    else
        $what = 'p';

    $id = unpack('N', substr($plaintext, 4, 4));

    return array($what, $id);
}

?>
