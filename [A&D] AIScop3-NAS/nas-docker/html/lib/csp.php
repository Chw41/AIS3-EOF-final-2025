<?php

function csp_generate_nonce() {
    $bytes = random_bytes(16);
    $nonce = bin2hex($bytes);
    return $nonce;
}

function csp_generate_header($nonce) {
    $policies = array(
        "default-src" => array("'self'"),
        "script-src" => array("'self'", "'nonce-$nonce'", "https://cdn.jsdelivr.net/", "https://cdn.tailwindcss.com/"),
        "style-src" => array("'self'", "'unsafe-inline'", "https://cdn.jsdelivr.net/", "https://fonts.googleapis.com/"),
        "font-src" => array("'self'", "https://cdn.jsdelivr.net/", "https://fonts.gstatic.com/"),
        "img-src" => array("'self'", "data:"),
        "connect-src" => array("'self'"),
        "frame-src" => array("'self'"),
        "base-uri" => array("'none'")
    );

    $header = "Content-Security-Policy:";
    foreach ($policies as $key => $value) {
        $header .= " $key " . implode(" ", $value) . ";";
    }

    return $header;
}

?>