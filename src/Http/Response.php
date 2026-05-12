<?php declare(strict_types=1);

namespace App\Http;

/**
 * Small response helper for controller endpoints.
 */
class Response
{
    private const JSON_OPTIONS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    /**
     * Send a plain HTTP response and finish the current request.
     *
     * @param string $content Response content.
     * @param int $status HTTP status code.
     * @param string $contentType Content-Type header value.
     * @return void
     */
    public static function http(string $content, int $status = 200, string $contentType = 'text/html; charset=utf-8'): void
    {
        http_response_code($status);
        header('Content-Type: ' . $contentType);
        echo $content;
        exit;
    }

    /**
     * Send a formatted JSON response and finish the current request.
     *
     * @param mixed $data Payload used by the selected response format.
     * @param string $type JSON response format identifier.
     * @param int $status HTTP status code.
     * @return void
     */
    public static function json(mixed $data, string $type = JSON_SUCCESS, int $status = 200): void
    {
        self::http(self::encodeJson(self::formatJson($data, $type)), $status, 'application/json; charset=utf-8');
    }

    /**
     * Build the normalized JSON body for the requested format.
     *
     * @param mixed $data Payload used by the selected response format.
     * @param string $type JSON response format identifier.
     * @return array<string, mixed>
     */
    private static function formatJson(mixed $data, string $type): array
    {
        return match ($type) {
            JSON_ERROR => [
                'success' => false,
                'data' => null,
                'error' => $data,
            ],
            JSON_WARNING => [
                'success' => true,
                'data' => $data,
                'warning' => true,
                'error' => null,
            ],
            default => [
                'success' => true,
                'data' => $data,
                'error' => null,
            ],
        };
    }

    /**
     * Encode a JSON response body.
     *
     * @param array<string, mixed> $body Normalized response body.
     * @return string
     */
    private static function encodeJson(array $body): string
    {
        $json = json_encode($body, self::JSON_OPTIONS);

        return $json === false ? '' : $json;
    }
}
