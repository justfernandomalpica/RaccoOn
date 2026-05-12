<?php declare(strict_types=1);

/**
 * Initialize a fresh RaccoOn project after cloning the scaffold.
 *
 * Usage:
 *   php scripts/init-project.php website-cv
 *   php scripts/init-project.php website-cv --vendor=acme
 */

$root = dirname(__DIR__);
$arguments = array_slice($argv, 1);

if ($arguments === [] || in_array($arguments[0], ['-h', '--help'], true)) {
    printUsage();
    exit($arguments === [] ? 1 : 0);
}

$projectName = array_shift($arguments);
$vendor = 'app';

foreach ($arguments as $argument) {
    if (str_starts_with($argument, '--vendor=')) {
        $vendor = substr($argument, strlen('--vendor='));
        continue;
    }

    fail("Unknown option: {$argument}");
}

$packageName = normalizePackageName($projectName);
$vendorName = normalizePackageName($vendor);

if ($packageName === '' || $vendorName === '') {
    fail('Project name and vendor must contain at least one letter or number.');
}

$composerName = "{$vendorName}/{$packageName}";

updateJsonFile($root . '/composer.json', static function (array $json) use ($composerName): array {
    $json['name'] = $composerName;

    return $json;
});

updateJsonFile($root . '/package.json', static function (array $json) use ($packageName): array {
    $json['name'] = $packageName;

    return $json;
});

updateJsonFile($root . '/package-lock.json', static function (array $json) use ($packageName): array {
    $json['name'] = $packageName;

    if (isset($json['packages']['']) && is_array($json['packages'][''])) {
        $json['packages']['']['name'] = $packageName;
    }

    return $json;
});

$envPath = $root . '/.env';
$envExamplePath = $root . '/.env.example';

if (!is_file($envPath) && is_file($envExamplePath)) {
    if (!copy($envExamplePath, $envPath)) {
        fail('Could not create .env from .env.example.');
    }

    line('Created .env from .env.example.');
}

line("Updated Composer package name to {$composerName}.");
line("Updated npm package name to {$packageName}.");
line('Next, refresh dependency metadata if needed:');
line('  composer update --lock');
line('  npm install --package-lock-only');

function updateJsonFile(string $path, callable $mutate): void
{
    if (!is_file($path)) {
        fail("Missing file: {$path}");
    }

    $contents = file_get_contents($path);

    if ($contents === false) {
        fail("Could not read file: {$path}");
    }

    $json = json_decode($contents, true);

    if (!is_array($json)) {
        fail("Invalid JSON file: {$path}");
    }

    $json = $mutate($json);
    $encoded = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if ($encoded === false) {
        fail("Could not encode JSON file: {$path}");
    }

    if (file_put_contents($path, $encoded . PHP_EOL) === false) {
        fail("Could not write file: {$path}");
    }
}

function normalizePackageName(string $name): string
{
    $name = strtolower(trim($name));
    $name = preg_replace('/[^a-z0-9._-]+/', '-', $name) ?? '';
    $name = preg_replace('/^[^a-z0-9]+|[^a-z0-9]+$/', '', $name) ?? '';
    $name = preg_replace('/[-_.]{2,}/', '-', $name) ?? '';

    return $name;
}

function printUsage(): void
{
    line('Usage: php scripts/init-project.php <project-name> [--vendor=vendor-name]');
    line('Example: php scripts/init-project.php website-cv --vendor=acme');
}

function line(string $message): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}

function fail(string $message): never
{
    fwrite(STDERR, 'Error: ' . $message . PHP_EOL);
    exit(1);
}
