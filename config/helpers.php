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
 * Send a JSON response and finish the current request.
 *
 * @param array $data Response payload.
 * @param int $status HTTP status code.
 * @return void
 */
function jsonRes(array $data, int $status = 200): void
{
    $jsonSettings = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, $jsonSettings);
    exit;
}

/**
 * Send an HTML response and finish the current request.
 *
 * @param string $html Rendered HTML content.
 * @param int $status HTTP status code.
 * @return void
 */
function htmlRes(string $html, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
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
