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

if (!$_SERVER['REQUEST_METHOD'] == 'POST') {
    http_response_code(405);
    die();
}

if (!isset($_POST['volume'])) {
    http_response_code(400);
    die();
}

$volume_name = $_POST['volume'];
$volumes = json_decode(file_get_contents($config['volumes_path']), true);

if (!isset($volumes[$volume_name])) {
    http_response_code(400);
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

if (!in_array('write', $user_permissions)) {
    http_response_code(403);
    die();
}
// check user permissions end

if (!isset($_POST['dir'])) {
    $dir = '/';
} else {
    $dir = $_POST['dir'];
}

$dir_path = join('/', [$volumes[$volume_name]["path"], $dir]);
if (!file_exists($dir_path) || !is_dir($dir_path)) {
    http_response_code(400);
    die();
}

if (!isset($_FILES['upload'])) {
    http_response_code(400);
    die();
}

$uploaded_file = $_FILES['upload'];

$upload_filename = basename($uploaded_file['name']);

$result = move_uploaded_file($uploaded_file['tmp_name'], join('/', [$dir_path, $upload_filename]));

if (!$result) {
    http_response_code(500);
    die();
}

http_response_code(200);

?>