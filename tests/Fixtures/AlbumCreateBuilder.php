<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Fixtures;

use haddowg\JsonApiClient\Support\Conditionable;
use haddowg\JsonApiClient\Support\Missing;

/**
 * Stands in for a generated write builder, so the shared conditional-composition trait is
 * exercised through the shape codegen will actually produce.
 */
final class AlbumCreateBuilder
{
    use Conditionable;

    public string|Missing $title = Missing::Value;

    public string|Missing $status = Missing::Value;

    /**
     * @var list<string>
     */
    public array $applied = [];

    public function title(string $title): self
    {
        $this->title = $title;
        $this->applied[] = 'title';

        return $this;
    }

    public function status(string $status): self
    {
        $this->status = $status;
        $this->applied[] = 'status';

        return $this;
    }
}
