<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Http;

use Psr\Http\Message\StreamInterface;

/**
 * One outbound request, described in the client's own terms.
 *
 * Deliberately not a PSR-7 request: the media type is not decided yet. A request states the
 * extensions and profiles it opts into and lets {@see Transport} compose them into `Accept`
 * and `Content-Type`, which is what keeps content negotiation in one place instead of spread
 * across every call site that happens to need the atomic extension.
 *
 * @phpstan-import-type FormFields from FormEncoding
 * @phpstan-import-type MultipartFields from FormEncoding
 */
final class Request
{
    /**
     * @param string                           $uri         a path relative to the base URL, or an
     *                                                      absolute URL (a page link is already one)
     * @param array<string, string>            $headers     headers for this request alone; they win
     *                                                      over both the negotiated defaults and the
     *                                                      client's header provider
     * @param list<string>                     $ext         extension URIs to negotiate
     * @param list<string>                     $profiles    profile URIs to negotiate
     * @param string|null                      $contentType overrides the negotiated `Content-Type`,
     *                                                      for a custom action sending a raw payload
     * @param string|null                      $accept      overrides the negotiated `Accept`
     */
    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        public readonly StreamInterface|string|null $body = null,
        public readonly array $headers = [],
        public readonly array $ext = [],
        public readonly array $profiles = [],
        public readonly ?string $contentType = null,
        public readonly ?string $accept = null,
    ) {}

    public static function get(string $uri): self
    {
        return new self('GET', $uri);
    }

    public static function post(string $uri, StreamInterface|string|null $body = null): self
    {
        return new self('POST', $uri, $body);
    }

    public static function patch(string $uri, StreamInterface|string|null $body = null): self
    {
        return new self('PATCH', $uri, $body);
    }

    public static function put(string $uri, StreamInterface|string|null $body = null): self
    {
        return new self('PUT', $uri, $body);
    }

    public static function delete(string $uri, StreamInterface|string|null $body = null): self
    {
        return new self('DELETE', $uri, $body);
    }

    /**
     * @param list<string> $ext
     */
    public function withExt(array $ext): self
    {
        return $this->copy(ext: $ext);
    }

    /**
     * @param list<string> $profiles
     */
    public function withProfiles(array $profiles): self
    {
        return $this->copy(profiles: $profiles);
    }

    public function withHeader(string $name, string $value): self
    {
        return $this->copy(headers: [...$this->headers, $name => $value]);
    }

    /**
     * Send this body verbatim under `$contentType`, bypassing JSON:API negotiation for the
     * request (the response is still negotiated).
     */
    public function withRawBody(StreamInterface|string $body, string $contentType): self
    {
        return $this->copy(body: $body, contentType: $contentType);
    }

    /**
     * Send `$fields` as a `multipart/form-data` body, under a boundary of its own unless one is
     * given. A {@see FilePart} goes as a file upload, anything else as a plain field.
     *
     * @param MultipartFields $fields
     */
    public function withMultipartBody(array $fields, ?string $boundary = null): self
    {
        $boundary ??= FormEncoding::boundary();

        return $this->withRawBody(FormEncoding::multipart($fields, $boundary), 'multipart/form-data; boundary=' . $boundary);
    }

    /**
     * Send `$fields` as an `application/x-www-form-urlencoded` body.
     *
     * @param FormFields $fields
     */
    public function withFormBody(array $fields): self
    {
        return $this->withRawBody(FormEncoding::urlencoded($fields), 'application/x-www-form-urlencoded');
    }

    /**
     * Ask for `$accept` instead of the negotiated JSON:API media type, for a custom action that can
     * answer with a raw body such as a CSV export or an image. An error still arrives as JSON:API.
     */
    public function withAccept(string $accept): self
    {
        return $this->copy(accept: $accept);
    }

    public function hasBody(): bool
    {
        return $this->body !== null;
    }

    /**
     * @param array<string, string>|null $headers
     * @param list<string>|null          $ext
     * @param list<string>|null          $profiles
     */
    private function copy(
        StreamInterface|string|null $body = null,
        ?array $headers = null,
        ?array $ext = null,
        ?array $profiles = null,
        ?string $contentType = null,
        ?string $accept = null,
    ): self {
        return new self(
            $this->method,
            $this->uri,
            $body ?? $this->body,
            $headers ?? $this->headers,
            $ext ?? $this->ext,
            $profiles ?? $this->profiles,
            $contentType ?? $this->contentType,
            $accept ?? $this->accept,
        );
    }
}
