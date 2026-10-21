<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

/**
 * For a code whose catalogue entry requires `source.parameter`: the query parameters the
 * server blamed, one per error.
 *
 * @phpstan-require-extends JsonApiErrorResponse
 */
trait PointsAtQueryParameters
{
    /**
     * @return list<string>
     */
    public function parameters(): array
    {
        $parameters = [];
        foreach ($this->errors() as $error) {
            $parameter = $error->parameter();
            if ($parameter !== null) {
                $parameters[] = $parameter;
            }
        }

        return $parameters;
    }
}
