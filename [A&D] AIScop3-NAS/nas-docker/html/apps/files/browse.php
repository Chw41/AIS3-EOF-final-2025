<?php

set_include_path(get_include_path() . PATH_SEPARATOR . '/var/www/html');

include_once "config.php";
include_once "lib/user.php";

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    die();
} else if (($_SESSION['TWOFA'] <=> 0) < 0) {
    // negative => TWOFA not passed
    header('Location: 2fa.php');
    die();
}

if (!isset($_GET['volume'])) {
    echo "Parameter wrong.";
    die();
}

$volume_name = $_GET['volume'];

$volumes = json_decode(file_get_contents($config['volumes_path']), true);

if (!isset($volumes[$volume_name])) {
    echo "Volume not found.";
    die();
}

$volume_path = $volumes[$volume_name]["path"];

if (!isset($_GET['dir'])) {
    $dir = '';
} else {
    $dir = $_GET['dir'];
}

if ($dir == '') {
    $dir = '/';
}

if (!file_exists(join('/', [$volume_path, $dir]))) {
    echo "Directory not found.";
    die();
}

function human_bytes($bytes) {
    $units = ['bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $i = 0;
    while ($bytes >= 1024) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

function timestamp2date($time) {
    return date('Y-m-d H:i:s', $time);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $config['system']['name'] ?> - Disk Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.2/css/fontawesome.min.css" integrity="sha256-TBe0l9PhFaVR3DwHmA2jQbUf1y6yQ22RBgJKKkNkC50=" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.2/css/solid.min.css" integrity="sha256-ETbOOM9U8WfaXg2AkW2t+QbdpWYFRAO+HEXOth8EQbY=" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.6.0/dist/echarts.min.js" integrity="sha256-v0oiNSTkC3fDBL7GfhIiz1UfFIgM9Cxp3ARlWOEcB7E=" crossorigin="anonymous"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        .text-shadow {
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        .grid-cols-\[min-content_1fr_\_min-content\] {
            grid-template-columns: min-content 1fr min-content;
        }

        .scrollbar-none::-webkit-scrollbar {
            display: none;
        }

        .scrollbar-none {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>
<body>
    <div id="app" class="h-screen w-screen flex flex-col px-2 pl-4 py-2">
        <div class="flex-1 overflow-auto" id="files-container">
            <table class="table-fixed min-w-full">
                <!-- Name (full), Size, Type, Modify Time -->
                <thead class="text-[rgb(31,139,228)] select-none border-t-2 border-y border-gray-300">
                    <tr>
                        <th class="w-full border-r border-gray-300 px-2 py-1 text-left">Name <i class="fas fa-caret-up text-sm"></i></th>
                        <th class="border-r border-gray-300 px-2 py-1 text-right">Size</th>
                        <th class="border-r border-gray-300 px-2 py-1">Type</th>
                        <th class="border-r border-gray-300 px-2 py-1 text-right whitespace-nowrap">Modified Date</th>
                        <th class="px-2"><i class="fa-solid fa-ellipsis-vertical"></i></th>
                    </tr>
                </thead>
                <tbody class="whitespace-nowrap select-none" id="files-table">
                    <?php
                        $count = 0;
                        chdir(join('/', [$volume_path, $dir]));
                        foreach(scandir('.') as $file) {
                            if ($file == '.' || $file == '..') continue;
                            $count++;
                    ?>
                    <tr class="border-b border-gray-300 hover:cursor-pointer hover:bg-[rgb(0,0,255,0.1)]" data-type="<?= ((is_dir($file)) ? 'folder' : 'file'); ?>" data-path="<?= htmlspecialchars(basename($file)) ?>">
                        <td class="px-2 py-1"><i class="fas <?= ((is_dir($file)) ? 'fa-folder text-yellow-500' : 'fa-file text-gray-400'); ?> text-center w-[1.5rem] mr-1"></i><?= htmlspecialchars(basename($file)); ?></td>
                        <td class="px-2 py-1 text-right"><?= ((!is_dir($file)) ? human_bytes(filesize($file)) : ''); ?></td>
                        <td class="px-2 py-1"><?= ((is_dir($file)) ? 'Folder' : 'File'); ?></td>
                        <td class="px-2 py-1 text-right"><?= timestamp2date(filemtime($file)); ?></td>
                        <td></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <div class="flex items-center justify-end text-[rgb(31,139,228)] text-sm border-y border-gray-300">
            <span class="border-r border-gray-300 px-2 py-1"><?= $count; ?> Items</span>
            <button class="px-4 py-1" id="refresh"><i class="fas fa-rotate-right"></i></button>
        </div>
    </div>
    <script>
        const $filesTable = document.getElementById('files-table');
        const $refresh = document.getElementById('refresh');
        const dir = '<?= $dir ?>';
        const volume = '<?= $volume_name ?>';

        $refresh.addEventListener('click', ev => {
            location.reload();
        });

        window.addEventListener('DOMContentLoaded', ev => {
            parent.postMessage({"event": "load", "volume": volume, "path": dir}, '*');
        });

        document.querySelectorAll('tr').forEach($tr => {
            $tr.addEventListener('click', ev => {
                $filesTable.querySelectorAll('tr').forEach($tr => {
                    $tr.classList.remove('bg-[rgb(0,0,255,0.1)]');
                });
                const $target = ev.currentTarget;
                $target.classList.add('bg-[rgb(0,0,255,0.1)]');

                const type = $target.getAttribute('data-type');
                const path = $target.getAttribute('data-path');

                parent.postMessage({"event": "selected", "type": type, "volume": volume, "dir": dir, "path": path}, '*');
            });

            $tr.addEventListener('dblclick', ev => {
                const $target = ev.currentTarget;
                const type = $target.getAttribute('data-type');
                const path = $target.getAttribute('data-path');
                if (dir == '/') {
                    parent.postMessage({"event": "open", "type": type, "volume": volume, "path": `${path}`}, '*');
                } else {
                    parent.postMessage({"event": "open", "type": type, "volume": volume, "path": `${dir}/${path.trim('/')}`}, '*');
                }
            });
        });

        document.querySelector('#files-container').addEventListener('click', function(ev) {
            if (ev.target != this) return;

            $filesTable.querySelectorAll('tr').forEach($tr => {
                $tr.classList.remove('bg-[rgb(0,0,255,0.1)]');
            });
            parent.postMessage({"event": "selected", "type": null, "volume": volume, "path": null}, '*');
        });
    </script>
</body>
</html>