<?php

include "config.php";

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    die();
} else if (($_SESSION['TWOFA'] <=> 0) < 0) {
    header('Location: 2fa.php');
    die();
}

if (!isset($_GET['app'])) {
    http_response_code(404);
    die();
}

$app = rawurldecode($_GET['app']); 

if (!preg_match('/^[a-zA-Z0-9_\/]+$/', $app)) {
    http_response_code(400);
    die("Invalid request");
}

if (strpos($app, '/') === false) {
    $app .= "/index";
}

$source_path = "apps/$app";

if (strpos($source_path, ".php") === false) {
    $source_path .= ".php";
}

$real_base = realpath(__DIR__ . "/apps");
$real_path = realpath($source_path);

if (!$real_path || strpos($real_path, $real_base) !== 0 || !is_file($real_path)) {
    http_response_code(404);
    echo "App not found";
    die();
}

require($real_path);

?>
