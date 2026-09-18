<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Exceptions;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Errors\ErrorDocument;
use haddowg\JsonApiClient\Errors\ErrorSource;
use haddowg\JsonApiClient\Errors\SourcePointer;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\Conflict;
use haddowg\JsonApiClient\Exceptions\ErrorResponse;
use haddowg\JsonApiClient\Exceptions\Forbidden;
use haddowg\JsonApiClient\Exceptions\JsonApiClientException;
use haddowg\JsonApiClient\Exceptions\JsonApiErrorResponse;
use haddowg\JsonApiClient\Exceptions\NotAcceptable;
use haddowg\JsonApiClient\Exceptions\NotFound;
use haddowg\JsonApiClient\Exceptions\ServerError;
use haddowg\JsonApiClient\Exceptions\TooManyRequests;
use haddowg\JsonApiClient\Exceptions\Unauthorized;
use haddowg\JsonApiClient\Exceptions\UnexpectedResponse;
use haddowg\JsonApiClient\Exceptions\UnsupportedMediaType;
use haddowg\JsonApiClient\Exceptions\ValidationFailed;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonApiErrorResponse::class)]
#[CoversClass(BadRequest::class)]
#[CoversClass(Conflict::class)]
#[CoversClass(Forbidden::class)]
#[CoversClass(NotAcceptable::class)]
#[CoversClass(NotFound::class)]
#[CoversClass(ServerError::class)]
#[CoversClass(TooManyRequests::class)]
#[CoversClass(Unauthorized::class)]
#[CoversClass(UnexpectedResponse::class)]
#[CoversClass(UnsupportedMediaType::class)]
#[CoversClass(ValidationFailed::class)]
#[UsesClass(Error::class)]
#[UsesClass(ErrorDocument::class)]
#[UsesClass(ErrorSource::class)]
#[UsesClass(SourcePointer::class)]
final class JsonApiErrorResponseTest extends TestCase
{
    /**
     * @param class-string $expected
     */
    #[DataProvider('statuses')]
    public function testEachStatusGetsItsOwnCatchableClass(int $status, string $expected): void
    {
        self::assertInstanceOf($expected, JsonApiErrorResponse::for($status));
    }

    /**
     * @return iterable<string, array{int, class-string}>
     */
    public static function statuses(): iterable
    {
        yield '400' => [400, BadRequest::class];
        yield '401' => [401, Unauthorized::class];
        yield '403' => [403, Forbidden::class];
        yield '404' => [404, NotFound::class];
        yield '406' => [406, NotAcceptable::class];
        yield '409' => [409, Conflict::class];
        yield '415' => [415, UnsupportedMediaType::class];
        yield '422' => [422, ValidationFailed::class];
        yield '429' => [429, TooManyRequests::class];
        yield '500' => [500, ServerError::class];
        yield '502' => [502, ServerError::class];
        yield '418' => [418, UnexpectedResponse::class];
        yield '451' => [451, UnexpectedResponse::class];
    }

    public function testAFiveHundredCarriesItsOwnStatusRatherThanACollapsedOne(): void
    {
        self::assertSame(502, JsonApiErrorResponse::for(502)->statusCode());
        self::assertSame(503, JsonApiErrorResponse::for(503)->statusCode());
    }

    public function testEveryExceptionIsCatchableThroughTheOneContract(): void
    {
        $exception = JsonApiErrorResponse::for(404);

        self::assertInstanceOf(ErrorResponse::class, $exception);
        self::assertInstanceOf(JsonApiClientException::class, $exception);
    }

    public function testTheStatusMatchers(): void
    {
        $notFound = JsonApiErrorResponse::for(404);

        self::assertTrue($notFound->isNotFound());
        self::assertTrue($notFound->is4xx());
        self::assertTrue($notFound->hasStatus(404));
        self::assertFalse($notFound->is5xx());
        self::assertFalse($notFound->isConflict());
        self::assertFalse($notFound->hasStatus(400));
    }

    public function testValidationErrorIsAnAliasOfUnprocessable(): void
    {
        $failed = JsonApiErrorResponse::for(422);

        self::assertTrue($failed->isUnprocessable());
        self::assertTrue($failed->isValidationError());
    }

    /**
     * @param \Closure(ErrorResponse): bool $matcher
     */
    #[DataProvider('matchers')]
    public function testEachMatcherAnswersForItsOwnStatusOnly(\Closure $matcher, int $status): void
    {
        $matching = JsonApiErrorResponse::for($status);
        $other = JsonApiErrorResponse::for($status === 404 ? 409 : 404);

        self::assertTrue($matcher($matching));
        self::assertFalse($matcher($other));
    }

