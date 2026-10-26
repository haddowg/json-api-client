<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Resources;

use haddowg\JsonApiClient\Exceptions\MalformedDocument;
use haddowg\JsonApiClient\Support\Wire;

/**
 * What a generated link object class is built on: one declared link a server renders as an object
 * with an `href`, and possibly static members and `meta`.
 *
 * The members are read through the same typed readers as an attribute, so a member of the wrong
 * type throws `MalformedDocument` naming the link. A generated class adds an accessor per static
 * member the link declares and a `meta()` that wraps the link's `meta` in its own typed class.
 */
abstract class DeclaredLink
{
    use ReadsMembers;

    /**
     * @param array<string, mixed> $raw     the link object as it arrived
     * @param string               $subject the link, for a failure message: `The "upload" link of the "files" resource`
     */
    final public function __construct(
        private readonly array $raw,
        private readonly string $subject,
    ) {}

    /**
     * The link's URL.
     *
     * @throws MalformedDocument when the link object carries no string `href`
     */
    public function href(): string
    {
        return $this->_string('href');
    }

    /**
     * The link object as it arrived.
     *
     * @return array<string, mixed>
     */
    public function _raw(): array
    {
        return $this->raw;
    }

    /**
     * The link's `meta`, wrapped in the class generated for it. A link with no `meta` gets an
     * empty one, whose members then read as absent.
     *
     * @template TMeta of LinkMeta
     *
     * @param class-string<TMeta> $class
     *
     * @return TMeta
     */
    protected function _metaAs(string $class): LinkMeta
    {
        return new $class(Wire::map($this->raw, 'meta'), 'The meta of ' . self::lower($this->subject));
    }

    protected function _attribute(string $field): mixed
    {
        return $this->raw[$field] ?? null;
    }

    protected function _subject(): string
    {
        return $this->subject;
    }

    private static function lower(string $subject): string
    {
        return \str_starts_with($subject, 'The ') ? 'the ' . \substr($subject, 4) : $subject;
    }
}
