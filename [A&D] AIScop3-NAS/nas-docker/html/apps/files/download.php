<?php

set_include_path(get_include_path() . PATH_SEPARATOR . '/var/www/html');

include_once "config.php";
include_once "lib/user.php";

if (!isset($_SESSION['username'])) {
    http_response_code(403);
    die();
} else if (($_SESSION['TWOFA'] <=> 0) < 0) {
    // negative => TWOFA not passed
    http_response_code(403);
    die();
}

if (!$_SERVER['REQUEST_METHOD'] == 'GET') {
    http_response_code(405);
    die();
}

if (!isset($_GET['volume'])) {
    http_response_code(400);
    echo "Parameter wrong.";
    die();
}

$volume_name = $_GET['volume'];
$volumes = json_decode(file_get_contents($config['volumes_path']), true);

if (!isset($volumes[$volume_name])) {
    http_response_code(400);
    echo "Volume not found.";
    die();
}

$volume = $volumes[$volume_name];

// check user permissions
$user_groups = user_get_groups($_SESSION['username']);
$user_permissions = [];

foreach ($volume['permissions'] as $permission) {
    if (in_array($permission['group'], $user_groups) && count($permission['permissions']) > count($user_permissions)) {
        $user_permissions = $permission['permissions'];
    }
}

if (!in_array('read', $user_permissions)) {
    http_response_code(403);
    die();
}

if (!isset($_GET['dir'])) {
    $dir = '/';
} else {
    $dir = $_GET['dir'];
}

if (!isset($_GET['file'])) {
    http_response_code(400);
    echo "Parameter wrong.";
    die();
}

$filename = basename($_GET['file']);

$file_path = join('/', [$volumes[$volume_name]["path"], $dir, $filename]);
if (!file_exists($file_path) || !is_file($file_path)) {
    http_response_code(400);
    echo "File not found.";
    die();
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));

readfile($file_path);

?>