<?php

declare(strict_types=1);

use haddowg\JsonApiClient\Tests\Tooling\ErrorCodeEmitter;

/*
 * Regenerates src/Exceptions/Codes from a json-api server's OpenAPI document, and the trimmed
 * catalogue copy tests/Fixtures/error-catalogue.json the parity test checks it against.
 *
 *   php bin/generate-error-codes.php ../json-api-client-codegen/tests/fixtures/music-catalog.openapi.json
 */

require \dirname(__DIR__) . '/vendor/autoload.php';

$source = $argv[1] ?? null;
if ($source === null || !\is_file($source)) {
    \fwrite(\STDERR, "Usage: php bin/generate-error-codes.php <openapi.json>\n");
    exit(1);
}

$document = \json_decode((string) \file_get_contents($source), true, 512, \JSON_THROW_ON_ERROR);
if (!\is_array($document)) {
    \fwrite(\STDERR, "{$source} is not a JSON object.\n");
    exit(1);
}

$root = \dirname(__DIR__);
foreach (\glob($root . '/' . ErrorCodeEmitter::DIRECTORY . '/*.php') ?: [] as $stale) {
    \unlink($stale);
}

$files = ErrorCodeEmitter::emit($document);
foreach ($files as $path => $contents) {
    \file_put_contents($root . '/' . $path, $contents);
}

\file_put_contents(
    $root . '/tests/Fixtures/error-catalogue.json',
    \json_encode(ErrorCodeEmitter::catalogue($document), \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR) . "\n",
);

\fwrite(\STDOUT, \sprintf("Wrote %d files to %s.\n", \count($files), ErrorCodeEmitter::DIRECTORY));
