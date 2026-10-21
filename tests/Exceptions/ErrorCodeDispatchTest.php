<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Exceptions;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Errors\ErrorSource;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\Codes\ApplicationError;
use haddowg\JsonApiClient\Exceptions\Codes\AttributeValueInvalidError;
use haddowg\JsonApiClient\Exceptions\Codes\ErrorCodes;
use haddowg\JsonApiClient\Exceptions\Codes\FilterValueInvalidError;
use haddowg\JsonApiClient\Exceptions\Codes\ResourceNotFoundError;
use haddowg\JsonApiClient\Exceptions\JsonApiErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtDocumentMembers;
use haddowg\JsonApiClient\Exceptions\PointsAtQueryParameters;
use haddowg\JsonApiClient\Exceptions\ServerError;
use haddowg\JsonApiClient\Exceptions\ValidationFailed;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonApiErrorResponse::class)]
#[CoversClass(FilterValueInvalidError::class)]
#[CoversClass(AttributeValueInvalidError::class)]
#[CoversClass(ResourceNotFoundError::class)]
#[CoversClass(ApplicationError::class)]
#[CoversTrait(PointsAtDocumentMembers::class)]
#[CoversTrait(PointsAtQueryParameters::class)]
#[UsesClass(Error::class)]
#[UsesClass(ErrorSource::class)]
#[UsesClass(ErrorCodes::class)]
final class ErrorCodeDispatchTest extends TestCase
{
    public function testErrorsSharingOneCatalogedCodeGetThatCodesClass(): void
    {
        $e = JsonApiErrorResponse::for(400, [
            self::error('FILTER_VALUE_INVALID', '400', parameter: 'filter[rating]'),
            self::error('FILTER_VALUE_INVALID', '400', parameter: 'filter[releasedAt]'),
        ]);

        self::assertInstanceOf(FilterValueInvalidError::class, $e);
        self::assertInstanceOf(BadRequest::class, $e, 'a code class is still its status class');
        self::assertSame(['filter[rating]', 'filter[releasedAt]'], $e->parameters());
        self::assertCount(2, $e->errors());
    }

    public function testAPointerCodeExposesItsPointers(): void
    {
        $e = JsonApiErrorResponse::for(422, [self::error('ATTRIBUTE_VALUE_INVALID', '422', pointer: '/data/attributes/title')]);

        self::assertInstanceOf(AttributeValueInvalidError::class, $e);
        self::assertInstanceOf(ValidationFailed::class, $e);
        self::assertSame(['/data/attributes/title'], $e->pointers());
    }

    public function testAServerErrorCodeKeepsItsStatus(): void
    {
        $e = JsonApiErrorResponse::for(500, [self::error('APPLICATION_ERROR', '500')]);

        self::assertInstanceOf(ApplicationError::class, $e);
        self::assertInstanceOf(ServerError::class, $e);
        self::assertSame(500, $e->statusCode());
    }

    public function testMixedCodesFallBackToTheStatusClassWithEveryError(): void
    {
        $e = JsonApiErrorResponse::for(400, [
            self::error('FILTER_VALUE_INVALID', '400'),
            self::error('SORTING_UNRECOGNIZED', '400'),
        ]);

        self::assertSame(BadRequest::class, $e::class);
        self::assertCount(2, $e->errors());
    }

    public function testAnUncataloguedCodeFallsBackToTheStatusClass(): void
    {
        $e = JsonApiErrorResponse::for(400, [self::error('SOMETHING_THE_APP_INVENTED', '400')]);

        self::assertSame(BadRequest::class, $e::class);
        self::assertSame('SOMETHING_THE_APP_INVENTED', $e->errors()[0]->code);
    }

    public function testACodeArrivingWithADifferentStatusFallsBackToThatStatusClass(): void
    {
        $e = JsonApiErrorResponse::for(400, [self::error('RESOURCE_NOT_FOUND', '400')]);

        self::assertSame(BadRequest::class, $e::class);
    }

    public function testErrorsWithoutACodeFallBackToTheStatusClass(): void
    {
        self::assertSame(BadRequest::class, JsonApiErrorResponse::for(400, [new Error(status: '400')])::class);
        self::assertSame(BadRequest::class, JsonApiErrorResponse::for(400)::class);
    }

    public function testEveryMappedClassCarriesTheCodeItIsMappedFrom(): void
    {
        foreach (ErrorCodes::MAP as $code => $class) {
            self::assertSame($code, $class::CODE);
        }
    }

    private static function error(string $code, string $status, ?string $pointer = null, ?string $parameter = null): Error
    {
        return new Error(
            status: $status,
            code: $code,
            source: $pointer === null && $parameter === null ? null : new ErrorSource(pointer: $pointer, parameter: $parameter),
        );
    }
}
