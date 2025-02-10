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
}

$otp_token = $otp_db[$_SESSION['username']];

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
            .active-tab {
                @apply text-[rgb(31,139,228)] after:block after:content-[''] after:w-[10px] after:h-[10px] after:absolute after:bottom-0 after:left-1/2 after:bg-white after:transform after:-translate-x-1/2 after:translate-y-[calc(100%+0.25rem)] after:rotate-[-45deg] after:border-t after:border-r after:border-gray-400;
            }
        }
    </style>
</head>
<body>
    <div id="app" class="flex flex-col h-screen gap-2 px-3 relative">
        <nav class="flex py-2 border-b border-gray-400" id="menu">
            <div class="relative px-3 cursor-pointer border-x border-gray-300 whitespace-nowrap active-tab">Account</div>
            <div class="relative px-3 cursor-pointer border-r border-gray-300 whitespace-nowrap">Account Protection</div>
            <div class="relative px-3 cursor-pointer border-r border-gray-300 whitespace-nowrap">Quota</div>
            <div class="relative px-3 cursor-pointer border-r border-gray-300 whitespace-nowrap">Desktop</div>
            <div class="relative px-3 cursor-pointer border-r border-gray-300 whitespace-nowrap">Others</div>
        </nav>
        <div class="flex-1 p-2 overflow-y-auto">
            <div id="account-tab">
                <div class="grid grid-cols-2 w-[32em] gap-2 items-center">
                    <div>Name: </div> <p><?= $_SESSION['username'] ?></p>
                    <div>Description: </div> <input class="w-full shadow-inner border border-gray-300 px-2 py-1 bg-white" type="text">
                    <div>Password: </div> <input class="w-full shadow-inner border border-gray-300 px-2 py-1 bg-white" type="password">
                    <div>New Password: </div> <input class="w-full shadow-inner border border-gray-300 px-2 py-1 bg-white" type="password">
                    <div>Email: </div> <input class="w-full shadow-inner border border-gray-300 px-2 py-1 bg-white" type="email">
                    <div>Display Language: </div> <select class="w-full shadow-inner border border-gray-300 p-2 bg-white"><option value="en">English</option></select>
                </div>
                <div class="grid grid-cols-[min-content_1fr] gap-3 items-start my-2">
                    <label class="w-[1.5rem] h-[1.5rem] shadow-inner border border-gray-300 block text-center" for="twofa-enabled">
                        <input name="twofa-enabled" id="twofa-enabled" class="peer hidden" type="checkbox"<?php if ($otp_token != '') echo ' checked' ?> readonly>
                        <i class="fas fa-check text-[rgb(31,139,228)] hidden peer-checked:inline"></i>
                    </label>
                    <div>
                        <p class="mb-2">Enable 2FA</p>
                        <button class="bg-[rgb(240,244,249)] rounded-[3px] px-3 py-1 border border-[rgb(203,211,221)] opacity-50" disabled id="twofa-btn">2-Step Authentication</button>
                    </div>
                </div>
                <p class="my-2">
                    View your account activity, including current connections, trusted devices, and login history.
                </p>
                <button class="bg-[rgb(240,244,249)] rounded-[3px] px-3 py-1 border border-[rgb(203,211,221)]">Account Activity</button>
            </div>
        </div>
        <div class="flex justify-end gap-2 p-2 border-t border-gray-400">
            <button class="w-[6em] bg-gradient-to-b from-[#0d92f2] to-[#0086e5] text-white rounded-[3px] px-3 py-1 border border-[#1270B2]">Ok</button>
            <button class="w-[6em] bg-[rgb(240,244,249)] rounded-[3px] px-3 py-1 border border-[rgb(203,211,221)]" id="settings-cancel-btn">Cancel</button>
        </div>
        <div class="absolute w-full h-full top-0 left-0 bg-[rgb(255,255,255,0.5)] flex items-center justify-center hidden" id="twofa-qrcode-modal">
            <div class="w-[80%] max-w-[30em] max-h-[90%] bg-white shadow-md p-3 overflow-y-auto">
                <h1 class="font-bold text-3xl">Setup 2-Step Authentication with OTP</h1>
                <br />
                <ol class="list-decimal pl-5">
                    <li>Download and install an OTP app (ex. Google Authenticator) on your phone.</li>
                    <li>Scan the QR code below with the OTP app.</li>
                    <li>Enter the 6-digit code generated by the OTP app when login.</li>
                </ol>
                <div class="flex justify-center my-2">
                    <canvas id="twofa-qrcode"></canvas>
                </div>
                <hr class="my-2 border border-gray-500" />
                <div class="text-end">
                    <button class="w-[6em] bg-gradient-to-b from-[#0d92f2] to-[#0086e5] text-white rounded-[3px] px-3 py-1 border border-[#1270B2]">Ok</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        const $twofaEnabled = document.getElementById('twofa-enabled');
        const $twofaBtn = document.getElementById('twofa-btn');

        if ($twofaEnabled.checked) {
            $twofaBtn.classList.remove('opacity-50');
            $twofaBtn.removeAttribute('disabled');
        }

        const $cancelBtn = document.getElementById('settings-cancel-btn');

        $cancelBtn.addEventListener('click', function() {
            parent.postMessage({ type: 'close' }, '*');
        });
    </script>
    <?php if ($otp_token != '') { ?>
    <script type="module">
        import qrcode from 'https://cdn.jsdelivr.net/npm/qrcode@1.5.4/+esm';

        const $twofaQRCodeModal = document.getElementById('twofa-qrcode-modal');
        const $twofaQRCode = document.getElementById('twofa-qrcode');

        qrcode.toCanvas($twofaQRCode, '<?= otp_generate_url($otp_token, urlencode($config['system']['name']) . ":" . $_SESSION['username']) ?>', function(error) {
            if (error) {
                console.error(error);
            }
        });

        $twofaBtn.addEventListener('click', function() {
            $twofaQRCodeModal.classList.toggle('hidden');
        });

        $twofaQRCodeModal.addEventListener('click', function(event) {
            if (event.target === this) {
                this.classList.add('hidden');
            }
        });

        $twofaQRCodeModal.querySelector('button').addEventListener('click', function() {
            $twofaQRCodeModal.classList.add('hidden');
        });
    </script>
    <?php } ?>
</body>
</html>