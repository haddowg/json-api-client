<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

/**
 * The contract every exception this client throws implements.
 *
 * One `catch` covers the lot — a server error document, a transport failure, a response
 * whose shape the runtime cannot work with.
 */
interface JsonApiClientException extends \Throwable {}
