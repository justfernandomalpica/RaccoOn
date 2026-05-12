<?php declare(strict_types=1);

namespace App\Controllers;

use App\Http\Response;

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
        htmlRes(<<<HTML
            <!doctype html>
            <html lang="es">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>PHP MVC Scaffold</title>
                <style>
                    body {
                        margin: 0;
                        font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                        color: #1f2937;
                        background: #f8fafc;
                    }

                    main {
                        max-width: 720px;
                        margin: 12vh auto;
                        padding: 0 24px;
                    }

                    a {
                        color: #0f766e;
                    }
                </style>
            </head>
            <body>
                <main>
                    <h1>Scaffold activo</h1>
                    <p>Esta pagina es una ruta demo. Puedes eliminarla junto con su ruta sin afectar el core.</p>
                    <p><a href="/api/health">Ver health check</a></p>
                </main>
            </body>
            </html>
        HTML);
    }

    /**
     * Return a JSON health check response for local smoke testing.
     *
     * @return void
     */
    public static function health(): void
    {
        Response::success([
            'message' => 'Application is running',
            'environment' => env('APP_ENV', 'local'),
            'timestamp' => PROJECT_DATE_TIME,
        ]);
    }
}
