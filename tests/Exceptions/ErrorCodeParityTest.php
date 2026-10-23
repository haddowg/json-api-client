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

    /**
     * Core describes every code it catalogues. A catalogue copied from a document that predates
     * those descriptions would still regenerate byte for byte, so this is what catches it.
     */
    public function testEveryCataloguedCodeCarriesItsDescription(): void
    {
        $root = \dirname(__DIR__, 2);
        $catalogue = \json_decode((string) \file_get_contents($root . '/tests/Fixtures/error-catalogue.json'), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($catalogue);
        self::assertIsArray($catalogue['components'] ?? null);
        $schemas = $catalogue['components']['schemas'] ?? null;
        self::assertIsArray($schemas);
        self::assertNotEmpty($schemas);

        foreach ($schemas as $name => $schema) {
            self::assertIsArray($schema);
            $description = $schema['description'] ?? null;
            self::assertIsString($description, $name . ' has no description.');
            self::assertNotSame('', \trim($description), $name . ' has an empty description.');
            // The docblock wraps at word boundaries, so its first words are on one line.
            $opening = \implode(' ', \array_slice(\explode(' ', \trim($description)), 0, 5));
            self::assertStringContainsString($opening, (string) \file_get_contents($root . '/' . ErrorCodeEmitter::DIRECTORY . '/' . $name . '.php'), $name . "'s class does not carry its description.");
        }
    }
}
