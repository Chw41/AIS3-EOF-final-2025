<?php

define('TWOFA_FAILED', -2);
define('TWOFA_UNAUTH', -1);
define('TWOFA_AUTH', 1);

if (!isset($_SESSION)) {
    session_start();
}

const CONFIG_PATH = "/var/www/config/config.json";
global $config;
$config = json_decode(file_get_contents(CONFIG_PATH), true);

?>