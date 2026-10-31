<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Validation;

use haddowg\JsonApiClient\Exceptions\SchemaViolation;
use Psr\Http\Message\RequestInterface;

/**
 * Checks a successful response against what the server documents, before anything reads it.
 *
 * Opt-in: without one, the client applies only its structural guards. {@see OpenApiValidator} is
 * the implementation that reads the server's own OpenAPI document; anything else that can say
 * whether a body is acceptable can stand in for it.
 */
interface ResponseValidator
{
    /**
     * @param string $path the request's path relative to the client's base URL, with a leading `/`
     *
     * @throws SchemaViolation when the body does not match
     */
    public function validate(RequestInterface $request, string $path, int $status, string $contentType, string $body): void;
}
