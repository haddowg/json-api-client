<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

use haddowg\JsonApiClient\Support\DidYouMean;

/**
 * A filter name the endpoint does not advertise.
 *
 * Both array doors need this guard, not only the loose one. Filters are all optional, so every
 * filter key sits in the optional-key blind spot: `filter(['titel' => 'x'])` compiles and
 * returns a quietly unfiltered collection, which looks like data rather than a bug.
 */
final class UnknownFilterException extends \RuntimeException implements JsonApiClientException
{
    private function __construct(
        string $message,
        public readonly string $filter,
    ) {
        parent::__construct($message);
    }

    /**
     * @param list<string> $known the descriptor's filterable tokens for this endpoint
     */
    public static function for(string $filter, array $known): self
    {
        return new self(
            \sprintf('"%s" is not a filter this endpoint accepts.', $filter)
            . DidYouMean::hint($filter, $known),
            $filter,
        );
    }
}
