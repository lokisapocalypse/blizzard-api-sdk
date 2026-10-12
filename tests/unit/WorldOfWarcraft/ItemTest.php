<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Region;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Item::class)]
class ItemTest extends ServiceTestCase
{
    /**
     * Checks that an item is fetched by id from the static namespace of the given region.
     *
     * @return void
     */
    public function testGetById(): void
    {
        $item = $this->serviceExpecting(
            Item::class,
            '/data/wow/item/19019',
            ['namespace' => 'static-kr'],
            Region::Kr,
            ['id' => 19019]
        );

        $this->assertSame(['id' => 19019], $item->getById(19019, Region::Kr));
    }

    /**
     * Checks that an item comes from the US when no region is given, with the locale.
     *
     * @return void
     */
    public function testGetByIdDefaultsToUs(): void
    {
        $item = $this->serviceExpecting(
            Item::class,
            '/data/wow/item/19019',
            ['namespace' => 'static-us', 'locale' => 'en_US'],
            Region::Us,
            ['id' => 19019],
            'en_US'
        );

        $this->assertSame(['id' => 19019], $item->getById(19019));
    }

    /**
     * Checks that an item's icon is fetched from the item media endpoint of the given region.
     *
     * @return void
     */
    public function testGetIcon(): void
    {
        $item = $this->serviceExpecting(
            Item::class,
            '/data/wow/media/item/19019',
            ['namespace' => 'static-eu'],
            Region::Eu,
            ['assets' => []]
        );

        $this->assertSame(['assets' => []], $item->getIcon(19019, Region::Eu));
    }

    /**
     * Checks that an item's icon comes from the US when no region is given.
     *
     * @return void
     */
    public function testGetIconDefaultsToUs(): void
    {
        $item = $this->serviceExpecting(
            Item::class,
            '/data/wow/media/item/19019',
            ['namespace' => 'static-us'],
            Region::Us,
            ['assets' => []]
        );

        $this->assertSame(['assets' => []], $item->getIcon(19019));
    }
}
