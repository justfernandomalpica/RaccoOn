<?php declare(strict_types=1);

namespace App\Rendering;

/**
 * Renders views and partials into HTML strings.
 */
final class Render
{
    /**
     * Prevent instances; rendering is exposed as a static application helper.
     */
    private function __construct()
    {
    }

    /**
     * Render a page view inside a layout.
     *
     * The view HTML is exposed to the layout as $content. View data is exposed
     * to both templates as variables prefixed with $data_.
     *
     * @param string $layout Relative layout path inside views/layouts.
     * @param View $view Page view reference.
     * @return string
     */
    public static function view(string $layout, View $view): string
    {
        $viewData = $view->getData();
        $content = self::renderFile(self::resolveTemplate(PAGES_PATH, $view->getPath(), 'View'), $viewData);

        return self::renderFile(
            self::resolveTemplate(LAYOUTS_PATH, self::normalizePath($layout, 'Layout'), 'Layout'),
            array_merge($viewData, ['content' => $content])
        );
    }

    /**
     * Render a partial template without a layout.
     *
     * Partial data is exposed as variables prefixed with $data_.
     *
     * @param Partial $partial Partial template reference.
     * @return string
     */
    public static function partial(Partial $partial): string
    {
        return self::renderFile(
            self::resolveTemplate(PARTIALS_PATH, $partial->getPath(), 'Partial'),
            $partial->getData()
        );
    }

    /**
     * Render a PHP template file into a string.
     *
     * @param string $file Absolute template file path.
     * @param array<string, mixed> $data Template data.
     * @return string
     */
    private static function renderFile(string $file, array $data): string
    {
        if ($data !== []) {
            extract($data, EXTR_PREFIX_ALL, 'data');
        }

        if (array_key_exists('content', $data)) {
            $content = $data['content'];
        }

        ob_start();

        try {
            include $file;
            $html = ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        return $html === false ? '' : $html;
    }

    private static function resolveTemplate(string $basePath, string $path, string $type): string
    {
        $file = $basePath . DIRECTORY_SEPARATOR . $path . '.php';

        if (!is_file($file)) {
            throw new \RuntimeException("{$type} template does not exist: [{$path}].");
        }

        return $file;
    }

    private static function normalizePath(string $path, string $type): string
    {
        $baseErrorMsg = "Error on setting {$type} path: ";
        $path = trim($path);

        if ($path === '') {
            throw new \InvalidArgumentException($baseErrorMsg . 'Path cannot be empty.');
        }

        if (str_ends_with($path, '.php')) {
            $path = substr($path, 0, -4);
        }

        if ($path === '') {
            throw new \InvalidArgumentException($baseErrorMsg . 'Path cannot be empty.');
        }

        if (preg_match('#(^|[\\\\/])\.\.([\\\\/]|$)#', $path) === 1) {
            throw new \InvalidArgumentException($baseErrorMsg . 'Path cannot contain parent directory segments.');
        }

        if (
            str_starts_with($path, DIRECTORY_SEPARATOR)
            || str_starts_with($path, '/')
            || preg_match('#^[A-Za-z]:[\\\\/]#', $path) === 1
        ) {
            throw new \InvalidArgumentException($baseErrorMsg . 'Path must be relative.');
        }

        if (str_ends_with($path, '/') || str_ends_with($path, '\\')) {
            throw new \InvalidArgumentException($baseErrorMsg . 'Path cannot end with a directory separator.');
        }

        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }
}
