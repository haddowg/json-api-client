<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Exceptions;

use haddowg\JsonApiClient\Tests\Tooling\ErrorCodeEmitter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The runtime's code classes are generated, not written. Regenerating them from the committed
 * catalogue must reproduce `src/Exceptions/Codes` byte for byte, so a hand edit, a stale class
 * or a catalogue updated without regenerating all fail here.
 */
#[CoversClass(ErrorCodeEmitter::class)]
final class ErrorCodeParityTest extends TestCase
{
    public function testTheCommittedClassesAreExactlyWhatTheCatalogueGenerates(): void
    {
        $root = \dirname(__DIR__, 2);
        $catalogue = \json_decode((string) \file_get_contents($root . '/tests/Fixtures/error-catalogue.json'), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($catalogue);

        $expected = ErrorCodeEmitter::emit($catalogue);

        $committed = [];
        foreach (\glob($root . '/' . ErrorCodeEmitter::DIRECTORY . '/*.php') ?: [] as $file) {
            $committed[ErrorCodeEmitter::DIRECTORY . '/' . \basename($file)] = (string) \file_get_contents($file);
        }
        \ksort($committed, \SORT_STRING);

        self::assertSame(\array_keys($expected), \array_keys($committed), 'Regenerate with bin/generate-error-codes.php.');
        foreach ($expected as $path => $contents) {
            self::assertSame($contents, $committed[$path], $path . ' differs from what the catalogue generates.');
        }
    }
}
