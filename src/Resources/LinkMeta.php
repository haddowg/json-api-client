<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Resources;

/**
 * What a generated link meta class is built on: the `meta` of one declared link object.
 *
 * A meta member is read through the same typed readers as an attribute, so a date reads as a
 * `\DateTimeImmutable` and an enum as its case. A member the server can leave out reads as null
 * through a nullable reader. One it always renders reads through a strict reader, which throws
 * `MalformedDocument` when it is missing.
 */
abstract class LinkMeta
{
    use ReadsMembers;

    /**
     * @param array<string, mixed> $members the link's `meta` as it arrived
     * @param string               $subject the meta, for a failure message: `The meta of the "upload" link of the "files" resource`
     */
    final public function __construct(
        private readonly array $members,
        private readonly string $subject,
    ) {}

    /**
     * The `meta` as it arrived.
     *
     * @return array<string, mixed>
     */
    public function _raw(): array
    {
        return $this->members;
    }

    protected function _attribute(string $field): mixed
    {
        return $this->members[$field] ?? null;
    }

    protected function _subject(): string
    {
        return $this->subject;
    }
}
