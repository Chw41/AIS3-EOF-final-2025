<?php

include "config.php";
include "lib/user.php";
include "lib/csp.php";

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    die();
} else if (($_SESSION['TWOFA'] <=> 0) < 0) {
    // negative => TWOFA not passed
    header('Location: 2fa.php');
    die();
}

$csp_nonce = csp_generate_nonce();
header(csp_generate_header($csp_nonce));

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

        .grid-cols-\[min-content_1fr\] {
            grid-template-columns: min-content 1fr;
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
    <div id="app" class="flex flex-col h-screen bg-gradient-to-b from-[#106cbf] from-0% via-[#1182d2] via-35% to-[#125ad3] to-100%">
        <nav class="relative w-full bg-[rgba(255,255,255,0.625)] flex justify-between text-2xl text-gray-700 shadow-xl z-20">
            <div class="flex align-center overflow-x-auto">
                <button class="border-x-2 border-gray-500 shadow-xl bg-gradient-to-b from-0% from-[rgba(255,255,255,0.5)] to-100% to-transparent ml-4 mr-2 px-8 py-2">
                    <i class="fas fa-shapes"></i>
                </button>
                <div id="taskbar" class="flex align-center w-full overflow-x-auto scrollbar-none"></div>
            </div>
            <div class="border-l-2 border-gray-500 px-4 my-2 flex gap-6 align-center">
                <button id="menu-message-button">
                    <i class="fas fa-comment-dots"></i>
                </button>
                <button id="menu-user-button">
                    <i class="fas fa-user"></i>
                </button>
                <button id="menu-search-button">
                    <i class="fas fa-magnifying-glass"></i>
                </button>
                <button id="menu-info-button">
                    <i class="far fa-newspaper"></i>
                </button>
            </div>
            <div class="absolute right-0 top-full bg-white text-base px-4 w-[160px] shadow-2xl hidden" id="user-menu">
                <div class="p-2 text-center font-bold">
                    <?= $_SESSION['username'] ?>
                </div>
                <hr class="border-t border-gray-300" />
                <button class="w-full p-2 text-gray-700 flex gap-2 items-center" id="menu-user-personal">
                    <i class="fas fa-gear text-xl w-[1em]"></i> Personal
                </button>
                <hr class="border-t border-gray-300" />
                <button class="w-full p-2 text-gray-700 flex gap-2 items-center">
                    <i class="fas fa-rotate-right text-xl w-[1em]"></i> Restart
                </button>
                <button class="w-full p-2 text-gray-700 flex gap-2 items-center">
                    <i class="fas fa-power-off text-xl w-[1em]"></i> Shutdown
                </button>
                <hr class="border-t border-gray-300" />
                <button class="w-full p-2 text-gray-700 flex gap-2 items-center">
                    <i class="fas fa-info text-xl w-[1em]"></i> About
                </button>
                <hr class="border-t border-gray-300" />
                <a class="w-full p-2 text-gray-700 flex gap-2 items-center" href="logout.php">
                    <i class="fas fa-right-from-bracket text-xl w-[1em]"></i> Logout
                </a>
            </div>
            <div class="absolute right-0 top-full translate-y-[5px] bg-[rgba(255,255,255,0.625)] p-2 w-[300px] shadow-2xl hidden text-base" id="info-menu">
                <div class="flex justify-between items-center px-2 mb-2 font-bold">
                    <div>
                        <button><i class="fas fa-plus"></i></button>
                    </div>
                    <div class="flex gap-2">
                        <button><i class="fas fa-minus"></i></button>
                        <button><i class="fas fa-thumbtack"></i></button>
                        <button><i class="fas fa-bar-chart"></i></button>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-2">
                    <div class="bg-[rgba(255,255,255,0.625)] w-full p-2 grid gap-2">
                        <h1 class="text-large"><i class="fas fa-circle-info mr-1"></i> System</h1>
                        <div class="grid gap-2 p-1">
                            <div class="grid grid-cols-[min-content_1fr] gap-x-2">
                                <div class="row-span-2"><i class="fas fa-check-circle text-green-600 text-[4.5em]"></i></div>
                                <p class="self-end text-2xl text-green-600">Good</p>
                                <p class="self-start text-sm">Disk Studio works well.</p>
                            </div>
                            <div class="grid grid-cols-2">
                                <div class="font-bold">System Name</div> <div><?= $config['system']['name'] ?></div>
                                <div class="font-bold">Version</div> <div>DS v<?= $config['system']['version'] ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-[rgba(255,255,255,0.625)] w-full p-2">
                        <h1 class="text-large"><i class="fas fa-gauge-high mr-1"></i> Monitor</h1>
                        <div class="p-1">
                            <div class="grid grid-cols-[1fr_2fr]">
                                <div class="font-bold">CPU</div> <div class="grid grid-cols-[1fr_2em] gap-2 items-center"><div id="cpu-bar" class="rounded-[3px] overflow-hidden h-[1em] w-full bg-[rgb(203,211,222)] before:h-full before:w-[--bar-size] before:content-[''] before:block before:bg-gradient-to-r before:from-0% before:from-[rgb(14,165,254)] before:to-100% before:to-[rgb(36,147,228)]"></div> <span>- %</span></div>
                                <div class="font-bold">Memory</div> <div class="grid grid-cols-[1fr_2em] gap-2 items-center"><div id="memory-bar" class="rounded-[3px] overflow-hidden h-[1em] w-full bg-[rgb(203,211,222)] before:h-full before:w-[--bar-size] before:content-[''] before:block before:bg-gradient-to-r before:from-0% before:from-[rgb(14,165,254)] before:to-100% before:to-[rgb(36,147,228)]"></div> <span>- %</span></div>
                                <div class="font-bold">Network</div> <div class="grid grid-cols-2 items-center text-sm"> <div id="net-upload" class="text-blue-500"><i class="fas fa-up-long mr-1"></i> <span>2.5 KB/s</span></div> <div id="net-download" class="text-green-600"><i class="fas fa-down-long mr-1"></i> <span>2.5 KB/s</span></div> </div>
                            </div>
                            <div class="w-[260px] h-[6em]" id="net-chart-container"></div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        <div id="desktop" class="flex-1 relative z-10 overflow-hidden">
            <div id="desktop-icons" class="h-full flex flex-col flex-wrap items-start justify-start content-start gap-2 overflow-hidden px-8 py-4">
                <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2" id="files-app">
                    <i class="fas fa-folder text-[4em] text-yellow-500"></i>
                    <span class="text-white select-none">Files</span>
                </div>
                <?php if (user_is_admin($_SESSION['username'])) { ?>
                <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2" id="controls-app">
                    <i class="fas fa-sliders text-[4em] text-white"></i>
                    <span class="text-white select-none">Controls</span>
                </div>
                <?php } ?>
                <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2" id="manual-app">
                    <i class="fas fa-question-circle text-[4em] text-gray-300"></i>
                    <span class="text-white select-none">Manual</span>
                </div>
                <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 hidden" id="settings-app">
                    <i class="fas fa-gear text-[4em] text-gray-500"></i>
                    <span class="text-white select-none">Personal</span>
                </div>
            </div>
            <div class="contents" id="windows">

            </div>
        </div>
        <i class="fas fa-book-open fixed right-0 bottom-0 transform translate-x-[58%] translate-y-[125%] rotate-[180deg] scale-[2] text-[100vw] leading-[100vw] text-white opacity-20 select-none"></i>
    </div>
    <template id="window-template">
        <div class="absolute top-0 left-0 border-blue-300 shadow-md resize overflow-auto flex flex-col bg-white">
            <nav class="w-full bg-gradient-to-b from-0% from-[rgba(31,139,228,0.3)] to-100% to-white flex items-center justify-between px-2 py-1 cursor-move">
                <i></i>
                <p class="font-bold text-[rgb(31,139,228)] select-none"></p>
                <div class="flex gap-2 text-gray-500">
                    <button><i class="fas fa-minus"></i></button>
                    <button><i class="fas fa-square"></i></button>
                    <button><i class="fas fa-x"></i></button>
                </div>
            </nav>
            <iframe src="" class="bg-white flex-1"></iframe>
        </div>
    </template>
    <script nonce="<?= $csp_nonce ?>">
        const activeMenuClass = 'text-[#1182d2]';

        const $menuUserButton = document.querySelector('#menu-user-button');
        const $userMenu = document.querySelector('#user-menu');

        const $menuInfoButton = document.querySelector('#menu-info-button');
        const $infoMenu = document.querySelector('#info-menu');

        for (let [$button, $menu] of [[$menuUserButton, $userMenu], [$menuInfoButton, $infoMenu]]) {
            $button.addEventListener('click', function () {
                for (let $b of document.querySelectorAll('button[id^="menu-"]')) {
                    if ($b.classList.contains(activeMenuClass) && $b !== $button) {
                        $b.click();
                    }
                }

                if ($button.classList.contains(activeMenuClass)) {
                    $button.classList.remove(activeMenuClass);
                    $menu.classList.add('hidden');
                } else {
                    $button.classList.add(activeMenuClass);
                    $menu.classList.remove('hidden');
                }
            });
        }

        const $menuUserPersonal = document.querySelector('#menu-user-personal');
        $menuUserPersonal.addEventListener('click', function () {
            document.querySelector("#settings-app").click();
        });

        const $desktop = document.querySelector('#desktop');
        const $windowsContainer = document.querySelector('#windows');
        let originX = 0, originY = 0;

        $desktop.addEventListener('click', (ev) => {
            // close user menu
            if ($menuUserButton.classList.contains(activeMenuClass)) {
                $menuUserButton.click();
            }
        });

        $windowsContainer.addEventListener('mousedown', function (ev) {
            if (!ev.target.matches('#windows > div[id^="window-"] *')) return;

            const $window = ev.target.closest('div[id^="window-"]');

            if (ev.target.matches('nav')) {
                ev.preventDefault();
                $window.dataset.dragging = true;

                document.querySelectorAll('#windows > div[id^="window-"] iframe').forEach($iframe => {
                    $iframe.classList.toggle('pointer-events-none', true);
                });
                
                originX = ev.screenX;
                originY = ev.screenY;
            }

            Array.from($windowsContainer.children).forEach($w => {
                $w.classList.remove('z-50');
            });
            $window.classList.add('z-50');
        });
        
        document.addEventListener('mouseup', function (ev) {
            const $window = document.querySelector('div[id^="window-"][data-dragging="true"]');
            if (!$window) return;
            
            ev.preventDefault();
            
            delete $window.dataset.dragging;

            document.querySelectorAll('#windows > div[id^="window-"] iframe').forEach($iframe => {
                $iframe.classList.toggle('pointer-events-none', false);
            });
            
            const winRect = $window.getBoundingClientRect();
            const desktopRect = $desktop.getBoundingClientRect();
            const x = winRect.x;
            const y = winRect.y - desktopRect.y;
            
            $window.style.top = `${y}px`;
            $window.style.left = `${x}px`;
            $window.style.removeProperty('transform');
        });
        
        window.addEventListener('mousemove', function (ev) {
            const $window = document.querySelector('div[id^="window-"][data-dragging="true"]');
            if (!$window) return;
            
            ev.preventDefault();

            const windowsRect = $desktopIcons.getBoundingClientRect();

            if (ev.clientX < 0 || ev.clientX >= windowsRect.right ||
                ev.clientY < windowsRect.top || ev.clientY >= windowsRect.bottom) {
                return;
            }

            // use translate instead of top/left
            const deltaX = ev.screenX - originX;
            const deltaY = ev.screenY - originY;

            $window.style.transform = `translate(${deltaX}px, ${deltaY}px)`;
        });

        const $desktopIcons = $desktop.querySelector('#desktop-icons');
        const $taskbar = document.querySelector('#taskbar');

        $desktopIcons.addEventListener('click', (ev) => {
            if (!ev.target.matches('#desktop-icons > div, #desktop-icons > div *')) return;

            const $app = ev.target.closest('div[id$="-app"]');
            const appId = $app.id.split('-')[0];

            // create window from window-template
            const $clone = document.querySelector('#window-template').content.cloneNode(true);
            const $appWindow = $clone.querySelector('div');
            const $appIcon = $appWindow.querySelector('nav > i');
            const $appTitle = $appWindow.querySelector('nav > p');
            const $iframe = $appWindow.querySelector('iframe');

            $appWindow.id = `window-${Math.random().toString(36).slice(2)}`;
            $appWindow.style.width = `700px`;
            $appWindow.style.height = `450px`;
            $appIcon.className = $app.querySelector('i').className;
            $appIcon.classList.remove('text-[4em]');
            $appTitle.innerText = $app.querySelector('span').innerText;
            $iframe.src = `app.php?app=${appId}`;

            $windowsContainer.appendChild($appWindow);

            // create taskbar button
            const $taskbarButton = document.createElement('button');
            $taskbarButton.id = `taskbar-${$appWindow.id}`;
            $taskbarButton.dataset.app = appId;
            $taskbarButton.className = 'border-x-2 border-gray-500 bg-[rgba(255,255,255,0.2)] px-4 h-full';
            $taskbarButton.innerHTML = $app.querySelector('i').outerHTML;
            $taskbarButton.querySelector('i').classList.remove('text-[4em]');
            $taskbarButton.querySelector('i').classList.add('text-4xl');
            $taskbarButton.addEventListener('click', () => {
                $appWindow.classList.toggle('hidden');

                if (!$appWindow.classList.contains('hidden')) {
                    Array.from($windowsContainer.children).forEach($w => {
                        $w.classList.remove('z-50');
                    });
                    $appWindow.classList.add('z-50');
                }
            });
            $taskbar.appendChild($taskbarButton);

            // scroll to the right
            $taskbar.scrollLeft = $taskbar.scrollWidth;
        });

        $windowsContainer.addEventListener('click', (ev) => {
            if (!ev.target.matches('#windows > div[id^="window-"] > nav > div > button, #windows > div[id^="window-"] > nav > div > button > i')) return;

            const $button = ev.target.closest('button');
            const $window = $button.closest('div[id^="window-"]');

            if ($button.querySelector('i').classList.contains('fa-minus')) {
                $window.classList.add('hidden');
            } else if ($button.querySelector('i').classList.contains('fa-x')) {
                $window.remove();
                $taskbar.querySelector(`#taskbar-${$window.id}`).remove();
            } else if ($button.querySelector('i').classList.contains('fa-square')) {
                $window.classList.toggle('!size-full');
                $window.classList.toggle('!top-0');
                $window.classList.toggle('!left-0');
            }
        });

        window.addEventListener('message', (ev) => {
            const { type } = ev.data;

            if (type === 'close') {
                const $window = ev.source.frameElement.parentElement;
                if ($window) {
                    $window.querySelector('nav > div > button > i.fa-x').click();
                }
            }
        });

        // convert scroll to horizontal scroll
        $taskbar.addEventListener('wheel', (ev) => {
            ev.preventDefault();
            $taskbar.scrollLeft += ev.deltaY;
        });

        // CPU and Memory bar
        const $cpuBar = document.querySelector('#cpu-bar');
        const $memoryBar = document.querySelector('#memory-bar');

        const updateBar = ($bar, value) => {
            $bar.style.setProperty('--bar-size', `${value}%`);
            $bar.nextElementSibling.innerText = `${Math.round(value)}%`;
        };

        setInterval(() => {
            const cpuUsage = (Math.random() < 0.1) ? Math.random() * 100 : Math.random() * 10;
            const memoryUsage = (Math.random() < 0.1) ? Math.random() * 100 : Math.random() * 10;

            updateBar($cpuBar, cpuUsage);
            updateBar($memoryBar, memoryUsage);
        }, 2000);

        // Network
        const $netUpload = document.querySelector('#net-upload').querySelector('span');
        const $netDownload = document.querySelector('#net-download').querySelector('span');
        const $netChartContainer = document.querySelector('#net-chart-container');

        const chartSamplePoints = 20;
        const uploadSpeedSamples = new Array(chartSamplePoints).fill('-');
        const downloadSpeedSamples = new Array(chartSamplePoints).fill('-');
        const netChart = echarts.init($netChartContainer);

        netChart.setOption({
            grid: {
                top: 10,
                right: 5,
                left: 86,
                bottom: 10,
                show: true,
                backgroundColor: 'white'
            },
            xAxis: {
                type: 'category',
                axisTick: {
                    show: false,
                    inside: true
                },
                axisLabel: {
                    show: false,
                    inside: true,
                    margin: 0
                },
            },
            yAxis: {
                type: 'value',
                splitNumber: 5
            },
            animation: false,
            series: [
                {
                    name: 'upload',
                    type: 'line',
                    data: uploadSpeedSamples,
                    symbol: 'none',
                    silent: true,
                    lineStyle: {
                        color: 'blue'
                    }
                },
                {
                    name: 'download',
                    type: 'line',
                    data: downloadSpeedSamples,
                    symbol: 'none',
                    silent: true,
                    lineStyle: {
                        color: 'green'
                    }
                }
            ]
        });

        setInterval(() => {
            const upload = (Math.random() > 0.9) ? Math.random() * 100 : Math.random() * 10;
            const download = (Math.random() > 0.9) ? Math.random() * 100 : Math.random() * 10;

            $netUpload.innerText = `${upload.toFixed(1)} KB/s`;
            $netDownload.innerText = `${download.toFixed(1)} KB/s`;

            uploadSpeedSamples.shift();
            uploadSpeedSamples.push(upload);
            downloadSpeedSamples.shift();
            downloadSpeedSamples.push(download);

            netChart.setOption({
                series: [
                    {
                        name: 'upload',
                        data: uploadSpeedSamples,
                    },
                    {
                        name: 'download',
                        data: downloadSpeedSamples,
                    }
                ]
            });
            netChart.resize();
        }, 2000);
    </script>
</body>
</html>