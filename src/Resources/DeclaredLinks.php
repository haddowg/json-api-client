<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Resources;

use haddowg\JsonApiClient\Exceptions\MalformedDocument;
use haddowg\JsonApiClient\Support\Wire;

/**
 * What a generated `<Type>Links` is built on: the links a resource type declares, one typed
 * accessor each, read from the resource's `links`.
 *
 * A declared link is optional on the wire, because a server leaves one out whenever its href
 * resolves to nothing, so every accessor returns null for an absent link. A bare link reads its
 * URL through `_href()`, which takes a string or a link object as `_link()` does. A link object
 * is wrapped in the generated `DeclaredLink` that types its members and meta.
 *
 * Links the type does not declare are not here. `ResourceObject::_links()` still has all of them.
 */
abstract class DeclaredLinks
{
    use ReadsMembers;

    /**
     * @param array<string, mixed> $links   the resource's `links` as it arrived
     * @param string               $subject what owns them, for a failure message: `The "images" resource`
     */
    final public function __construct(
        private readonly array $links,
        private readonly string $subject,
    ) {}

    /**
     * The resource's `links` as they arrived, declared or not.
     *
     * @return array<string, mixed>
     */
    public function _raw(): array
    {
        return $this->links;
    }

    /**
     * A bare link's URL, from either a string or a link object, or null when the response left
     * it out.
     */
    protected function _href(string $name): ?string
    {
        return Wire::href($this->links[$name] ?? null);
    }

    /**
     * A link object, wrapped in the class generated for it, or null when the response left it
     * out.
     *
     * @template TLink of DeclaredLink
     *
     * @param class-string<TLink> $class
     *
     * @return TLink|null
     *
     * @throws MalformedDocument when the link is there but is not an object
     */
    protected function _object(string $name, string $class): ?DeclaredLink
    {
        $link = $this->_nullableMap($name);

        return $link === null ? null : new $class($link, 'The "' . $name . '" link of ' . self::lower($this->subject));
    }

    protected function _attribute(string $field): mixed
    {
        return $this->links[$field] ?? null;
    }

    protected function _subject(): string
    {
        return 'The links of ' . self::lower($this->subject);
    }

    private static function lower(string $subject): string
    {
        return \str_starts_with($subject, 'The ') ? 'the ' . \substr($subject, 4) : $subject;
    }
}
