<?php declare(strict_types=1);

/**
 * Read an environment variable and normalize common scalar values.
 *
 * @param string $key Environment variable name.
 * @param mixed $default Value returned when the variable is empty or missing.
 * @return mixed
 */
function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    if (is_string($value)) {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => $value,
        };
    }

    return $value;
}

/**
 * Print a formatted variable dump for quick local debugging.
 *
 * @param mixed $var Value to inspect.
 * @param bool $kill Whether execution should stop after dumping.
 * @return void
 */
function debug(mixed $var, bool $kill = true): void
{
    echo '<pre>';
    var_dump($var);
    echo '</pre>';

    if ($kill) {
        exit;
    }
}

/**
 * Start the PHP session only when it is not already active.
 *
 * @return void
 */
function start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}
