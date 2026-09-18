<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * One inbound response, with the body already read.
 *
 * A PSR-7 body is a stream and a stream can be read once, so the transport reads it and hands
 * over a string. Everything downstream — the materialiser, the error parser, a test assertion
 * — can then look at the body as many times as it likes.
 */
final class Response
{
    /**
     * @param array<string, list<string>> $headers
     */
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        private readonly array $headers = [],
    ) {}

    public static function fromPsr(ResponseInterface $response): self
    {
        $headers = [];

        foreach ($response->getHeaders() as $name => $values) {
            $headers[$name] = \array_values($values);
        }

        return new self($response->getStatusCode(), (string) $response->getBody(), $headers);
    }

    /**
     * @return array<string, list<string>>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * The first value of a header, matched without regard to case as HTTP requires.
     */
    public function header(string $name): ?string
    {
        $wanted = \strtolower($name);

        foreach ($this->headers as $header => $values) {
            if (\strtolower($header) === $wanted) {
                return $values[0] ?? null;
            }
        }

        return null;
    }

    public function isSuccessful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    /**
     * False for a `204`, and for the empty body some servers send with a `200`.
     */
    public function hasBody(): bool
    {
        return $this->status !== 204 && \trim($this->body) !== '';
    }

    /**
     * The response's media type without its parameters — `application/vnd.api+json` for a
     * JSON:API response, whatever `ext` and `profile` it also carried.
     */
    public function mediaType(): ?string
    {
        $contentType = $this->header('Content-Type');

        if ($contentType === null) {
            return null;
        }

        $type = \strstr($contentType, ';', true);

        return \strtolower(\trim($type === false ? $contentType : $type));
    }
}
