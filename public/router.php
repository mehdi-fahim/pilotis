<?php

/**
 * Router for the PHP built-in web server.
 *
 * Usage: php -S 127.0.0.1:8000 -t public public/router.php
 *
 * Serves existing files under public/ as static assets; everything else
 * is forwarded to the Symfony front controller (AssetMapper included).
 */
if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $filepath = __DIR__.$path;

    if ($path !== '/' && is_file($filepath)) {
        return false;
    }
}

// Ensure the Symfony Runtime loads index.php (not this router) as the app entrypoint.
$_SERVER['SCRIPT_FILENAME'] = __DIR__.\DIRECTORY_SEPARATOR.'index.php';

require __DIR__.'/index.php';
