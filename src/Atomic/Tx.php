<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Atomic;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Errors\SourcePointer;
use haddowg\JsonApiClient\Exceptions\MalformedDocument;
use haddowg\JsonApiClient\Exceptions\MissingRequiredMemberException;
use haddowg\JsonApiClient\Resources\ResourceObject;
use haddowg\JsonApiClient\Support\Identifier;
use haddowg\JsonApiClient\Support\Wire;
use haddowg\JsonApiClient\Writes\CreateInput;
use haddowg\JsonApiClient\Writes\UpdateInput;

/**
 * The recorder an atomic batch is written against.
 *
 * Each method records one operation, in call order, and nothing is sent until the batch callback
 * returns. One recorder spans every type, which is why it takes the generated write inputs rather
 * than arrays: the input is what knows its type and how to read the resource that comes back.
 *
 * A create gets a local id, `atomic-<index>`, unless its input carries a client-generated id, so
 * the handle it returns can stand in for the new resource anywhere a later operation needs one:
 * as an update's or a delete's target, or in a relation setter.
 *
 * ```php
 * [$artist, $album] = $client->atomic(function (Tx $tx) {
 *     $a = $tx->create(Artist::create()->name('Boards of Canada'));
 *     $b = $tx->create(Album::create()->title('Geogaddi')->artist($a));
 *
 *     return [$a, $b];
 * });
 *
 * $album->resource()->title(); // 'Geogaddi'
 * ```
 *
 * A recorder belongs to one batch. Once the batch is sent, recording on it throws.
 */
final class Tx
{
    private const string LID_PREFIX = 'atomic-';

    /**
     * @var list<array<string, mixed>>
     */
    private array $operations = [];

    private bool $sealed = false;

    /**
     * One result object per operation, in batch order, once the batch has committed.
     *
     * @var list<array<string, mixed>>|null
     */
    private ?array $results = null;

    /**
     * @internal built by the runtime for each batch; generated code never constructs one
     */
    public function __construct() {}

    /**
     * Record a create.
     *
     * @template T of object
     *
     * @param CreateInput<T> $input a create input, or a builder, which is one
     *
     * @return Created<T>
     *
     * @throws MissingRequiredMemberException when a builder is missing a member the create requires
     * @throws \LogicException                when the batch has already been sent
     */
    public function create(CreateInput $input): Created
    {
        $this->guardOpen();

        $data = self::data($input->toDocument());
        $type = self::type($data);
        $index = \count($this->operations);

        $id = $data['id'] ?? null;
        $lid = null;

        if (!\is_string($id)) {
            $id = null;
            $lid = self::LID_PREFIX . $index;
            $data = ['type' => $type, 'lid' => $lid, ...$data];
        }

        $this->operations[] = ['op' => 'add', 'data' => $data];

        return new Created($this, $index, $input, $type, $id, $lid);
    }

    /**
     * Record an update of an existing resource, or of one created earlier in this batch.
     *
     * @template T of object
     *
     * @param Created<object>|ResourceObject|Identifier $target
     * @param UpdateInput<T>                            $input
     *
     * @return Updated<T>
     *
     * @throws \InvalidArgumentException when the input writes a different type from the target's
     * @throws \LogicException           when the batch has already been sent
     */
    public function update(Created|ResourceObject|Identifier $target, UpdateInput $input): Updated
    {
        $this->guardOpen();

        $ref = self::ref($target);
        $data = self::data($input->toDocument($ref['id'] ?? ''));
        $type = self::type($data);

        if ($type !== $ref['type']) {
            throw new \InvalidArgumentException(\sprintf('An update of "%s" cannot target a "%s" resource.', $type, $ref['type']));
        }

        unset($data['id'], $data['type']);
        $index = \count($this->operations);
        $this->operations[] = ['op' => 'update', 'ref' => $ref, 'data' => [...$ref, ...$data]];

        return new Updated($this, $index, $input);
    }

