<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Exceptions;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Errors\ErrorSource;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\Forbidden;
use haddowg\JsonApiClient\Exceptions\JsonApiErrorResponse;
use haddowg\JsonApiClient\Exceptions\MalformedDocument;
use haddowg\JsonApiClient\Exceptions\PointsAtDocumentMembers;
use haddowg\JsonApiClient\Exceptions\PointsAtQueryParameters;
use haddowg\JsonApiClient\Exceptions\ServerError;
use haddowg\JsonApiClient\Exceptions\ValidationFailed;
use haddowg\JsonApiClient\Tests\Fixtures\PaymentRequiredError;
use haddowg\JsonApiClient\Tests\Generated\MusicCatalog\ApplicationError;
use haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AttributeValueInvalidError;
use haddowg\JsonApiClient\Tests\Generated\MusicCatalog\FilterValueInvalidError;
use haddowg\JsonApiClient\Tests\Generated\MusicCatalog\InsufficientScopeError;
use haddowg\JsonApiClient\Tests\Generated\MusicCatalog\InsufficientScopeMeta;
use haddowg\JsonApiClient\Tests\Generated\MusicCatalog\MusicCatalogErrorCodes;
use haddowg\JsonApiClient\Tests\Generated\MusicCatalog\RequestBodyInvalidJsonError;
use haddowg\JsonApiClient\Tests\Generated\MusicCatalog\RequestBodyInvalidJsonMeta;
use haddowg\JsonApiClient\Tests\Generated\MusicCatalog\ResourceNotFoundError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonApiErrorResponse::class)]
#[UsesClass(FilterValueInvalidError::class)]
#[UsesClass(AttributeValueInvalidError::class)]
#[UsesClass(ResourceNotFoundError::class)]
#[UsesClass(ApplicationError::class)]
#[CoversTrait(PointsAtDocumentMembers::class)]
#[CoversTrait(PointsAtQueryParameters::class)]
#[UsesClass(Error::class)]
#[UsesClass(ErrorSource::class)]
#[UsesClass(InsufficientScopeError::class)]
#[UsesClass(InsufficientScopeMeta::class)]
#[UsesClass(RequestBodyInvalidJsonError::class)]
#[UsesClass(RequestBodyInvalidJsonMeta::class)]
#[UsesClass(PaymentRequiredError::class)]
final class ErrorCodeDispatchTest extends TestCase
{
    public function testErrorsSharingOneCatalogedCodeGetThatCodesClass(): void
    {
        $e = JsonApiErrorResponse::for(400, [
            self::error('FILTER_VALUE_INVALID', '400', parameter: 'filter[rating]'),
            self::error('FILTER_VALUE_INVALID', '400', parameter: 'filter[releasedAt]'),
        ], codes: MusicCatalogErrorCodes::MAP);

        self::assertInstanceOf(FilterValueInvalidError::class, $e);
        self::assertInstanceOf(BadRequest::class, $e, 'a code class is still its status class');
        self::assertSame(['filter[rating]', 'filter[releasedAt]'], $e->parameters());
        self::assertCount(2, $e->errors());
    }

    public function testAPointerCodeExposesItsPointers(): void
    {
        $e = JsonApiErrorResponse::for(422, [self::error('ATTRIBUTE_VALUE_INVALID', '422', pointer: '/data/attributes/title')], codes: MusicCatalogErrorCodes::MAP);

        self::assertInstanceOf(AttributeValueInvalidError::class, $e);
        self::assertInstanceOf(ValidationFailed::class, $e);
        self::assertSame(['/data/attributes/title'], $e->pointers());
    }

    public function testAServerErrorCodeKeepsItsStatus(): void
    {
        $e = JsonApiErrorResponse::for(500, [self::error('APPLICATION_ERROR', '500')], codes: MusicCatalogErrorCodes::MAP);

        self::assertInstanceOf(ApplicationError::class, $e);
        self::assertInstanceOf(ServerError::class, $e);
        self::assertSame(500, $e->statusCode());
    }

