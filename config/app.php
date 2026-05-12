<?php declare(strict_types=1);

use App\Database\ActiveRecord;
use App\Routing\Router;
use App\Support\Logger;
use Dotenv\Dotenv;

/**
 * Application bootstrap.
 *
 * Loads Composer, helpers, environment variables, optional services and the
 * router instance used by public/index.php.
 */
require realpath(__DIR__ . '/constants.php');
require realpath(PROJECT_ROOT . '/vendor/autoload.php');
require realpath(__DIR__ . '/helpers.php');

$dotenv = Dotenv::createImmutable(PROJECT_ROOT);
$dotenv->safeLoad();

require realpath(__DIR__ . '/datetime.php');

if (env('APP_LOG_ENABLED', true)) {
    try {
        Logger::setStream('storage/logs');
    } catch (RuntimeException) {
        if (env('APP_DEBUG', false)) {
            error_log('Logger could not be initialized.');
        }
    }
}

if (env('APP_USE_DATABASE', false)) {
    require realpath(__DIR__ . '/database.php');
    ActiveRecord::setDB($db);
}

$router = new Router();

if (env('APP_CORS_ENABLED', false)) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}

header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}
