<?php declare(strict_types=1);

/**
 * Shared filesystem paths used during bootstrap and by small infrastructure
 * classes such as the logger.
 */
define('PROJECT_ROOT', dirname(__DIR__, 2));
define('SRC_PATH', PROJECT_ROOT . DIRECTORY_SEPARATOR . 'src');
define('CONFIG_PATH', PROJECT_ROOT . DIRECTORY_SEPARATOR . 'config');
define('PUBLIC_PATH', PROJECT_ROOT . DIRECTORY_SEPARATOR . 'public');
define('VIEWS_PATH', PROJECT_ROOT . DIRECTORY_SEPARATOR . 'views');
define('STORAGE_PATH', PROJECT_ROOT . DIRECTORY_SEPARATOR . 'storage');
