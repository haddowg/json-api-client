<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Http;

use Psr\Http\Message\StreamInterface;

/**
 * A file in a `multipart/form-data` body: its bytes, the filename the server sees, and the part's
 * own media type. A field without a filename is sent as a plain value, so a server that keeps
 * uploads apart from fields, as PHP's `$_FILES` does, needs this to tell them apart.
 */
final class FilePart
{
    public function __construct(
        public readonly StreamInterface|string $contents,
        public readonly string $filename,
        public readonly string $contentType = 'application/octet-stream',
    ) {}

    /**
     * The file's bytes, read from the start of a seekable stream.
     */
    public function bytes(): string
    {
        if (\is_string($this->contents)) {
            return $this->contents;
        }
        if ($this->contents->isSeekable()) {
            $this->contents->rewind();
        }

        return $this->contents->getContents();
    }
}
