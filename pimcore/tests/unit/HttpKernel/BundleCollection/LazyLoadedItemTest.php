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

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\HttpKernel\Bundle\DependentBundleInterface;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Pimcore\HttpKernel\BundleCollection\LazyLoadedItem;
use Pimcore\Tests\Test\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class LazyLoadedItemTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        LazyLoadedItemTestBundleA::resetCounter();
        LazyLoadedItemTestBundleB::resetCounter();
    }

    public function testGetBundle()
    {
        $item = new LazyLoadedItem(LazyLoadedItemTestBundleA::class);

        $this->assertEquals(0, LazyLoadedItemTestBundleA::getCounter());

        $bundle = $item->getBundle();

        $this->assertEquals(1, LazyLoadedItemTestBundleA::getCounter());

        $item->getBundle();

        $this->assertEquals(1, LazyLoadedItemTestBundleA::getCounter());

        $this->assertInstanceOf(LazyLoadedItemTestBundleA::class, $bundle);
    }

    public function testGetBundleIdentifier()
    {
        $item = new LazyLoadedItem(LazyLoadedItemTestBundleA::class);

        $this->assertEquals(LazyLoadedItemTestBundleA::class, $item->getBundleIdentifier());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The class "FooBarBazingaDummyClassName" does not exist
     */
    public function testExceptionOnInvalidClass()
    {
        new LazyLoadedItem('FooBarBazingaDummyClassName');
    }

    public function testIsPimcoreBundle()
    {
        $itemA = new LazyLoadedItem(LazyLoadedItemTestBundleA::class);
        $itemB = new LazyLoadedItem(LazyLoadedItemTestBundleB::class);

        $this->assertEquals(0, LazyLoadedItemTestBundleA::getCounter());
        $this->assertEquals(0, LazyLoadedItemTestBundleB::getCounter());

        $this->assertFalse($itemA->isPimcoreBundle());
        $this->assertTrue($itemB->isPimcoreBundle());

        // item is not instantiated
        $this->assertEquals(0, LazyLoadedItemTestBundleA::getCounter());
        $this->assertEquals(0, LazyLoadedItemTestBundleB::getCounter());
    }

    public function testIsPimcoreBundleWithBundleInstance()
    {
        $itemA = new LazyLoadedItem(LazyLoadedItemTestBundleA::class);
        $itemB = new LazyLoadedItem(LazyLoadedItemTestBundleB::class);

        $this->assertEquals(0, LazyLoadedItemTestBundleA::getCounter());
        $this->assertEquals(0, LazyLoadedItemTestBundleB::getCounter());

        $itemA->getBundle();
        $itemB->getBundle();

        $this->assertEquals(1, LazyLoadedItemTestBundleA::getCounter());
        $this->assertEquals(1, LazyLoadedItemTestBundleB::getCounter());

        $this->assertFalse($itemA->isPimcoreBundle());
        $this->assertTrue($itemB->isPimcoreBundle());
    }

    public function testRegistersDependencies()
    {
        $collection = new BundleCollection();

        $item = new LazyLoadedItem(LazyLoadedItemTestBundleC::class);

        $collection->add($item);

        $this->assertEquals([
            LazyLoadedItemTestBundleC::class,
            LazyLoadedItemTestBundleA::class,
        ], $collection->getIdentifiers());
    }

    public function testRegistersDependenciesWithBundleInstance()
    {
        $collection = new BundleCollection();

        $item = new LazyLoadedItem(LazyLoadedItemTestBundleC::class);
        $item->getBundle();

        $collection->add($item);

        $this->assertEquals([
            LazyLoadedItemTestBundleC::class,
            LazyLoadedItemTestBundleA::class,
        ], $collection->getIdentifiers());
    }
}

class LazyLoadedItemTestBundleA extends Bundle
{
    /**
     * @var int
     */
    private static $counter = 0;

    public function __construct()
    {
        static::$counter++;
    }

    public static function resetCounter()
    {
        static::$counter = 0;
    }

    public static function getCounter(): int
    {
        return static::$counter;
    }
}

class LazyLoadedItemTestBundleB extends AbstractPimcoreBundle
{
    /**
     * @var int
     */
    private static $counter = 0;

    public function __construct()
    {
        static::$counter++;
    }

    public static function resetCounter()
    {
        static::$counter = 0;
    }

    public static function getCounter(): int
    {
        return static::$counter;
    }
}

class LazyLoadedItemTestBundleC extends Bundle implements DependentBundleInterface
{
    public static function registerDependentBundles(BundleCollection $collection)
    {
        $collection->add(new LazyLoadedItem(LazyLoadedItemTestBundleA::class));
    }
}
