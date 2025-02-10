<?php

include "config.php";

$_SESSION['username'] = NULL;
$_SESSION['TWOFA'] = NULL;

session_destroy();
header('Location: login.php');

?>