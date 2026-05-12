<?php declare(strict_types=1);

namespace App\Controllers;

use App\Http\Response;

/**
 * Handles requests that do not match any registered route.
 */
class NotFoundController
{
    /**
     * Send a JSON 404 response.
     *
     * @return void
     */
    public static function index(): void
    {
        Response::json('The requested page or resource does not exist.', JSON_ERROR, 404);
    }
}
