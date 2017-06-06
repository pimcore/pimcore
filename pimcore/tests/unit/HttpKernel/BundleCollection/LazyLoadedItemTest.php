<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tests\Unit\HttpKernel\BundleCollection;

use Pimcore\HttpKernel\BundleCollection\LazyLoadedItem;
use Pimcore\Tests\Test\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class LazyLoadedItemTest extends TestCase
{
    public function testGetBundle()
    {
        $item = new LazyLoadedItem(LazyLoadedItemTestBundle::class);

        $this->assertEquals(0, LazyLoadedItemTestBundle::getCounter());

        $bundle = $item->getBundle();

        $this->assertEquals(1, LazyLoadedItemTestBundle::getCounter());

        $item->getBundle();

        $this->assertEquals(1, LazyLoadedItemTestBundle::getCounter());

        $this->assertInstanceOf(LazyLoadedItemTestBundle::class, $bundle);
    }

    public function testGetBundleIdentifier()
    {
        $item = new LazyLoadedItem(LazyLoadedItemTestBundle::class);

        $this->assertEquals(LazyLoadedItemTestBundle::class, $item->getBundleIdentifier());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The class "FooBarBazingaDummyClassName" does not exist
     */
    public function testExceptionOnInvalidClass()
    {
        $item = new LazyLoadedItem('FooBarBazingaDummyClassName');
        $item->getBundle();
    }
}

class LazyLoadedItemTestBundle extends Bundle
{
    /**
     * @var int
     */
    private static $counter = 0;

    public function __construct()
    {
        static::$counter++;
    }

    public static function getCounter(): int
    {
        return static::$counter;
    }
}
