<?php declare(strict_types=1);

/**
 * Development server router for `php -S`.
 *
 * Static files are served directly by PHP's built-in server. All other
 * requests fall through to public/index.php, which boots the application.
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$file = __DIR__ . $uri;

if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

require __DIR__ . '/index.php';
return true;
