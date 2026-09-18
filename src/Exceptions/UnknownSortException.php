<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

use haddowg\JsonApiClient\Support\DidYouMean;

/**
 * A sort token the endpoint does not advertise.
 *
 * The typed door takes a variadic of literal tokens and catches this at compile time. A
 * `list<string>` assembled from a request cannot satisfy a literal union, so `sortRaw()` exists
 * — and this is what keeps the loose door from being a silent one.
 */
final class UnknownSortException extends \RuntimeException implements JsonApiClientException
{
    private function __construct(
        string $message,
        public readonly string $token,
    ) {
        parent::__construct($message);
    }

    /**
     * @param list<string> $known every signed token the endpoint sorts by
     */
    public static function for(string $token, array $known): self
    {
        return new self(
            \sprintf('"%s" is not a sort token this endpoint accepts.', $token)
            . DidYouMean::hint($token, $known),
            $token,
        );
    }
}
