<?php declare(strict_types=1);

namespace App\Rendering;

/**
 * Represents a page view template and the data passed to it.
 */
class View
{
    private string $path;

    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * Create a view reference.
     *
     * @param string $path Relative path inside views/pages, without .php.
     */
    public function __construct(string $path)
    {
        $this->path = $this->validatePath($path);
    }

    /**
     * Merge template data into the view.
     *
     * Data is exposed to page templates as variables prefixed with $data_.
     *
     * @param array<string, mixed> $args Associative template data.
     * @return self
     */
    public function data(array $args): self
    {
        $validated = $this->validateAssocArray($args);

        foreach ($validated as $key => $value) {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * Return the normalized relative template path.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Return the data assigned to this view.
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    private function validatePath(string $path): string
    {
        $baseErrorMsg = 'Error on setting View path: ';
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

    private function validateAssocArray(array $array): array
    {
        $baseErrorMsg = 'Error while validating view data format: ';

        if ($array === []) {
            throw new \InvalidArgumentException($baseErrorMsg . 'Array cannot be empty.');
        }

        if (array_is_list($array)) {
            throw new \InvalidArgumentException($baseErrorMsg . 'Array must be associative.');
        }

        foreach (array_keys($array) as $key) {
            if (!is_string($key)) {
                throw new \InvalidArgumentException($baseErrorMsg . "All keys must be strings. Key: [{$key}].");
            }
        }

        return $array;
    }
}
