<?php declare(strict_types=1);

require dirname(__DIR__) . '/config/app.php';

require PROJECT_ROOT . '/routes/web.php';
require PROJECT_ROOT . '/routes/api.php';

$router->dispatch();
