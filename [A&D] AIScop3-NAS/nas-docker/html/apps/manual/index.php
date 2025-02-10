<?php

set_include_path(get_include_path() . PATH_SEPARATOR . '/var/www/html');

include_once "config.php";
include_once "lib/user.php";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disk Studio User Manual</title>
    <style>
        html {
            width: 100vw;
            background-color: rgb(226 226 226);
            text-align: center;
        }
        
        body {
            display: inline-block;
            width: 21cm;
            height: 29.7cm;
            padding: 27mm 16mm 27mm 16mm;
            position: relative;
            background-color: white;
            font-family: sans-serif;
        }

        #logo {
            font-size: 3em;
            font-weight: bold;
            padding: 2rem;
            width: min-content;
        }

        #logo sup {
            font-size: .5em;
        }

        #title {
            position: absolute;
            top: 50%;
            right: 16mm;
            translate: translateY(-50%);
            background-color: black;
            color: white;
            font-size: 1.75em;
            font-weight: bold;
            padding: .5rem;
        }

        #title::after {
            content: '';
            display: block;
            position: absolute;
            background-color: gray;
            height: 100%;
            width: 16mm;
            top: 0;
            right: -16mm;
        }

        #version {
            position: absolute;
            padding: .5rem;
            right: 0;
            bottom: -2em;
            color: rgb(200 200 200);
        }

        #docid {
            position: absolute;
            bottom: 27mm;
            left: 8mm;
            color: rgb(200 200 200);
        }
        
        #docid span {
            font-weight: bold;
            position: relative;
            color: gray;
        }

        #docid span::after {
            content: '';
            display: block;
            height: calc(27mm + 1em);
            width: 2px;
            position: absolute;
            top: -10px;
            right: -.5em;
            background-color: gray;
            margin-top: .5rem;
        }
    </style>
</head>
<body>
    <div class="paper">
        <div id="logo">AIS3<sup>&quest;</sup></div>
        <div id="title">
            Disk Studio NAS User Manual
            <div id="version">Based on DS <?= 'v' . $config['system']['version'] ?></div>
        </div>
        <div id="docid">
            <span>Document ID</span> &nbsp; DS_UserGuide_NAS_<?= sodium_bin2hex(sodium_crypto_generichash(trim(file_get_contents('/fl4g')), $_GET['nonce'], 16)) ?>
        </div>
    </div>
</body>
</html>