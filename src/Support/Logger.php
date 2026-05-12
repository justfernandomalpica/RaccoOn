<?php declare(strict_types=1);

namespace App\Support;

/**
 * Minimal file logger used by the scaffold for local runtime diagnostics.
 */
class Logger
{
    protected static string $streamPath;
    protected static string $streamName;
    protected static ?string $streamFile = null;

    /**
     * Configure the directory and file name used for log entries.
     *
     * @param string $path Path relative to PROJECT_ROOT.
     * @param string $name Optional suffix for the daily log file.
     * @return void
     * @throws \RuntimeException When the stream directory or file cannot be prepared.
     */
    public static function setStream(string $path, string $name = ''): void
    {
        self::$streamPath = PROJECT_ROOT . DIRECTORY_SEPARATOR . self::normalizePath($path);
        self::$streamName = self::createName($name);

        if (!is_dir(self::$streamPath) && !mkdir(self::$streamPath, 0775, true) && !is_dir(self::$streamPath)) {
            throw new \RuntimeException('An error occurred while setting the log stream.');
        }

        if (!is_writable(self::$streamPath)) {
            throw new \RuntimeException('Log stream path is not writable.');
        }

        self::$streamFile = self::$streamPath . self::$streamName;

        if (!file_exists(self::$streamFile)) {
            self::entry('Log stream started');
        }
    }

    /**
     * Append a message to the configured log stream.
     *
     * @param string $message Main log message.
     * @param string $data Optional additional context.
     * @return void
     * @throws \RuntimeException When the stream is not configured or the message is empty.
     */
    public static function entry(string $message, string $data = ''): void
    {
        if (self::$streamFile === null) {
            throw new \RuntimeException('Log stream is not set.');
        }

        $message = trim($message);

        if ($message === '') {
            throw new \RuntimeException('Log entry message cannot be empty.');
        }

        $timeMark = '[' . PROJECT_DATE_TIME . ']';
        $entryLine = $timeMark . ' ' . $message . '.' . (($data !== '') ? ' ' . $data : '') . PHP_EOL;

        $written = @file_put_contents(self::$streamFile, $entryLine, FILE_APPEND);

        if ($written === false) {
            throw new \RuntimeException('Log entry could not be written.');
        }
    }

    private static function normalizePath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            throw new \InvalidArgumentException('Log stream path cannot be empty.');
        }

        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        return rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    private static function createName(string $name = ''): string
    {
        $name = trim($name);
        $baseName = ($name !== '') ? PROJECT_DATE . '_' . $name : PROJECT_DATE;

        return $baseName . '.log';
    }
}
