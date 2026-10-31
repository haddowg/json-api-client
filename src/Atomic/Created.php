<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Atomic;

use haddowg\JsonApiClient\Exceptions\MalformedDocument;
use haddowg\JsonApiClient\Resources\ResourceContext;
use haddowg\JsonApiClient\Support\Wire;
use haddowg\JsonApiClient\Writes\CreateInput;

/**
 * A create recorded in an atomic batch.
 *
 * Before the batch is sent it stands in for the resource it will create: pass it to a relation
 * setter, or as the target of a later update or delete in the same batch, and it goes on the wire
 * as `{type, lid}`. After the batch commits, {@see self::resource()} reads the resource the server
 * created.
 *
 * @template-covariant T of object
 */
final class Created
{
    /**
     * @var T|null
     */
    private ?object $resource = null;

    /**
     * @internal returned by {@see Tx::create()}
     *
     * @param CreateInput<T> $input
     */
    public function __construct(
        private readonly Tx $tx,
        public readonly int $opIndex,
        private readonly CreateInput $input,
        public readonly string $type,
        private readonly ?string $id,
        public readonly ?string $lid,
    ) {}

    /**
     * The created resource, as the server returned it.
     *
     * @return T
     *
     * @throws \LogicException   when the batch has not committed
     * @throws MalformedDocument when the server's result for this operation carries no resource
     */
    public function resource(): object
    {
        if ($this->resource !== null) {
            return $this->resource;
        }

        $result = $this->tx->_result($this->opIndex);
        $data = Wire::map($result, 'data');

        if ($data === []) {
            throw MalformedDocument::missingMember(\sprintf('The result of atomic operation %d', $this->opIndex), 'data');
        }

        return $this->resource = $this->input->_hydrate($data, ResourceContext::of($result));
    }

    /**
     * The new resource's id: the client-generated one the input carried, or the one the server
     * assigned.
     *
     * @throws \LogicException   when the id is the server's and the batch has not committed
     * @throws MalformedDocument when the server's result for this operation carries no id
     */
    public function id(): string
    {
        if ($this->id !== null) {
            return $this->id;
        }

        $data = Wire::map($this->tx->_result($this->opIndex), 'data');

        return Wire::string($data, 'id') ?? throw MalformedDocument::missingMember(\sprintf('The result of atomic operation %d', $this->opIndex), 'id');
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

    /**
     * How this resource is referenced within the batch: by its client-generated id when it has
     * one, otherwise by its local id.
     *
     * @internal read by the recorder and by relationship linkage
     *
     * @return array{type: string, id: string}|array{type: string, lid: string}
     */
    public function _ref(): array
    {
        if ($this->id !== null) {
            return ['type' => $this->type, 'id' => $this->id];
        }

        return ['type' => $this->type, 'lid' => (string) $this->lid];
    }
}
