<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Collections;

use haddowg\JsonApiClient\Collections\ResourceCollection;
use haddowg\JsonApiClient\Tests\Fixtures\AlbumBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResourceCollection::class)]
#[UsesClass(AlbumBase::class)]
final class ResourceCollectionTest extends TestCase
{
    public function testItIsCountableAndIterable(): void
    {
        $collection = self::albums('1', '2', '3');

        self::assertCount(3, $collection);
        self::assertFalse($collection->isEmpty());

        $titles = [];
        foreach ($collection as $album) {
            $titles[] = $album->title;
        }

        self::assertSame(['Album 1', 'Album 2', 'Album 3'], $titles);
    }

    public function testItIsIndexableSoAMemberCanBeReachedDirectly(): void
    {
        $collection = self::albums('1', '2');

        self::assertSame('1', $collection[0]->id);
        self::assertSame('2', $collection[1]->id);
        self::assertTrue(isset($collection[1]));
        self::assertFalse(isset($collection[2]));
    }

    public function testReachingPastTheEndSaysHowManyMembersThereAre(): void
    {
        $collection = self::albums('1');

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('No member at index 7; the collection holds 1.');

        self::assertNotNull($collection[7]);
    }

    public function testItIsAReadResultAndCannotBeWrittenTo(): void
    {
        $collection = self::albums('1');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('A resource collection is a read result and cannot be modified.');

        $collection[0] = new AlbumBase('2', 'Album 2');
    }

    public function testAMemberCannotBeUnset(): void
    {
        $collection = self::albums('1');

        $this->expectException(\LogicException::class);

        unset($collection[0]);
    }

    public function testFirstAndLastAreNullOnAnEmptyCollection(): void
    {
        $empty = new ResourceCollection([]);

        self::assertTrue($empty->isEmpty());
        self::assertCount(0, $empty);
        self::assertNull($empty->first());
        self::assertNull($empty->last());
        self::assertSame([], $empty->all());
    }

    public function testFirstAndLastReachTheEnds(): void
    {
        $collection = self::albums('1', '2', '3');

        self::assertSame('1', $collection->first()?->id);
        self::assertSame('3', $collection->last()?->id);
    }

    public function testTheCollectionLevelMembersUseTheReservedUnderscore(): void
    {
        // A JSON:API member name can never start with `_`, so nothing an API names can collide.
        $collection = new ResourceCollection(
            [new AlbumBase('1', 'Album 1')],
            ['total' => 1],
            ['self' => '/albums'],
        );

        self::assertSame(['total' => 1], $collection->_meta());
        self::assertSame(['self' => '/albums'], $collection->_links());
    }

    public function testTheCollectionLevelMembersAreEmptyWhenTheResponseCarriedNone(): void
    {
        $collection = self::albums('1');

        self::assertSame([], $collection->_meta());
        self::assertSame([], $collection->_links());
    }

    /**
     * @return ResourceCollection<AlbumBase>
     */
    private static function albums(string ...$ids): ResourceCollection
    {
        return new ResourceCollection(
            \array_map(static fn(string $id): AlbumBase => new AlbumBase($id, 'Album ' . $id), \array_values($ids)),
        );
    }
}
