<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Atomic;

use haddowg\JsonApiClient\Support\Wire;
use haddowg\JsonApiClient\Writes\UpdateInput;

/**
 * An update recorded in an atomic batch. After the batch commits, {@see self::resource()} reads
 * the resource as the server left it.
 *
 * @template-covariant T of object
 */
final class Updated
{
    /**
     * @var T|null
     */
    private ?object $resource = null;

    /**
     * @internal returned by {@see Tx::update()}
     *
     * @param UpdateInput<T> $input
     */
    public function __construct(
        private readonly Tx $tx,
        public readonly int $opIndex,
        private readonly UpdateInput $input,
    ) {}

    /**
     * The updated resource, or null when the server applied the update exactly as sent and so
     * returned no resource for it, which the extension allows.
     *
     * @return T|null
     *
     * @throws \LogicException when the batch has not committed
     */
    public function resource(): ?object
    {
        if ($this->resource !== null) {
            return $this->resource;
        }

        $result = $this->tx->_result($this->opIndex);
        $data = Wire::map($result, 'data');

        if ($data === []) {
            return null;
        }

        return $this->resource = $this->input->_hydrate($data, $this->tx->_context($result));
    }

    /**
     * The `meta` of the server's result for this operation.
     *
     * @return array<string, mixed>
     *
     * @throws \LogicException when the batch has not committed
     */
    public function meta(): array
    {
        return Wire::map($this->tx->_result($this->opIndex), 'meta');
    }
}
