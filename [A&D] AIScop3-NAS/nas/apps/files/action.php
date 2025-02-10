<?php

// handle directory creation, file deletion

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

if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    $action = 'create';
} else if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    $action = 'delete';
} else {
    http_response_code(405);
    die();
}

parse_str(file_get_contents('php://input'), $_POST);

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

$perm = $action == 'create' ? 'write' : 'delete';

if (!in_array($perm, $user_permissions)) {
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

if ($action == 'create') {
    $new_dir = join('/', [$dir_path, $_POST['folder']]);
    if (file_exists($new_dir)) {
        http_response_code(400);
        die();
    }

    if (!mkdir($new_dir)) {
        http_response_code(500);
        die();
    }
} else if ($action == 'delete') {
    $file_path = join('/', [$dir_path, $_POST['file']]);
    if (!file_exists($file_path)) {
        http_response_code(400);
        die();
    }

    if (is_dir($file_path)) {
        $result = rrmdir($file_path);
    } else {
        $result = unlink($file_path);
    }

    if (!$result) {
        http_response_code(500);
        die();
    }
}

function rrmdir($dir) {
    // use scandir
    foreach (scandir($dir) as $file) {
        if ($file == '.' || $file == '..') continue;

        $full_path = join('/', [$dir, $file]);

        if (is_dir($full_path)) {
            rrmdir($full_path);
        } else {
            unlink($full_path);
        }
    }
    return rmdir($dir);
}

?>