    public function testMixedCodesFallBackToTheStatusClassWithEveryError(): void
    {
        $e = JsonApiErrorResponse::for(400, [
            self::error('FILTER_VALUE_INVALID', '400'),
            self::error('SORTING_UNRECOGNIZED', '400'),
        ], codes: MusicCatalogErrorCodes::MAP);

        self::assertSame(BadRequest::class, $e::class);
        self::assertCount(2, $e->errors());
    }

    public function testAnUncataloguedCodeFallsBackToTheStatusClass(): void
    {
        $e = JsonApiErrorResponse::for(400, [self::error('SOMETHING_THE_APP_INVENTED', '400')], codes: MusicCatalogErrorCodes::MAP);

        self::assertSame(BadRequest::class, $e::class);
        self::assertSame('SOMETHING_THE_APP_INVENTED', $e->errors()[0]->code);
    }

    public function testACodeIsDispatchedOnlyThroughTheMapItIsGiven(): void
    {
        $errors = [self::error('FILTER_VALUE_INVALID', '400')];

        self::assertSame(BadRequest::class, JsonApiErrorResponse::for(400, $errors)::class, 'the runtime ships no code classes of its own');
        self::assertInstanceOf(FilterValueInvalidError::class, JsonApiErrorResponse::for(400, $errors, codes: MusicCatalogErrorCodes::MAP));
        self::assertInstanceOf(PaymentRequiredError::class, JsonApiErrorResponse::for(400, $errors, codes: ['FILTER_VALUE_INVALID' => PaymentRequiredError::class]));
    }

    public function testRequiredMetaIsReadIntoTheCodesMetaClass(): void
    {
        $e = JsonApiErrorResponse::for(403, [new Error(status: '403', code: 'INSUFFICIENT_SCOPE', meta: [
            'requiredScopes' => ['catalog:read', 'catalog:write'],
            'missingScopes' => ['catalog:write'],
            'include' => 'tracks',
        ])], codes: MusicCatalogErrorCodes::MAP);

        self::assertInstanceOf(InsufficientScopeError::class, $e);
        self::assertInstanceOf(Forbidden::class, $e);
        self::assertSame(['catalog:read', 'catalog:write'], $e->meta()->requiredScopes);
        self::assertSame(['catalog:write'], $e->meta()->missingScopes);
        self::assertSame('tracks', $e->meta()->include);
    }

    public function testRequiredMetaMissingAMemberIsMalformed(): void
    {
        $e = new InsufficientScopeError([new Error(code: 'INSUFFICIENT_SCOPE', meta: ['requiredScopes' => ['catalog:read']])]);

        $this->expectException(MalformedDocument::class);
        $this->expectExceptionMessage('"missingScopes"');

        $e->meta();
    }

    public function testOptionalMetaIsNullWhenTheErrorCarriesNone(): void
    {
        self::assertNull((new RequestBodyInvalidJsonError([new Error(code: 'REQUEST_BODY_INVALID_JSON')]))->meta());
        self::assertSame('{"data":', (new RequestBodyInvalidJsonError([new Error(code: 'REQUEST_BODY_INVALID_JSON', meta: ['original' => '{"data":'])]))->meta()?->original);
    }

    public function testACodeArrivingWithADifferentStatusFallsBackToThatStatusClass(): void
    {
        $e = JsonApiErrorResponse::for(400, [self::error('RESOURCE_NOT_FOUND', '400')], codes: MusicCatalogErrorCodes::MAP);

        self::assertSame(BadRequest::class, $e::class);
    }

    public function testErrorsWithoutACodeFallBackToTheStatusClass(): void
    {
        self::assertSame(BadRequest::class, JsonApiErrorResponse::for(400, [new Error(status: '400')], codes: MusicCatalogErrorCodes::MAP)::class);
        self::assertSame(BadRequest::class, JsonApiErrorResponse::for(400)::class);
    }

    public function testEveryMappedClassCarriesTheCodeItIsMappedFrom(): void
    {
        foreach (MusicCatalogErrorCodes::MAP as $code => $class) {
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
