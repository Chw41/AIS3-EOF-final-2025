<?php

set_include_path(get_include_path() . PATH_SEPARATOR . '/var/www/html');

include_once "config.php";
include_once "lib/user.php";
include_once "lib/otp.php";

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    die();
} else if (($_SESSION['TWOFA'] <=> 0) < 0) {
    // negative => TWOFA not passed
    header('Location: 2fa.php');
    die();
} else if (!user_is_admin($_SESSION['username'])) {
    http_response_code(403);
    echo "Access Denied";
    die();
}

function get_uptime() {
    $boot_time = stat('/proc/1')['ctime'];
    $uptime = time() - $boot_time;
    $days = floor($uptime / 86400);
    $hours = floor(($uptime % 86400) / 3600);
    $minutes = floor(($uptime % 3600) / 60);
    $seconds = $uptime % 60;

    $result = '';
    if ($days > 0) $result .= $days . ' days ';
    if ($hours > 0) $result .= $hours . ' hours ';
    if ($minutes > 0) $result .= $minutes . ' minutes ';
    if ($seconds > 0) $result .= $seconds . ' seconds ';

    return $result;
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

        .grid-cols-\[min-content_1fr\] {
            grid-template-columns: min-content 1fr;
        }

        .grid-cols-\[min-content_1fr_1fr_min-content\] {
            grid-template-columns: min-content 1fr 1fr min-content;
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

            .active-tab {
                @apply text-[rgb(31,139,228)] after:block after:content-[''] after:w-[10px] after:h-[10px] after:absolute after:bottom-0 after:left-1/2 after:bg-white after:transform after:-translate-x-1/2 after:translate-y-[calc(100%+0.25rem)] after:rotate-[-45deg] after:border-t after:border-r after:border-gray-400;
            }
        }
    </style>
</head>
<body>
    <div id="app" class="flex flex-col h-screen gap-2relative">
        <div class="px-3" id="home">
            <nav class="flex items-center justify-between">
                <div class="flex items-center gap-2 border p-1">
                    <button class="whitespace-nowrap"><i class="fas fa-magnifying-glass w-[1.5rem]"></i></button>
                    <input type="text" class="w-[10em] h-full" placeholder="Search" />
                </div>
                <a href="#" class="font-bold text-[rgb(31,139,228)] select-none">Basic Mode <i class="fas fa-chevron-right"></i></a>
            </nav>
            <div class="flex-grow overflow-y-auto grid grid-cols-1 gap-2" id="controls-entries">
                <section>
                    <h1 class="text-lg text-[rgb(31,139,228)]">File Sharing</h1>
                    <hr class="border-gray-300 my-1" />
                    <div class="flex flex-wrap items-start w-full gap-2">
                        <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 w-[6.5em]" data-config="shared-folder">
                            <i class="fas fa-folder-open text-[2.5em] text-yellow-500"></i>
                            <span class="text-center leading-[0.95] select-none">Shared Folder</span>
                        </div>
        
                        <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 w-[6.5em]" data-config="file-services">
                            <i class="fas fa-file-invoice text-[2.5em] text-green-500"></i>
                            <span class="text-center leading-[0.95] select-none">File Services</span>
                        </div>
        
                        <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 w-[6.5em]" data-config="user">
                            <i class="fas fa-user text-[2.5em] text-emerald-400"></i>
                            <span class="text-center leading-[0.95] select-none">User</span>
                        </div>
        
                        <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 w-[6.5em]" data-config="group">
                            <i class="fas fa-users text-[2.5em] text-red-500"></i>
                            <span class="text-center leading-[0.95] select-none">Group</span>
                        </div>
        
                        <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 w-[6.5em]" data-config="domain-ldap">
                            <i class="fas fa-address-book text-[2.5em] text-blue-500"></i>
                            <span class="text-center leading-[0.95] select-none">Domain/LDAP</span>
                        </div>
                    </div>
                </section>
    
                <section>
                    <h1 class="text-lg text-[rgb(31,139,228)]">Connectivity</h1>
                    <hr class="border-gray-300 my-1" />
                    <div class="flex flex-wrap items-start w-full gap-2">
                        <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 w-[6.5em]" data-config="quiconnect">
                            <i class="fas fa-cloud-bolt text-[2.5em] text-cyan-500"></i>
                            <span class="text-center leading-[0.95] select-none">QuiConnect</span>
                        </div>
    
                        <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 w-[6.5em]" data-config="external-access">
                            <i class="fas fa-globe text-[2.5em] text-blue-500"></i>
                            <span class="text-center leading-[0.95] select-none">External Access</span>
                        </div>
    
                        <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 w-[6.5em]" data-config="network">
                            <i class="fas fa-network-wired text-[2.5em] text-gray-600"></i>
                            <span class="text-center leading-[0.95] select-none">Network</span>
                        </div>
    
                        <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 w-[6.5em]" data-config="dhcp-server">
                            <i class="fas fa-circle-dot text-[2.5em] text-blue-500"></i>
                            <span class="text-center leading-[0.95] select-none">DHCP Server</span>
                        </div>
    
                        <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 w-[6.5em]" data-config="wireless">
                            <i class="fas fa-wifi text-[2.5em] text-blue-500"></i>
                            <span class="text-center leading-[0.95] select-none">Wireless</span>
                        </div>
    
                        <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 w-[6.5em]" data-config="security">
                            <i class="fas fa-shield-cat text-[2.5em] text-yellow-500"></i>
                            <span class="text-center leading-[0.95] select-none">Security</span>
                        </div>
                    </div>
                </section>
    
                <section>
                    <h1 class="text-lg text-[rgb(31,139,228)]">System</h1>
                    <hr class="border-gray-300 my-1" />
                    <div class="flex flex-wrap items-start w-full gap-2">
                        <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 w-[6.5em]" data-config="info-center">
                            <i class="fas fa-info-circle text-[2.5em] text-blue-500"></i>
                            <span class="text-center leading-[0.95] select-none">Info Center</span>
                        </div>
    
                        <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 w-[6.5em]" data-config="theme">
                            <i class="fas fa-palette text-[2.5em] text-cyan-500"></i>
                            <span class="text-center leading-[0.95] select-none">Theme</span>
                        </div>
    
                        <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 w-[6.5em]" data-config="regional-options">
                            <i class="fas fa-map text-[2.5em] text-yellow-500"></i>
                            <span class="text-center leading-[0.95] select-none">Regional Options</span>
                        </div>
    
                        <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 w-[6.5em]" data-config="notification">
                            <i class="fas fa-comment-dots text-[2.5em] text-green-500"></i>
                            <span class="text-center leading-[0.95] select-none">Notification</span>
                        </div>
    
                        <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 w-[6.5em]" data-config="task-scheduler">
                            <i class="fas fa-calendar-check text-[2.5em] text-red-500"></i>
                            <span class="text-center leading-[0.95] select-none">Task Scheduler</span>
                        </div>
    
                        <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 w-[6.5em]" data-config="hardware-power">
                            <i class="fas fa-lightbulb text-[2.5em] text-yellow-500"></i>
                            <span class="text-center leading-[0.95] select-none">Hardware & Power</span>
                        </div>
    
                        <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 w-[6.5em]" data-config="external-devices">
                            <i class="fas fa-hard-drive text-[2.5em] text-green-500"></i>
                            <span class="text-center leading-[0.95] select-none">External Devices</span>
                        </div>
    
                        <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 w-[6.5em]" data-config="update-restore">
                            <i class="fas fa-rotate text-[2.5em] text-sky-500"></i>
                            <span class="text-center leading-[0.95] select-none">Update & Restore</span>
                        </div>
                    </div>
                </section>
    
                <section>
                    <h1 class="text-lg text-[rgb(31,139,228)]">Applications</h1>
                    <hr class="border-gray-300 my-1" />
                    <div class="flex flex-wrap items-start w-full gap-2">
                        <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 w-[6.5em]" data-config="privileges">
                            <i class="fas fa-cubes-stacked text-[2.5em] text-gray-500"></i>
                            <span class="text-center leading-[0.95] select-none">Privileges</span>
                        </div>
    
                        <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 w-[6.5em]" data-config="application-portal">
                            <i class="fas fa-arrow-up-right-from-square text-[2.5em] text-blue-500"></i>
                            <span class="text-center leading-[0.95] select-none">Application Portal</span>
                        </div>
    
                        <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 w-[6.5em]" data-config="indexing-service">
                            <i class="fas fa-file-circle-question text-[2.5em] text-green-500"></i>
                            <span class="text-center leading-[0.95] select-none">Indexing Service</span>
                        </div>
    
                        <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 w-[6.5em]" data-config="shared-folder-sync">
                            <i class="fas fa-folder-tree text-[2.5em] text-yellow-500"></i>
                            <span class="text-center leading-[0.95] select-none">Shared Folder Sync</span>
                        </div>
    
                        <div class="inline-flex flex-col shrink cursor-pointer items-center gap-2 p-2 w-[6.5em]" data-config="terminal-snmp">
                            <i class="fas fa-terminal text-[2.5em] text-gray-900"></i>
                            <span class="text-center leading-[0.95] select-none">Terminal & SNMP</span>
                        </div>
                    </div>
                </section>
            </div>
        </div>
        <div class="hidden h-full" id="settings">
            <div class="grid grid-cols-[min-content_1fr] h-full">
                <aside class="w-[225px] h-full flex flex-col gap-2 overflow-hidden">
                    <nav class="flex items-center gap-2 px-3">
                        <button class="btn px-3" id="home-btn"><i class="fas fa-home"></i></button>
                        <div class="flex items-center gap-2 border shadow-inner p-1">
                            <button class="whitespace-nowrap"><i class="fas fa-magnifying-glass w-[1.5rem]"></i></button>
                            <input type="text" class="w-full h-full" placeholder="Search" />
                        </div>
                    </nav>
                    <div class="flex-1 h-full overflow-y-auto px-3" id="sidebar"></div>
                </aside>
                <div class="h-full padding px-3 overflow-y-hidden" id="config">
                    <div class="flex flex-col h-full" data-config="file-services">
                        <nav class="flex py-2 border-b border-gray-400">
                            <div class="relative px-3 cursor-pointer border-r border-gray-300 whitespace-nowrap">SMB/AFP/NFS</div>
                            <div class="relative px-3 cursor-pointer border-x border-gray-300 whitespace-nowrap active-tab">FTP</div>
                            <div class="relative px-3 cursor-pointer border-x border-gray-300 whitespace-nowrap">TFTP</div>
                            <div class="relative px-3 cursor-pointer border-x border-gray-300 whitespace-nowrap">rsync</div>
                            <div class="relative px-3 cursor-pointer border-x border-gray-300 whitespace-nowrap">Advanced</div>
                        </nav>
                        <div class="flex-1 p-2 overflow-y-auto">
                            <h1 class="text-xl text-[rgb(31,139,228)]"><i class="fas fa-chevron-up"></i> FTP / FTPS</h1>
                            <hr class="border-b border-gray-200 mb-2">
                            <div class="grid grid-cols-[min-content_1fr] gap-3">
                                <label class="w-[1.5rem] h-[1.5rem] shadow-inner border border-gray-300 block text-center" for="ftp-enabled">
                                    <input name="ftp-enabled" id="ftp-enabled" class="peer hidden" type="checkbox" checked readonly>
                                    <i class="fas fa-check text-[rgb(31,139,228)] hidden peer-checked:inline"></i>
                                </label>
                                <p>Enable FTP service (No encryption)</p>
                                <!--  -->
                                <label class="w-[1.5rem] h-[1.5rem] shadow-inner border border-gray-300 block text-center" for="ftps-enabled">
                                    <input name="ftps-enabled" id="ftps-enabled" class="peer hidden" type="checkbox" readonly>
                                    <i class="fas fa-check text-[rgb(31,139,228)] hidden peer-checked:inline"></i>
                                </label>
                                <p>Enable FTP SSL/TLS encryption service (FTPS)</p>
                                <!--  -->
                                <span></span>
                                <div class="grid grid-cols-2 items-center gap-1">
                                    <p>Port number setting of FTP</p>
                                    <input class="w-full shadow-inner border border-gray-300 px-2 py-1 bg-white" type="number" value="21">
                                </div>
                                <!--  -->
                                <span></span>
                                <div>
                                    <p class="mb-2">Port range of Passive FTP</p>
                                    <div class="pl-6 grid grid-cols-[min-content_1fr] gap-2">
                                        <label class="w-[1.5rem] h-[1.5rem] shadow-inner border border-gray-300 block text-center rounded-full" for="passive-port-range-default">
                                            <input name="passive-port-range" id="passive-port-range-default" class="peer hidden" type="radio" readonly>
                                            <i class="fas fa-circle text-[rgb(31,139,228)] hidden peer-checked:inline text-[0.85em]"></i>
                                        </label>
                                        <p>Use the default port range (55536-55543)</p>
                                        <!--  -->
                                        <label class="w-[1.5rem] h-[1.5rem] shadow-inner border border-gray-300 block text-center rounded-full" for="passive-port-range-custom">
                                            <input name="passive-port-range" id="passive-port-range-custom" class="peer hidden" type="radio" checked readonly>
                                            <i class="fas fa-circle text-[rgb(31,139,228)] hidden peer-checked:inline text-[0.85em]"></i>
                                        </label>
                                        <div>
                                            Use the following port range:
                                            <br />
                                            From: <input class="w-[8em] shadow-inner border border-gray-300 px-2 py-1 bg-white" type="number" value="2100" readonly> To: <input class="w-[8em] shadow-inner border border-gray-300 px-2 py-1 bg-white" type="number" value="2199" readonly>
                                        </div>
                                    </div>
                                </div>
                                <!--  -->
                                <span></span>
                                <div class="grid grid-cols-[min-content_1fr] gap-3">
                                    <label class="w-[1.5rem] h-[1.5rem] shadow-inner border border-gray-300 block text-center" for="external-ip-pasv">
                                        <input name="external-ip-pasv" id="external-ip-pasv" class="peer hidden" type="checkbox" checked readonly>
                                        <i class="fas fa-check text-[rgb(31,139,228)] hidden peer-checked:inline"></i>
                                    </label>
                                    <p>Report external IP in PASV mode</p>
                                    <!--  -->
                                    <span></span>
                                    <div class="grid grid-cols-2 items-center gap-1">
                                        <p>Assign external IP:</p>
                                        <select class="w-full shadow-inner border border-gray-300 px-2 py-1 bg-gray-100" disabled readonly></select>
                                    </div>
                                    <!--  -->
                                    <label class="w-[1.5rem] h-[1.5rem] shadow-inner border border-gray-300 block text-center" for="fxp">
                                        <input name="fxp" id="fxp" class="peer hidden" type="checkbox" readonly>
                                        <i class="fas fa-check text-[rgb(31,139,228)] hidden peer-checked:inline"></i>
                                    </label>
                                    <p>Enable FXP</p>
                                    <!--  -->
                                    <label class="w-[1.5rem] h-[1.5rem] shadow-inner border border-gray-300 block text-center" for="ascii-mode">
                                        <input name="ascii-mode" id="ascii-mode" class="peer hidden" type="checkbox" readonly>
                                        <i class="fas fa-check text-[rgb(31,139,228)] hidden peer-checked:inline"></i>
                                    </label>
                                    <p>Support ASCII transfer mode</p>
                                    <!--  -->
                                    <span></span>
                                    <div class="grid grid-cols-2">
                                        <p class="-ml-[calc(0.75rem+1.5rem)]">UTF-8 Encoding:</p>
                                        <select class="w-full shadow-inner border border-gray-300 px-2 py-1 bg-gray-100" readonly>
                                            <option value="auto" selected>Auto</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 p-2 border-t border-gray-400">
                            <button class="w-[6em] bg-gradient-to-b from-[#0d92f2] to-[#0086e5] text-white rounded-[3px] px-3 py-1 border border-[#1270B2]">Apply</button>
                            <button class="w-[6em] bg-[rgb(240,244,249)] rounded-[3px] px-3 py-1 border border-[rgb(203,211,221)]">Reset</button>
                        </div>
                    </div>
                    <div class="flex flex-col h-full" data-config="user">
                        <nav class="flex py-2 border-b border-gray-400">
                            <div class="relative px-3 cursor-pointer border-x border-gray-300 whitespace-nowrap active-tab">User</div>
                            <div class="relative px-3 cursor-pointer border-r border-gray-300 whitespace-nowrap">Advanced</div>
                        </nav>
                        <div class="flex-1 p-2 overflow-y-auto flex flex-col gap-2">
                            <div class="flex items-between gap-2">
                                <div class="flex items-center gap-2">
                                    <button class="btn px-3">Create <i class="fas fa-caret-down text-sm"></i></button>
                                    <button class="btn px-3 opacity-50" disabled>Edit</button>
                                    <button class="btn px-3 opacity-50" disabled>Delete</button>
                                </div>

                                <div class="ml-auto flex items-center gap-2 border shadow-inner p-1">
                                    <button class="whitespace-nowrap"><i class="fas fa-filter w-[1.5rem]"></i></button>
                                    <input type="text" class="w-full h-full" placeholder="Search" />
                                </div>
                            </div>
                            <div class="flex-1 overflow-auto">
                                <table class="table-fixed min-w-full">
                                    <thead class="text-[rgb(31,139,228)] select-none border-t-2 border-y border-gray-300">
                                        <tr>
                                            <th class="border-r border-gray-300 px-2 py-1">Username <i class="fas fa-caret-up text-sm"></i></th>
                                            <th class="border-r border-gray-300 px-2 py-1">Email</th>
                                            <th class="border-r border-gray-300 px-2 py-1">Groups</th>
                                            <th class="border-r border-gray-300 px-2 py-1">2FA</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach(parse_etc_passwd() as $username => $user) { ?>
                                        <tr class="border-b border-gray-300 hover:bg-[rgb(0,0,255,0.1)]">
                                            <td class="p-1"><?= $username ?></td>
                                            <td class="p-1 text-center">-</td>
                                            <td class="p-1"><?= implode(user_get_groups($username)) ?></td>
                                            <td class="p-1 text-center"><?= array_key_exists($username, $otp_db) ? 'Enabled' : 'Disabled' ?></td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 p-2 border-t border-gray-400">
                            <button class="w-[6em] bg-gradient-to-b from-[#0d92f2] to-[#0086e5] text-white rounded-[3px] px-3 py-1 border border-[#1270B2]">Apply</button>
                            <button class="w-[6em] bg-[rgb(240,244,249)] rounded-[3px] px-3 py-1 border border-[rgb(203,211,221)]">Reset</button>
                        </div>
                    </div>
                    <div class="flex flex-col h-full" data-config="network">
                        <nav class="flex py-2 border-b border-gray-400">
                            <div class="relative px-3 cursor-pointer border-x border-gray-300 whitespace-nowrap">General</div>
                            <div class="relative px-3 cursor-pointer border-r border-gray-300 whitespace-nowrap active-tab">Interfaces</div>
                            <div class="relative px-3 cursor-pointer border-r border-gray-300 whitespace-nowrap">Traffic Control</div>
                            <div class="relative px-3 cursor-pointer border-r border-gray-300 whitespace-nowrap">Static Route</div>
                            <div class="relative px-3 cursor-pointer border-r border-gray-300 whitespace-nowrap">Connectivity</div>
                        </nav>
                        <div class="flex-1 p-2 overflow-y-auto">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <button class="btn px-3">Create <i class="fas fa-caret-down text-sm"></i></button>
                                    <button class="btn px-3">Edit</button>
                                    <button class="btn px-3 opacity-50" disabled>Delete</button>
                                    <button class="btn px-3 opacity-50" disabled>Connect</button>
                                    <button class="btn px-3">Manage <i class="fas fa-caret-down text-sm"></i></button>
                                </div>
                                <?php foreach(net_get_interfaces() as $interface_name => $interface) {
                                    if ($interface_name === 'lo') continue;
                                ?>
                                <div class="border-y-2 border-gray-400 grid grid-cols-[min-content_1fr_1fr_min-content] gap-4 p-2 items-center">
                                    <i class="fas fa-ethernet text-[2em]<?= ($interface['up'] ? ' text-[rgb(31,139,228)]' : '') ?>"></i>
                                    <div>
                                        <h1 class="text-lg font-bold"><?= $interface_name ?></h1>
                                        <p class="text-[<?= ($interface['up'] ? 'rgb(31,139,228)' : 'gray') ?>]"><?= ($interface['up'] ? 'Connected' : 'Disconnected') ?></p>
                                    </div>
                                    <div>
                                        <p>Static</p>
                                        <p><?= $interface['unicast'][1]['address'] ?></p>
                                    </div>
                                    <i class="fas fa-chevron-down"></i>
                                    <!--  -->
                                    <div ></div>
                                    <div >
                                        <p>Subnet Mask</p>
                                    </div>
                                    <div >
                                        <p><?= $interface['unicast'][1]['netmask'] ?></p>
                                    </div>
                                    <div ></div>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 p-2 border-t border-gray-400">
                            <button class="w-[6em] bg-gradient-to-b from-[#0d92f2] to-[#0086e5] text-white rounded-[3px] px-3 py-1 border border-[#1270B2]">Apply</button>
                            <button class="w-[6em] bg-[rgb(240,244,249)] rounded-[3px] px-3 py-1 border border-[rgb(203,211,221)]">Reset</button>
                        </div>
                    </div>
                    <div class="flex flex-col h-full" data-config="info-center">
                        <nav class="flex py-2 border-b border-gray-400">
                            <div class="relative px-3 cursor-pointer border-x border-gray-300 whitespace-nowrap active-tab" data-tab="general">General</div>
                            <div class="relative px-3 cursor-pointer border-r border-gray-300 whitespace-nowrap">Network</div>
                            <div class="relative px-3 cursor-pointer border-r border-gray-300 whitespace-nowrap">Storage</div>
                            <div class="relative px-3 cursor-pointer border-r border-gray-300 whitespace-nowrap" data-tab="service">Service</div>
                            <div class="relative px-3 cursor-pointer border-r border-gray-300 whitespace-nowrap">Data Collection</div>
                        </nav>
                        <div class="flex-1 p-2 overflow-y-auto tabs">
                            <div class="contents" data-tab="general">
                                <details open>
                                    <summary class="text-[rgb(31,139,228)] font-bold border-y border-gray-300">Basic Information</summary>
                                    <div class="w-full overflow-auto">
                                        <table class="table-fixed min-w-full">
                                            <tbody>
                                                <tr class="border-b border-gray-300"><th class="pl-[.9rem] font-bold text-left p-1">Serial</td> <td class="p-1">10S16SAO0048763</td></tr>
                                                <tr class="border-b border-gray-300"><th class="pl-[.9rem] font-bold text-left p-1">Model</td> <td class="p-1">Elucidator</td></tr>
                                                <tr class="border-b border-gray-300"><th class="pl-[.9rem] font-bold text-left p-1">CPU</td> <td class="p-1">Dark Repulser</td></tr>
                                                <tr class="border-b border-gray-300"><th class="pl-[.9rem] font-bold text-left p-1">CPU Clock Rate</td> <td class="p-1">680 GHz</td></tr>
                                                <tr class="border-b border-gray-300"><th class="pl-[.9rem] font-bold text-left p-1">CPU Cores</td> <td class="p-1">700</td></tr>
                                                <tr class="border-b border-gray-300"><th class="pl-[.9rem] font-bold text-left p-1">Total Physical Memory</td> <td class="p-1">1200 TB</td></tr>
                                                <tr class="border-b border-gray-300"><th class="pl-[.9rem] font-bold text-left p-1">DS Version</td> <td class="p-1"><?= $config['system']['name'] ?></td></tr>
                                                <tr class="border-b border-gray-300"><th class="pl-[.9rem] font-bold text-left p-1">System Time</td> <td class="p-1" id="time"><?= date('Y-m-d H:i:s', time()) ?></td></tr>
                                                <tr class="border-b border-gray-300"><th class="pl-[.9rem] font-bold text-left p-1">Uptime Time</td> <td class="p-1" id="uptime"><?= get_uptime() ?></td></tr>
                                                <tr class="border-b border-gray-300"><th class="pl-[.9rem] font-bold text-left p-1">Thermal Status</td> <td class="p-1 text-green-500"><i class="fas fa-circle"></i> Normal</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </details>
                            </div>
                            <div class="contents hidden" data-tab="service">
                                <div class="h-full w-full overflow-auto"> 
                                    <table class="table-fixed min-w-full">
                                        <thead>
                                            <tr class="border-b border-gray-300">
                                                <th class="text-gray-400 font-normal text-left p-1">Service</th>
                                                <th class="text-gray-400 font-normal text-left p-1">Ports</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="border-b border-gray-300"><td class="p-1">DiskStudio Web</td> <td class="p-1">5000-5001</td></tr>
                                            <tr class="border-b border-gray-300"><td class="p-1">NFS</td> <td class="p-1"> - </td></tr>
                                            <tr class="border-b border-gray-300"><td class="p-1">NTP</td> <td class="p-1"> - </td></tr>
                                            <tr class="border-b border-gray-300"><td class="p-1">SNMP</td> <td class="p-1"> - </td></tr>
                                            <tr class="border-b border-gray-300"><td class="p-1">SSH</td> <td class="p-1"> - </td></tr>
                                            <tr class="border-b border-gray-300"><td class="p-1">FTP</td> <td class="p-1">20,21,2100-2199</td></tr>
                                            <tr class="border-b border-gray-300"><td class="p-1">mDNS</td> <td class="p-1">5353</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const $home = document.getElementById('home');
        const $homeBtn = document.getElementById('home-btn');

        const $controlsEntries = document.getElementById('controls-entries');
        const $settings = document.getElementById('settings');
        const $sidebar = document.getElementById('sidebar');

        const $config = document.getElementById('config');

        // control visibility with css
        (() => {
            const $style = document.createElement('style');
            Array.from($config.children).forEach($entryConfig => {
                const slug = $entryConfig.getAttribute('data-config');
                $style.innerHTML += `#config[data-config="${slug}"] > div:not([data-config="${slug}"]) { display: none; }\n`;
            });
            document.head.appendChild($style);
        })();

        $controlsEntries.querySelectorAll('section').forEach($section => {
            const $details = document.createElement('details');
            $details.classList.add('py-2');
            $details.setAttribute('open', '');
            $details.innerHTML = `<summary class="text-lg text-[rgb(31,139,228)]">${$section.querySelector('h1').textContent}</summary>`;

            $section.querySelectorAll('.inline-flex').forEach($entry => {
                const $icon = $entry.querySelector('i');
                const iconClassName = $icon.className.replace(/text-\[.*(px|em)\]/g, '');
                const name = $entry.querySelector('span').textContent;
                const slug = $entry.getAttribute('data-config');

                $details.innerHTML += `
                    <div class="flex items-center gap-2 p-2 cursor-pointer hover:bg-[rgb(0,0,255,0.1)]" data-config="${slug}">
                        <i class="${iconClassName} text-[1.25em] w-[1.7rem] text-center"></i>
                        <span class="select-none">${name}</span>
                    </div>
                `;
            });

            $sidebar.appendChild($details);
        });

        function showConfig(slug) {
            const $entryConfg = $config.querySelector(`[data-config="${slug}"]`);

            if (!$entryConfg) return false;

            const $entry = $sidebar.querySelector(`.cursor-pointer[data-config="${slug}"]`);

            $sidebar.querySelectorAll('.cursor-pointer').forEach($entry => {
                $entry.classList.remove('bg-[rgba(0,0,255,0.1)]');
            });

            $entry.classList.add('bg-[rgba(0,0,255,0.1)]');

            $config.dataset.config = slug;

            return true;
        }

        $controlsEntries.addEventListener('click', ev => {
            if (!ev.target.matches('.inline-flex, .inline-flex *')) return;
            
            const $entry = ev.target.closest('.inline-flex');
            const slug = $entry.getAttribute('data-config');

            if (!showConfig(slug)) return;

            $home.classList.add('hidden');
            $settings.classList.remove('hidden');

            const $sidebarEntry = $sidebar.querySelector(`.cursor-pointer[data-config="${slug}"]`);
            $sidebar.scrollTop = $sidebarEntry.getBoundingClientRect().top - $sidebar.getBoundingClientRect().top;
        });

        $homeBtn.addEventListener('click', ev => {
            $home.classList.remove('hidden');
            $settings.classList.add('hidden');
        });

        $sidebar.addEventListener('click', ev => {
            if (ev.target.matches('.cursor-pointer, .cursor-pointer *')) {
                const slug = ev.target.closest('.cursor-pointer').getAttribute('data-config');
                showConfig(slug);
            }
        });

        const $infoCenter = $config.querySelector('[data-config="info-center"]');
        const $infoCenterNav = $infoCenter.querySelector('nav');
        $infoCenterNav.addEventListener('click', ev => {
            const $tabs = $infoCenter.querySelector('.tabs');

            if (ev.target.matches('nav > [data-tab]')) {
                const $tabNavEntry = ev.target.closest('[data-tab]');

                $infoCenterNav.querySelectorAll('[data-tab]').forEach($tab => {
                    $tab.classList.toggle('active-tab', $tab.dataset.tab === $tabNavEntry.dataset.tab);
                });

                const tabName = $tabNavEntry.getAttribute('data-tab');

                $tabs.querySelectorAll('[data-tab]').forEach($tab => {
                    $tab.classList.toggle('hidden', $tab.dataset.tab !== tabName);
                });
            }
        });

        const $time = $config.querySelector('#time');
        const $uptime = $config.querySelector('#uptime');

        setInterval(() => {
            const oldTime = new Date($time.textContent + "+0");
            oldTime.setSeconds(oldTime.getSeconds() + 1);
            $time.textContent = oldTime.toISOString().replace('T', ' ').replace(/\.\d+Z/, '');

            
        }, 1000);
    </script>
</body>
</html>