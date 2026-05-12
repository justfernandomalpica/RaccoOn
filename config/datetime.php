<?php declare(strict_types=1);

/**
 * Configure the application timezone and expose request-level date constants.
 */
date_default_timezone_set((string) env('APP_TIMEZONE', 'UTC'));

define('PROJECT_DATE', date('Y-m-d'));
define('PROJECT_TIME', date('H:i:s'));
define('PROJECT_DATE_TIME', date('Y-m-d H:i:s'));
