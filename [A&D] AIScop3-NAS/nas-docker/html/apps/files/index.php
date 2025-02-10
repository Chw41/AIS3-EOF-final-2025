<?php

set_include_path(get_include_path() . PATH_SEPARATOR . "/var/www/html");

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

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
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
    <style type="text/tailwindcss">
        @layer components {
            .btn {
                @apply bg-[rgb(240,244,249)] border border-[rgb(203,211,221)] rounded text-gray-700 whitespace-nowrap p-1 cursor-pointer;
            }
        }
    </style>
</head>
<body>
    <div id="app" class="flex flex-col h-screen gap-2">
        <nav class="flex flex-col gap-2 px-3">
            <div class="grid grid-cols-[min-content_1fr_min-content] gap-2">
                <div class="flex items-center gap-2">
                    <button class="btn" id="browser-back"><i class="fas fa-chevron-left w-[1.5rem]"></i></button>
                    <button class="btn" id="browser-forward"><i class="fas fa-chevron-right w-[1.5rem]"></i></button>
                    <button class="btn mx-1"  id="browser-reload"><i class="fas fa-rotate-right w-[1.5rem]"></i></button>
                </div>
                <div class="flex items-center w-full border p-1">
                    <input type="text" class="h-full w-full" id="path" readonly />
                    <button><i class="fas fa-star w-[1.5rem] text-gray-300"></i></button>
                </div>
                <div class="flex items-center gap-2 border p-1">
                    <button class="whitespace-nowrap"><i class="fas fa-magnifying-glass w-[1.5rem]"></i><i class="fas fa-caret-down text-sm"></i></button>
                    <input type="text" class="w-[10em] h-full" placeholder="Search" />
                </div>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="btn px-3 relative" id="menu-upload-btn">
                        Upload <i class="fas fa-caret-down text-sm"></i>
                        <div class="hidden absolute left-0 bottom-0 translate-y-[calc(100%+3px)] p-2 bg-white border border-gray-300 shadow-md flex flex-col items-start">
                            <button class="px-2 py-1 rounded hover:bg-[rgba(0,0,255,0.1)] w-full text-left" id="menu-upload-ignore-btn"><i class="fas fa-upload"></i> Upload (Ignore)</button>
                            <button class="px-2 py-1 rounded hover:bg-[rgba(0,0,255,0.1)] w-full text-left opacity-50" disabled><i class="fas fa-upload"></i> Upload (Overwrite)</button>
                        </div>
                    </div>
                    <div class="btn px-3 relative" id="menu-create-btn">
                        Create <i class="fas fa-caret-down text-sm"></i>
                        <div class="hidden absolute left-0 bottom-0 translate-y-[calc(100%+3px)] p-2 bg-white border border-gray-300 shadow-md flex flex-col items-start">
                            <button class="px-2 py-1 rounded hover:bg-[rgba(0,0,255,0.1)] w-full text-left" id="menu-create-folder-btn"><i class="fas fa-folder-plus"></i> Create Folder</button>
                            <button class="px-2 py-1 rounded hover:bg-[rgba(0,0,255,0.1)] w-full text-left opacity-50" disabled><i class="fas fa-folder-plus"></i> Create Share Folder</button>
                        </div>
                    </div>
                    <div class="btn px-3 relative" id="menu-action-btn">
                        Action <i class="fas fa-caret-down text-sm"></i>
                        <div class="hidden absolute left-0 bottom-0 translate-y-[calc(100%+3px)] p-2 bg-white border border-gray-300 shadow-md flex flex-col items-start">
                            <button class="px-2 py-1 rounded hover:bg-[rgba(0,0,255,0.1)] w-full text-left opacity-50" disabled id="menu-action-download-btn"><i class="fas fa-download text-center w-[1.25rem]"></i> Download</button>
                            <hr class="w-full h-[1px] my-1 bg-gray-200" />
                            <button class="px-2 py-1 rounded hover:bg-[rgba(0,0,255,0.1)] w-full text-left opacity-50" disabled><i class="fas fa-arrow-up-right-from-square text-center w-[1.25rem]"></i> Open in New Tab</button>
                            <hr class="w-full h-[1px] my-1 bg-gray-200" />
                            <button class="px-2 py-1 rounded hover:bg-[rgba(0,0,255,0.1)] w-full text-left opacity-50" disabled><i class="fas fa-box-archive text-center w-[1.25rem]"></i> Compress</button>
                            <hr class="w-full h-[1px] my-1 bg-gray-200" />
                            <button class="px-2 py-1 rounded hover:bg-[rgba(0,0,255,0.1)] w-full text-left opacity-50" disabled><i class="fas fa-file-export text-center w-[1.25rem]"></i> Copy/Move to <i class="fas fa-caret-right"></i></button>
                            <button class="px-2 py-1 rounded hover:bg-[rgba(0,0,255,0.1)] w-full text-left opacity-50" disabled><i class="fas fa-copy text-center w-[1.25rem]"></i> Copy</button>
                            <button class="px-2 py-1 rounded hover:bg-[rgba(0,0,255,0.1)] w-full text-left opacity-50" disabled><i class="fas fa-scissors text-center w-[1.25rem]"></i> Cut</button>
                            <button class="px-2 py-1 rounded hover:bg-[rgba(0,0,255,0.1)] w-full text-left opacity-50" disabled id="menu-action-delete-btn"><i class="fas fa-trash-can text-center w-[1.25rem]"></i> Delete</button>
                            <button class="px-2 py-1 rounded hover:bg-[rgba(0,0,255,0.1)] w-full text-left opacity-50" disabled><i class="fas fa-i-cursor text-center w-[1.25rem]"></i> Rename</button>
                            <hr class="w-full h-[1px] my-1 bg-gray-200" />
                            <button class="px-2 py-1 rounded hover:bg-[rgba(0,0,255,0.1)] w-full text-left opacity-50" disabled><i class="fas fa-info text-center w-[1.25rem]"></i> Info</button>
                            <button class="px-2 py-1 rounded hover:bg-[rgba(0,0,255,0.1)] w-full text-left opacity-50" disabled><i class="fas fa-share text-center w-[1.25rem]"></i> Share</button>
                        </div>
                    </div>
                    <button class="btn px-3">Tools <i class="fas fa-caret-down text-sm"></i></button>
                    <button class="btn px-3">Settings</button>
                </div>
                <div class="flex items-center gap-2">
                    <div>
                        <button class="btn rounded-r-none"><i class="fas fa-list w-[1.5rem]"></i></button><button class="btn rounded-l-none"><i class="fas fa-caret-down text-sm"></i></button>
                    </div>
                    <button class="btn"><i class="fas fa-arrow-down-wide-short"></i></button>
                </div>
            </div>
        </nav>
        <div class="flex-1 grid grid-cols-[min-content_1fr]">
            <aside class="w-[20vw] h-full px-3">
                <details open id="volumes">
                    <summary class="font-bold text-lg rounded hover:cursor-pointer hover:bg-[rgba(0,0,255,0.1)] p-2"><?= $config['system']['name'] ?></summary>
                    <?php include "volume.php"; ?>
                </details>
            </aside>
            <iframe class="w-full h-full shadow-md" id="browser"></iframe>
        </div>
    </div>
    <script>
        const $browser = document.querySelector('#browser');
        const $path = document.querySelector('#path');
        const urlBase = "/app.php?app=files/browse";

        const $uploadBtn = document.querySelector('#menu-upload-ignore-btn');
        const $createFolderBtn = document.querySelector('#menu-create-folder-btn');
        const $downloadBtn = document.querySelector('#menu-action-download-btn');
        const $deleteBtn = document.querySelector('#menu-action-delete-btn');
        
        window.onload = () => {
            const firstVolume = document.querySelector('#volumes details summary').innerText.trim();
            $browser.src = `${urlBase}&volume=${firstVolume}&dir=/`;
            browser_loading(true);
        };

        // listen to messages from iframe
        window.addEventListener('message', ev => {
            console.log(ev.data);
            if (ev.data.event == 'open') {
                if (ev.data.type == 'folder') {
                    $browser.src = `${urlBase}&volume=${ev.data.volume}&dir=${ev.data.path}`;
                    browser_loading(true);
                }
            } else if (ev.data.event == 'load') {
                if (ev.data.path == '/') {
                    $path.value = `${ev.data.volume}`;
                } else {
                    $path.value = `${ev.data.volume}/${ev.data.path}`;
                }

                const permissions = get_volume_permissions(ev.data.volume);
                if (!permissions.includes('write')) {
                    $uploadBtn.setAttribute('disabled', '');
                    $uploadBtn.classList.add('opacity-50');
                    $createFolderBtn.setAttribute('disabled', '');
                    $createFolderBtn.classList.add('opacity-50');
                } else {
                    $uploadBtn.removeAttribute('disabled');
                    $uploadBtn.classList.remove('opacity-50');
                    $createFolderBtn.removeAttribute('disabled');
                    $createFolderBtn.classList.remove('opacity-50');

                    $uploadBtn.dataset.volume = ev.data.volume;
                    $uploadBtn.dataset.dir = ev.data.path;

                    $createFolderBtn.dataset.volume = ev.data.volume;
                    $createFolderBtn.dataset.dir = ev.data.path;
                }

                browser_loading(false);
            } else if (ev.data.event == 'selected') {
                const permissions = get_volume_permissions(ev.data.volume);

                if (ev.data.path == null) {
                    $downloadBtn.setAttribute('disabled', '');
                    $downloadBtn.classList.add('opacity-50');
                    $deleteBtn.setAttribute('disabled', '');
                    $deleteBtn.classList.add('opacity-50');
                } else {
                    if (ev.data.type == 'folder' || !permissions.includes('read')) {
                        $downloadBtn.setAttribute('disabled', '');
                        $downloadBtn.classList.add('opacity-50');
                    } else {
                        $downloadBtn.removeAttribute('disabled');
                        $downloadBtn.classList.remove('opacity-50');

                        $downloadBtn.dataset.volume = ev.data.volume;
                        $downloadBtn.dataset.dir = ev.data.dir;
                        $downloadBtn.dataset.path = ev.data.path;
                    }

                    if (!permissions.includes('delete')) {
                        $deleteBtn.setAttribute('disabled', '');
                        $deleteBtn.classList.add('opacity-50');
                    } else {
                        $deleteBtn.removeAttribute('disabled');
                        $deleteBtn.classList.remove('opacity-50');

                        $deleteBtn.dataset.volume = ev.data.volume;
                        $deleteBtn.dataset.dir = ev.data.dir;
                        $deleteBtn.dataset.path = ev.data.path;
                    }
                }
            }
        });

        document.querySelector('#volumes').addEventListener('click', ev => {
            if (ev.target.matches('#volumes > details > summary')) {
                const volume = ev.target.innerText.trim();
                $browser.src = `${urlBase}&volume=${volume}&dir=/`;
                browser_loading(true);
            }
        });

        document.querySelector('#browser-back').addEventListener('click', ev => {
            $browser.contentWindow.history.back();
            browser_loading(true);
        });
        
        document.querySelector('#browser-forward').addEventListener('click', ev => {
            $browser.contentWindow.history.forward();
            browser_loading(true);
        });
        
        document.querySelector('#browser-reload').addEventListener('click', ev => {
            $browser.contentWindow.location.reload();
            browser_loading(true);
        });

        function browser_loading(loading) {
            timeout = 0;
            if (!loading) {
                timeout = 500;
            }

            setTimeout(() => {
                $browser.classList.toggle('opacity-50', loading);
                $browser.classList.toggle('pointer-events-none', loading);
            }, timeout);
        }

        function get_volume_permissions(volume) {
            return document.querySelector(`#volumes summary[data-volume="${volume}"]`).getAttribute('data-permission').split(',');
        }

        document.querySelector('#menu-upload-btn').addEventListener('click', function(ev) {
            this.querySelector('.absolute').classList.toggle('hidden');
        });

        document.querySelector('#menu-create-btn').addEventListener('click', function(ev) {
            this.querySelector('.absolute').classList.toggle('hidden');
        });

        document.querySelector('#menu-action-btn').addEventListener('click', function(ev) {
            this.querySelector('.absolute').classList.toggle('hidden');
        });

        $downloadBtn.addEventListener('click', function(ev) {
            if (this.hasAttribute('disabled')) return;

            const volume = this.dataset.volume;
            const dir = this.dataset.dir;
            const path = this.dataset.path;

            window.open(`/app.php?app=files/download&volume=${volume}&dir=${dir}&file=${path}`);
        });

        $uploadBtn.addEventListener('click', function(ev) {
            const volume = this.dataset.volume;
            const dir = this.dataset.dir;

            const $input = document.createElement('input');
            $input.type = 'file';
            $input.multiple = false;
            $input.accept = '*/*';
            $input.style.display = 'none';
            $input.addEventListener('change', ev => {
                const file = $input.files[0];
                const formData = new FormData();
                formData.append('volume', volume);
                formData.append('dir', dir);
                formData.append('upload', file);

                fetch(`/app.php?app=files/upload`, {
                    method: 'POST',
                    body: formData
                }).then(res => {
                    if (res.ok) {
                        document.querySelector('#browser-reload').click();
                    } else {
                        alert('Failed to upload file');
                    }
                    $input.remove();
                });
            });

            $input.click();
        });

        $createFolderBtn.addEventListener('click', function(ev) {
            const volume = this.dataset.volume;
            const dir = this.dataset.dir;

            const folderName = prompt('Enter folder name');
            if (folderName == null) return;

            fetch(`/app.php?app=files/action`, {
                method: 'PUT',
                body: new URLSearchParams({
                    volume: volume,
                    dir: dir,
                    folder: folderName
                }).toString()
            }).then(res => {
                if (res.ok) {
                    document.querySelector('#browser-reload').click();
                } else {
                    alert('Failed to create folder');
                }
            });
        });

        $deleteBtn.addEventListener('click', function(ev) {
            if (this.hasAttribute('disabled')) return;

            const volume = this.dataset.volume;
            const dir = this.dataset.dir;
            const path = this.dataset.path;

            if (confirm(`Are you sure you want to delete ${path}?`)) {
                fetch(`/app.php?app=files/action`, {
                    method: 'DELETE',
                    body: new URLSearchParams({
                        volume: volume,
                        dir: dir,
                        file: path
                    }).toString()
                }).then(res => {
                    if (res.ok) {
                        document.querySelector('#browser-reload').click();
                    } else {
                        alert('Failed to delete file');
                    }
                });
            }
        });
    </script>
</body>
</html>