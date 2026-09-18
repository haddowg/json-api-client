<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Errors;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Errors\ErrorDocument;
use haddowg\JsonApiClient\Errors\ErrorSource;
use haddowg\JsonApiClient\Errors\SourcePointer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Error::class)]
#[CoversClass(ErrorSource::class)]
#[CoversClass(ErrorDocument::class)]
#[UsesClass(SourcePointer::class)]
final class ErrorTest extends TestCase
{
    public function testItReadsEveryMemberTheSpecDefines(): void
    {
        $error = Error::fromArray([
            'id' => 'e-1',
            'status' => '422',
            'code' => 'ATTRIBUTE_INVALID',
            'title' => 'Invalid Attribute',
            'detail' => 'The title must be at most 200 characters.',
            'source' => ['pointer' => '/data/attributes/title'],
            'meta' => ['max' => 200],
        ]);

        self::assertSame('e-1', $error->id);
        self::assertSame('422', $error->status);
        self::assertSame(422, $error->statusCode());
        self::assertSame('ATTRIBUTE_INVALID', $error->code);
        self::assertSame('Invalid Attribute', $error->title);
        self::assertSame('The title must be at most 200 characters.', $error->detail);
        self::assertSame(['max' => 200], $error->meta);
    }

    public function testTheRawPointerSurvivesAlongsideTheRemappedPath(): void
    {
        $error = Error::fromArray(['source' => ['pointer' => '/data/attributes/releaseInfo/label']]);

        self::assertSame('/data/attributes/releaseInfo/label', $error->pointer());
        self::assertSame('releaseInfo.label', $error->pathKey());
    }

    public function testAnAlreadyRemappedPathWinsOverRemappingThePointerAgain(): void
    {
        // A descriptor-aware remap upstream knows things this one cannot; it must not be redone.
        $error = Error::fromArray(['source' => ['pointer' => '/data/attributes/title']])
            ->withPath('tracks[0].title');

        self::assertSame('tracks[0].title', $error->pathKey());
        self::assertSame('/data/attributes/title', $error->pointer());
    }

    public function testAQuerySideErrorGroupsUnderTheParameterTheServerNamed(): void
    {
        // `source.parameter` already names something the caller wrote, so it is left alone.
        $error = Error::fromArray(['source' => ['parameter' => 'filter[titel]']]);

        self::assertSame('filter[titel]', $error->pathKey());
        self::assertSame('filter[titel]', $error->parameter());
        self::assertNull($error->pointer());
    }

    public function testAnErrorTheServerBlamedOnNothingGroupsUnderTheUnattributedKey(): void
    {
        $error = Error::fromArray(['title' => 'Service Unavailable']);

        self::assertSame(Error::UNATTRIBUTED, $error->pathKey());
        self::assertNull($error->source);
    }

    public function testAHeaderSourceIsCarriedButAttributedToNothing(): void
    {
        $error = Error::fromArray(['source' => ['header' => 'Accept']]);

        self::assertSame('Accept', $error->source?->header);
        self::assertSame(Error::UNATTRIBUTED, $error->pathKey());
    }

    public function testWithPathKeepsEverythingElseAndCarriesTheOperationIndex(): void
    {
        $original = Error::fromArray([
            'code' => 'ATTRIBUTE_INVALID',
            'source' => ['pointer' => '/atomic:operations/2/data/attributes/title'],
            'meta' => ['max' => 200],
        ]);

        $remapped = $original->withPath('title', 2);

        self::assertSame('title', $remapped->path);
        self::assertSame(2, $remapped->opIndex);
        self::assertSame('ATTRIBUTE_INVALID', $remapped->code);
        self::assertSame(['max' => 200], $remapped->meta);
        self::assertNull($original->path, 'the original must not be mutated');
    }

    public function testANonNumericStatusYieldsNoStatusCode(): void
    {
        self::assertNull(Error::fromArray(['status' => 'unprocessable'])->statusCode());
        self::assertNull(Error::fromArray([])->statusCode());
    }

    public function testAnEmptySourceObjectReadsAsNoSource(): void
    {
        self::assertNull(Error::fromArray(['source' => []])->source);
    }

    public function testParsingAnErrorDocument(): void
    {
        $errors = ErrorDocument::parse('{"errors":[{"status":"422","title":"a"},{"status":"422","title":"b"}]}');

        self::assertCount(2, $errors);
        self::assertSame('a', $errors[0]->title);
        self::assertSame('b', $errors[1]->title);
    }

    public function testABodyWithNoUsableErrorsYieldsNone(): void
    {
        // A gateway that answers with HTML still has to produce an exception carrying a status,
        // so an unparseable body must not become a second failure on top of the first.
        self::assertSame([], ErrorDocument::parse('<html>502 Bad Gateway</html>'));
        self::assertSame([], ErrorDocument::parse(''));
        self::assertSame([], ErrorDocument::parse('{"errors":"nope"}'));
        self::assertSame([], ErrorDocument::parse('{"data":null}'));
    }

    public function testErrorMembersThatAreNotObjectsAreSkipped(): void
    {
        $errors = ErrorDocument::parse('{"errors":["oops",{"title":"real"}]}');

        self::assertCount(1, $errors);
        self::assertSame('real', $errors[0]->title);
    }
}