    /**
     * @return iterable<string, array{\Closure(ErrorResponse): bool, int}>
     */
    public static function matchers(): iterable
    {
        yield 'isBadRequest' => [static fn(ErrorResponse $e): bool => $e->isBadRequest(), 400];
        yield 'isUnauthorized' => [static fn(ErrorResponse $e): bool => $e->isUnauthorized(), 401];
        yield 'isForbidden' => [static fn(ErrorResponse $e): bool => $e->isForbidden(), 403];
        yield 'isNotFound' => [static fn(ErrorResponse $e): bool => $e->isNotFound(), 404];
        yield 'isNotAcceptable' => [static fn(ErrorResponse $e): bool => $e->isNotAcceptable(), 406];
        yield 'isConflict' => [static fn(ErrorResponse $e): bool => $e->isConflict(), 409];
        yield 'isUnsupportedMediaType' => [static fn(ErrorResponse $e): bool => $e->isUnsupportedMediaType(), 415];
        yield 'isUnprocessable' => [static fn(ErrorResponse $e): bool => $e->isUnprocessable(), 422];
        yield 'isRateLimited' => [static fn(ErrorResponse $e): bool => $e->isRateLimited(), 429];
    }

    public function testByPathGroupsAValidationFailureByTheShapeTheCallerSupplied(): void
    {
        $exception = JsonApiErrorResponse::fromBody(422, (string) \json_encode([
            'errors' => [
                ['code' => 'a', 'source' => ['pointer' => '/data/attributes/title']],
                ['code' => 'b', 'source' => ['pointer' => '/data/attributes/title']],
                ['code' => 'c', 'source' => ['pointer' => '/data/attributes/releaseInfo/label']],
                ['code' => 'd', 'source' => ['pointer' => '/data/relationships/artist/data']],
                ['code' => 'e', 'source' => ['parameter' => 'sort']],
                ['code' => 'f'],
            ],
        ]));

        $grouped = $exception->byPath();

        self::assertSame(
            ['title', 'releaseInfo.label', 'artist', 'sort', Error::UNATTRIBUTED],
            \array_keys($grouped),
        );
        self::assertCount(2, $grouped['title']);
        self::assertSame(['a', 'b'], \array_map(static fn(Error $e): ?string => $e->code, $grouped['title']));
    }

    public function testByPathIsEmptyWhenTheServerReportedNoErrors(): void
    {
        self::assertSame([], JsonApiErrorResponse::for(500)->byPath());
    }

    public function testWithCodeFindsTheFirstErrorCarryingIt(): void
    {
        $exception = JsonApiErrorResponse::fromBody(400, (string) \json_encode([
            'errors' => [
                ['code' => 'FILTER_PARAM_UNRECOGNIZED', 'detail' => 'first'],
                ['code' => 'FILTER_PARAM_UNRECOGNIZED', 'detail' => 'second'],
            ],
        ]));

        self::assertSame('first', $exception->withCode('FILTER_PARAM_UNRECOGNIZED')?->detail);
        self::assertNull($exception->withCode('SOMETHING_ELSE'));
    }

    public function testTheMessageLeadsWithTheServersOwnWords(): void
    {
        $exception = JsonApiErrorResponse::fromBody(404, (string) \json_encode([
            'errors' => [['title' => 'Resource Not Found']],
        ]));

        self::assertSame('JSON:API request failed with status 404: Resource Not Found', $exception->getMessage());
    }

    public function testTheMessageFallsBackToDetailThenToTheStatusAlone(): void
    {
        $withDetail = JsonApiErrorResponse::fromBody(409, (string) \json_encode([
            'errors' => [['detail' => 'That id is taken.']],
        ]));
        $bare = JsonApiErrorResponse::for(500);

        self::assertSame('JSON:API request failed with status 409: That id is taken.', $withDetail->getMessage());
        self::assertSame('JSON:API request failed with status 500', $bare->getMessage());
    }

    public function testTheMessageSaysHowManyMoreErrorsThereAre(): void
    {
        $exception = JsonApiErrorResponse::fromBody(422, (string) \json_encode([
            'errors' => [['title' => 'Invalid Attribute'], ['title' => 'Invalid Attribute'], ['title' => 'x']],
        ]));

        self::assertSame(
            'JSON:API request failed with status 422: Invalid Attribute (and 2 more)',
            $exception->getMessage(),
        );
    }

    public function testAnExplicitMessageWins(): void
    {
        self::assertSame('nope', JsonApiErrorResponse::for(404, [], 'nope')->getMessage());
    }

    public function testTheStatusIsAlsoTheExceptionCodeSoItSurvivesLogging(): void
    {
        self::assertSame(422, JsonApiErrorResponse::for(422)->getCode());
    }

    public function testAnUnparseableBodyStillProducesTheRightExceptionForTheStatus(): void
    {
        $exception = JsonApiErrorResponse::fromBody(502, '<html>Bad Gateway</html>');

        self::assertInstanceOf(ServerError::class, $exception);
        self::assertSame(502, $exception->statusCode());
        self::assertSame([], $exception->errors());
    }

    public function testAPreviousExceptionIsCarried(): void
    {
        $previous = new \RuntimeException('underlying');

        self::assertSame($previous, JsonApiErrorResponse::for(500, [], null, $previous)->getPrevious());
    }

    public function testAStatusClassIsExtensibleSoGeneratedCodeSpecificSubclassesStayCatchable(): void
    {
        // A generated per-code class extends the status class its code maps to; an application
        // catching BadRequest must keep catching it.
        $generated = new class extends BadRequest {};

        self::assertInstanceOf(BadRequest::class, $generated);
        self::assertSame(400, $generated->statusCode());
    }
}
