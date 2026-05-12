<?php declare(strict_types=1);

use App\Controllers\DemoController;
use App\Routing\Router;

/**
 * Register API routes used by the scaffold.
 *
 * The `$router` instance is created during bootstrap in config/app.php and is
 * intentionally kept simple so routes can be edited or removed quickly.
 *
 * @var Router $router
 */
$router->get('/api/health', [DemoController::class, 'health']);
