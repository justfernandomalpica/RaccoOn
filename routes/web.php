<?php declare(strict_types=1);

use App\Controllers\DemoController;
use App\Routing\Router;

/**
 * Register web routes used by the scaffold.
 *
 * The `$router` instance is created during bootstrap in config/app.php.
 *
 * @var Router $router
 */
$router->get('/', [DemoController::class, 'index']);
