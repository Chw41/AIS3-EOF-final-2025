<?php

include "config.php";

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    die();
} else if (($_SESSION['TWOFA'] <=> 0) < 0) {
    // negative => TWOFA not passed
    header('Location: 2fa.php');
    die();
}

if (!isset($_GET['app'])) {
    http_response_code(404);
    die();
}

$app = $_GET['app'];
if (!strpos($app, '/')) {
    $app .= "/index";
}

$source_path = "apps/$app";

if (!strpos($source_path, "php")) {
    $source_path .= ".php";
}

// No LFI!
preg_replace('/\.\.\//', '', $source_path);

if (!file_exists($source_path)) {
    http_response_code(404);
    echo "App not found";
    die();
}

require($source_path);

?>