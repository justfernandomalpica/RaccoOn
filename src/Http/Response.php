<?php declare(strict_types=1);

namespace App\Http;

/**
 * Small JSON response helper for controller endpoints.
 */
class Response
{
    /**
     * Send a successful JSON response.
     *
     * @param mixed $data Payload placed under the `data` key.
     * @param int $status HTTP status code.
     * @return void
     */
    public static function success(mixed $data, int $status = 200): void
    {
        self::send([
            'success' => true,
            'data' => $data,
            'error' => null,
        ], $status);
    }

    /**
     * Send an error JSON response.
     *
     * @param mixed $message Error message or payload placed under the `error` key.
     * @param int $status HTTP status code.
     * @return void
     */
    public static function error(mixed $message, int $status = 400): void
    {
        self::send([
            'success' => false,
            'data' => null,
            'error' => $message,
        ], $status);
    }

    /**
     * Emit the JSON body, set headers and stop execution.
     *
     * @param array $body Normalized response body.
     * @param int $status HTTP status code.
     * @return void
     */
    private static function send(array $body, int $status): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