    /**
     * Record a delete of an existing resource, or of one created earlier in this batch.
     *
     * @param Created<object>|ResourceObject|Identifier $target
     *
     * @throws \LogicException when the batch has already been sent
     */
    public function delete(Created|ResourceObject|Identifier $target): void
    {
        $this->guardOpen();

        $this->operations[] = ['op' => 'remove', 'ref' => self::ref($target)];
    }

    /**
     * Close the recorder and hand over what it recorded, as the `atomic:operations` member.
     *
     * @internal called by the runtime when the batch callback returns
     *
     * @return list<array<string, mixed>>
     */
    public function _seal(): array
    {
        $this->sealed = true;

        return $this->operations;
    }

    /**
     * Take the server's answer to the batch: the `atomic:results` member of the response, or
     * null when it answered `204 No Content`.
     *
     * @internal called by the runtime once the batch has committed
     *
     * @param array<string, mixed>|null $document
     *
     * @throws MalformedDocument when the response does not carry one result per operation
     */
    public function _commit(?array $document): void
    {
        $count = \count($this->operations);

        if ($document === null) {
            $this->results = \array_fill(0, $count, []);

            return;
        }

        $results = $document['atomic:results'] ?? null;

        if (!\is_array($results) || !\array_is_list($results) || \count($results) !== $count) {
            throw MalformedDocument::unexpectedMember('The atomic response', 'atomic:results', \sprintf('a list of %d result%s', $count, $count === 1 ? '' : 's'));
        }

        $this->results = \array_map(static function (mixed $result): array {
            if (!\is_array($result)) {
                throw MalformedDocument::unexpectedMember('The atomic response', 'atomic:results', 'a list of result objects');
            }

            return Wire::map(['result' => $result], 'result');
        }, $results);
    }

    /**
     * The result object for one operation.
     *
     * @internal read by the handles this recorder returned
     *
     * @return array<string, mixed>
     *
     * @throws \LogicException when the batch has not committed
     */
    public function _result(int $index): array
    {
        if ($this->results === null) {
            throw new \LogicException('The atomic batch has not been committed, so its results cannot be read. Read a handle after $client->atomic() returns.');
        }

        return $this->results[$index] ?? [];
    }

    /**
     * Point an error the server raised for the batch at the operation and input member it blames.
     *
     * The server prefixes each pointer with `/atomic:operations/{n}`. The prefix is the failing
     * operation's index, in call order, and the tail is remapped as a standalone write's would be.
     *
     * @internal applied by the runtime to an error response for the batch
     */
    public static function _locate(Error $error): Error
    {
        $pointer = $error->pointer();
        if ($pointer === null) {
            return $error;
        }

        $located = SourcePointer::toAtomicPath($pointer);

        return $located['opIndex'] === null ? $error : $error->withPath($located['path'], $located['opIndex']);
    }

    /**
     * @param Created<object>|ResourceObject|Identifier $target
     *
     * @return array{type: string, id: string}|array{type: string, lid: string}
     */
    private static function ref(Created|ResourceObject|Identifier $target): array
    {
        if ($target instanceof Created) {
            return $target->_ref();
        }

        $identifier = $target instanceof ResourceObject ? $target->_identifier() : $target;

        return ['type' => $identifier->type, 'id' => $identifier->id];
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    private static function data(array $document): array
    {
        $data = Wire::map($document, 'data');

        if ($data === []) {
            throw new \LogicException('A write input produced a document with no primary data.');
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function type(array $data): string
    {
        return Wire::string($data, 'type') ?? throw new \LogicException('A write input produced a resource object with no type.');
    }

    /**
     * @throws \LogicException when the batch has already been sent
     */
    private function guardOpen(): void
    {
        if ($this->sealed) {
            throw new \LogicException('This atomic batch has already been sent. Record every operation inside the $client->atomic() callback.');
        }
    }
}
