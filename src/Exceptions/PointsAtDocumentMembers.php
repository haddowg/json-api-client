<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

/**
 * For a code whose catalogue entry requires `source.pointer`: the pointers, one per error.
 *
 * @phpstan-require-extends JsonApiErrorResponse
 */
trait PointsAtDocumentMembers
{
    /**
     * @return list<string>
     */
    public function pointers(): array
    {
        $pointers = [];
        foreach ($this->errors() as $error) {
            $pointer = $error->pointer();
            if ($pointer !== null) {
                $pointers[] = $pointer;
            }
        }

        return $pointers;
    }
}
