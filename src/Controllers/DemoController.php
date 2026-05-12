<?php declare(strict_types=1);

namespace App\Controllers;

use App\Http\Response;
use App\Rendering\Partial;
use App\Rendering\Render;
use App\Rendering\View;

/**
 * Provides the removable demo endpoints included with the scaffold.
 */
class DemoController
{
    /**
     * Render the default welcome page used to confirm the scaffold is running.
     *
     * @return void
     */
    public static function index(): void
    {
        $status = Render::partial(
            (new Partial('status'))->data([
                'message' => 'Render activo',
            ])
        );

        $view = (new View('welcome'))->data([
            'title' => 'PHP MVC Scaffold',
            'status' => $status,
        ]);

        Response::html(Render::view(layout: 'main', view: $view));
    }

    /**
     * Return a JSON health check response for local smoke testing.
     *
     * @return void
     */
    public static function health(): void
    {
        Response::json([
            'message' => 'Application is running',
            'environment' => env('APP_ENV', 'local'),
            'timestamp' => PROJECT_DATE_TIME,
        ], JSON_SUCCESS);
    }
}
