<?php

/**
 * Router for the PHP built-in web server.
 *
 * Usage: php -S 127.0.0.1:8000 -t public public/router.php
 */
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    $filepath = __DIR__.$path;

    if ($path !== '/' && is_file($filepath)) {
        return false;
    }
}

require_once __DIR__.'/index.php';
