<?php

global $otp_db;
$otp_db = json_decode(file_get_contents($config['otp_db_path']), true);

function base32_encode($str) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $str = str_split($str);
    $bin = '';
    foreach ($str as $char) {
        $bin .= sprintf('%08b', ord($char));
    }
    $bin = str_split($bin, 5);
    $str = '';
    foreach ($bin as $char) {
        $str .= $chars[bindec($char)];
    }
    return $str;
}

function base32_decode($str) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $chars = array_flip(str_split($chars));
    $str = strtoupper($str);
    $str = str_split($str);
    $bin = '';
    foreach ($str as $char) {
        $bin .= sprintf('%05b', $chars[$char]);
    }
    $bin = str_split($bin, 8);
    $str = '';
    foreach ($bin as $char) {
        $str .= chr(bindec($char));
    }
    return $str;
}

// TOTP functions
const TOTP_BYTES = 40;
const TOTP_DIGITS = 6;
const TOTP_PERIOD = 30;

function otp_generate_secret() {
    // use php secure random_bytes()
    $bytes = random_bytes(TOTP_BYTES);
    $secret = base32_encode($bytes);
    return $secret;
}

function otp_generate_url($secret, $name) {
    $url = 'otpauth://totp/' . $name . '?secret=' . $secret;
    return $url;
}

function otp_generate($secret) {
    $time = time();
    $time = floor($time / TOTP_PERIOD);
    $otp = hash_hmac('sha1', pack('J', $time), base32_decode($secret), true);
    $offset = ord($otp[strlen($otp) - 1]) & 0xf;
    $otp = (
        ((ord($otp[$offset + 0]) & 0x7f) << 24) |
        ((ord($otp[$offset + 1]) & 0xff) << 16) |
        ((ord($otp[$offset + 2]) & 0xff) << 8) |
        (ord($otp[$offset + 3]) & 0xff)
    );
    $otp = $otp % pow(10, TOTP_DIGITS);
    $otp = str_pad($otp, TOTP_DIGITS, '0', STR_PAD_LEFT);
    return $otp;
}

function otp_verify($secret, $otp) {
    $time = time();
    $status = false;

    for ($i = $time - 1; $i <= $time + 1; $i++) {
        if (hash_equals($otp, otp_generate($secret, $i))) {
            $status = true;
        }
    }

    return $status;
}

?>