<?php

include "config.php";
include "lib/otp.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['username'])) {
        http_response_code(400);
        die();
    }

    if (!isset($otp_db[$_SESSION['username']]) || $otp_db[$_SESSION['username']] == '') {
        $_SESSION['TWOFA'] = TWOFA_AUTH;
    }

    if ($_SESSION['TWOFA'] == TWOFA_AUTH) {
        http_response_code(204);
        die();
    }
    
    $twofa_code = isset($_POST['twofa']) ? $_POST['twofa'] : '';
    
    if ($twofa_code == '') {
        http_response_code(400);
        die();
    }

    if (otp_verify($otp_db[$_SESSION['username']], $twofa_code)) {
        $_SESSION['TWOFA'] = TWOFA_AUTH;
        http_response_code(204);
        die();
    } else {
        $_SESSION['TWOFA'] = TWOFA_FAILED;
        http_response_code(401);
        die();
    }
} else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!isset($_SESSION['username'])) {
        header('Location: login.php');
        die();
    }

    if (!isset($otp_db[$_SESSION['username']]) || $otp_db[$_SESSION['username']] == '') {
        $_SESSION['TWOFA'] = TWOFA_AUTH;
    }

    if ($_SESSION['TWOFA'] == TWOFA_AUTH) {
        header('Location: index.php');
        die();
    }
} else {
    http_response_code(405);
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
    </style>
</head>

<body>
    <div id="app" class="flex justify-center items-center h-screen bg-gradient-to-b from-[#106cbf] from-0% via-[#1182d2] via-35% to-[#125ad3] to-100%">
        <form action="2fa.php" method="POST" id="twofa">
            <div class="w-[360px] flex flex-col gap-4 pb-[4em] relative z-10">
                <h1 class="text-3xl text-white font-bold text-center mb-4">AISCOP3</h1>
                <div class="bg-[rgba(255,255,255,0.3)] shadow-md">
                    <div class="px-6 py-3 border-b border-gray-300 flex items-center gap-2">
                        <i class="fas fa-mobile-screen text-2xl text-white text-shadow"></i>
                        <input type="text" name="twofa" id="twofa-code" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                            class="mt-1 block w-full px-3 py-2 bg-transparent border-none text-2xl text-white text-shadow placeholder:text-gray-600 focus:outline-none focus:ring-0" placeholder="6 digit 2FA code" maxlength="6" minlength="6" pattern="^\d{6}$" required>
                    </div>
                    <div class="p-6">
                        <button type="submit" id="login-button"
                            class="w-full bg-gradient-to-b from-[#0d92f2] to-[#0086e5] text-white font-bold rounded-[3px] px-4 py-2 border border-[#1270B2] mb-4">Login</button>
                        <a class="text-white underline" href="javascript:alert('So Sad.')">Missing phone?</a>
                    </div>
                </div>
                <div class="bg-[rgba(0,0,0,0.2)] p-4 text-white text-shadow shadow-md opacity-0 select-none" id="message">
                    Logging in...
                </div>
            </div>
        </form>
        <i class="fas fa-angles-right fixed right-0 top-1/2 h-full transform translate-x-[15%] -translate-y-[70%] text-[100vh] leading-[100vh] text-white opacity-30 select-none"></i>
        <i class="fas fa-angles-right fixed right-0 top-1/2 h-full transform translate-x-[30%] -translate-y-[35%] text-[100vh] leading-[100vh] text-white opacity-20 select-none"></i>
        <div class="fixed right-[20px] bottom-[20px] text-[rgba(255,255,255,0.6)] font-bold select-none">
            &copy; AIS3 DS v<?= $config['system']['version'] ?>
        </div>
    </div>

    <script>
        document.querySelector('#twofa').addEventListener('submit', function (ev) {
            let $message = document.querySelector('#message');
            let $loginButton = document.querySelector('#login-button');

            ev.preventDefault();
            if ($loginButton.disabled) return;

            $message.classList.remove('bg-[rgba(229,69,69,0.6)]');
            $message.innerText = 'Logging in...';
            $message.style.opacity = 1;

            $loginButton.classList.add('cursor-not-allowed');
            $loginButton.classList.add('opacity-75');
            $loginButton.disabled = true;

            setTimeout(async () => {
                try {
                    let response = await fetch('2fa.php', {
                        method: 'POST',
                        body: new FormData(document.querySelector('#twofa'))
                    });

                    if (response.ok) {
                        location.href = 'index.php';
                        return;
                    }
                } catch (error) {
                    console.error(error);
                }

                $message.classList.add('bg-[rgba(229,69,69,0.6)]');
                $message.innerText = 'Login failed. Try again.';
    
                $loginButton.classList.remove('cursor-not-allowed');
                $loginButton.classList.remove('opacity-75');
                $loginButton.disabled = false;
            }, 2000);
        });
    </script>
</body>

</html>