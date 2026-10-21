<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Resources;

use haddowg\JsonApiClient\Exceptions\MalformedDocument;
use haddowg\JsonApiClient\Resources\ResourceObject;
use haddowg\JsonApiClient\Tests\Fixtures\Widget;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResourceObject::class)]
final class ResourceObjectReadersTest extends TestCase
{
    public function testNullableScalarsReadTheirTypeOrNull(): void
    {
        $widget = Widget::with(['count' => 3, 'none' => null, 'active' => false]);

        self::assertSame(3, $widget->nullableInt('count'));
        self::assertNull($widget->nullableInt('none'));
        self::assertFalse($widget->nullableBool('active'));
        self::assertNull($widget->nullableBool('none'));
    }

    public function testANullableScalarOfTheWrongTypeIsMalformed(): void
    {
        $this->expectException(MalformedDocument::class);

        Widget::with(['count' => '3'])->nullableInt('count');
    }

    public function testListsArriveUnnarrowedForGeneratedCodeToGuard(): void
    {
        $widget = Widget::with(['sizes' => [1, 2.5, 'three'], 'none' => null]);

        self::assertSame([1, 2.5, 'three'], $widget->list('sizes'));
        self::assertNull($widget->nullableList('none'));
    }

    public function testAnObjectIsNotAList(): void
    {
        $this->expectException(MalformedDocument::class);

        Widget::with(['sizes' => ['a' => 1]])->list('sizes');
    }

    public function testANonNullableListRejectsNull(): void
    {
        $this->expectException(MalformedDocument::class);

        Widget::with(['sizes' => null])->list('sizes');
    }

    public function testAnEmptyObjectIsAnObjectWithNoMembers(): void
    {
        // `{}` decodes to `[]`, which is also an empty list.
        $widget = Widget::with(['dimensions' => [], 'none' => null]);

        self::assertSame([], $widget->map('dimensions'));
        self::assertSame([], $widget->nullableMap('dimensions'));
        self::assertNull($widget->nullableMap('none'));
    }

    public function testANonEmptyListIsNotAnObject(): void
    {
        $this->expectException(MalformedDocument::class);

        Widget::with(['dimensions' => [1, 2]])->nullableMap('dimensions');
    }

    public function testANonNullableMapRejectsNull(): void
    {
        $this->expectException(MalformedDocument::class);

        Widget::with(['dimensions' => null])->map('dimensions');
    }
}
