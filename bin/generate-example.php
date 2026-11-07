<?php

declare(strict_types=1);

/*
 * Generates the music-catalog example's client, which is not committed.
 *
 * It reads the music catalog's OpenAPI document from `@haddowg/json-api-symfony-specs`, the
 * npm package the bundle publishes on GitHub Packages and package.json pins (`npm install`),
 * copies it to `examples/music-catalog/openapi.json` for ExampleServer, and writes
 * `examples/music-catalog/generated` with json-api-client-codegen, which it installs into
 * tools/codegen first.
 *
 * To generate from documents the bundle exported locally (`php bin/export-specs.php` in a
 * json-api-symfony checkout), point MUSIC_CATALOG_SPECS at that directory:
 *
 *     MUSIC_CATALOG_SPECS=../json-api-symfony/build/specs composer generate-example
 *
 * Usage: composer generate-example. It takes no arguments, because Composer hands a calling
 * script's arguments (`composer test -- --filter …`) to every script it runs.
 */

$root = \dirname(__DIR__);
$example = $root . '/examples/music-catalog';
$override = \getenv('MUSIC_CATALOG_SPECS');
$specs = \is_string($override) && $override !== ''
    ? $override
    : $root . '/node_modules/@haddowg/json-api-symfony-specs';
$document = $specs . '/music-catalog.openapi.json';

if (!\is_file($document)) {
    \fwrite(\STDERR, \is_string($override) && $override !== ''
        ? "MUSIC_CATALOG_SPECS names {$specs}, which has no music-catalog.openapi.json.\n"
        : "No {$document}. Run `npm install` first: it reads @haddowg/json-api-symfony-specs from GitHub Packages,\n"
            . "which needs NODE_AUTH_TOKEN set to a GitHub token with read:packages. Or set MUSIC_CATALOG_SPECS\n"
            . "to a directory the bundle's bin/export-specs.php wrote.\n");

    exit(1);
}

// The codegen is installed in a Composer project of its own, tools/codegen, rather than as a
// require-dev here: this package's tests cover what happens when the codegen is not installed.
// It is (re)installed whenever tools/codegen/composer.json changes, such as a new pin.
$tools = $root . '/tools/codegen';
$codegen = $tools . '/vendor/bin/json-api-client-codegen';
$stamp = $tools . '/vendor/.installed-from';
$wanted = \sha1_file($tools . '/composer.json');
if (!\is_file($codegen) || !\is_file($stamp) || \file_get_contents($stamp) !== $wanted) {
    $composer = \getenv('COMPOSER_BINARY');
    $install = \is_string($composer) && $composer !== ''
        ? \escapeshellarg(\PHP_BINARY) . ' ' . \escapeshellarg($composer)
        : 'composer';
    \passthru($install . ' update --no-interaction --no-progress --quiet --working-dir=' . \escapeshellarg($tools), $installed);
    if ($installed !== 0 || !\is_file($codegen)) {
        \fwrite(\STDERR, "Could not install json-api-client-codegen into {$tools}.\n");

        exit(1);
    }
    \file_put_contents($stamp, (string) $wanted);
}

if (!\copy($document, $example . '/openapi.json')) {
    \fwrite(\STDERR, "Could not copy {$document} into {$example}.\n");

    exit(1);
}

// Start from an empty directory, so a type the document no longer declares leaves no file behind.
$output = $example . '/generated';
foreach (\glob($output . '/*') ?: [] as $stale) {
    \unlink($stale);
}

$command = \implode(' ', \array_map('escapeshellarg', [
    \PHP_BINARY,
    $codegen,
    $example . '/openapi.json',
    $output,
    '--namespace=haddowg\JsonApiClient\Examples\MusicCatalog',
    // 8.3, so the example runs on every PHP version this package supports.
    '--target=8.3',
]));

\passthru($command, $code);

exit($code);
