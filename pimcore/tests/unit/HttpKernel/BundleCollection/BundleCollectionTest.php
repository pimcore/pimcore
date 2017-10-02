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

use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Pimcore\HttpKernel\BundleCollection\Item;
use Pimcore\HttpKernel\BundleCollection\LazyLoadedItem;
use Pimcore\Tests\Test\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class BundleCollectionTest extends TestCase
{
    /**
     * @var BundleCollection
     */
    private $collection;

    /**
     * @var BundleInterface[]
     */
    private $bundles;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->collection = new BundleCollection();

        $this->bundles = [
            new BundleA,
            new BundleB,
            new BundleC,
            new BundleD
        ];
    }

    public function testAddBundle()
    {
        foreach ($this->bundles as $bundle) {
            $this->collection->addBundle($bundle);
        }

        $this->assertEquals($this->bundles, $this->collection->getBundles('prod'));
    }

    public function testAddBundles()
    {
        $this->collection->addBundles($this->bundles);

        $this->assertEquals($this->bundles, $this->collection->getBundles('prod'));
    }

    public function testAddItem()
    {
        foreach ($this->bundles as $bundle) {
            $this->collection->add(new Item($bundle));
        }

        $this->assertEquals($this->bundles, $this->collection->getBundles('prod'));
    }

    public function testHasItem()
    {
        foreach ($this->bundles as $bundle) {
            $item = new Item($bundle);

            $this->assertFalse($this->collection->hasItem($item->getBundleIdentifier()));
            $this->collection->add($item);
            $this->assertTrue($this->collection->hasItem($item->getBundleIdentifier()));
        }
    }

    public function testGetItem()
    {
        foreach ($this->bundles as $bundle) {
            $item = new Item($bundle);

            $this->collection->add($item);
            $this->assertEquals($item, $this->collection->getItem($item->getBundleIdentifier()));
        }
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Bundle "Pimcore\Tests\Unit\HttpKernel\BundleCollection\BundleA" is not registered
     */
    public function testGetItemThrowsException()
    {
        $item = new Item($this->bundles[0]);

        $this->assertFalse($this->collection->hasItem($item->getBundleIdentifier()));
        $this->collection->getItem($item->getBundleIdentifier());
    }

    public function testGetItems()
    {
        $items = [];
        foreach ($this->bundles as $bundle) {
            $item = new Item($bundle);
            $items[] = $item;

            $this->collection->add($item);
        }

        $this->assertEquals($items, $this->collection->getItems());
    }

    public function testGetIdentifiers()
    {
        $identifiers = [];
        foreach ($this->bundles as $bundle) {
            $item = new Item($bundle);
            $identifiers[] = $item->getBundleIdentifier();

            $this->collection->add($item);
        }

        $this->assertEquals($identifiers, $this->collection->getIdentifiers());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Trying to register the bundle "Pimcore\Tests\Unit\HttpKernel\BundleCollection\BundleA" multiple times
     */
    public function testExceptionOnDoubleBundle()
    {
        $this->collection->addBundle($this->bundles[0]);
        $this->collection->addBundle($this->bundles[0]);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Trying to register the bundle "Pimcore\Tests\Unit\HttpKernel\BundleCollection\BundleA" multiple times
     */
    public function testExceptionOnDoubleBundleWithDifferentInstance()
    {
        $this->collection->addBundle(new BundleA);
        $this->collection->addBundle(new BundleA);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Trying to register the bundle "Pimcore\Tests\Unit\HttpKernel\BundleCollection\BundleA" multiple times
     */
    public function testExceptionOnDoubleBundleWithItem()
    {
        $this->collection->add(new Item($this->bundles[0]));
        $this->collection->add(new Item($this->bundles[0]));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Trying to register the bundle "Pimcore\Tests\Unit\HttpKernel\BundleCollection\BundleA" multiple times
     */
    public function testExceptionOnDoubleLazyLoadedBundle()
    {
        $bundleClass = get_class($this->bundles[0]);

        $this->collection->add(new LazyLoadedItem($bundleClass));
        $this->collection->add(new LazyLoadedItem($bundleClass));
    }

    public function testBundlesAreOrderedByPriority()
    {
        $collection = $this->collection;
        $bundles = $this->bundles;

        $collection->addBundle($bundles[0], 10);
        $collection->addBundle($bundles[1], 5);
        $collection->addBundle($bundles[2], -10);
        $collection->addBundle($bundles[3], 50);

        $result = $collection->getBundles('prod');

        $this->assertEquals($bundles[3], $result[0]);
        $this->assertEquals($bundles[0], $result[1]);
        $this->assertEquals($bundles[1], $result[2]);
        $this->assertEquals($bundles[2], $result[3]);
    }

    public function testBundlesAreFilteredByEnvironment()
    {
        $collection = $this->collection;

        $bundles = $this->bundles;
        $bundles[] = new BundleD;

        $collection->addBundle($bundles[0]); // this will always be loaded
        $collection->addBundle($bundles[1], 0, ['dev']);
        $collection->addBundle($bundles[2], 0, ['dev', 'test']);
        $collection->addBundle($bundles[3], 0, ['test']);

        // dev and test will be excluded
        $this->assertEquals([
            $bundles[0],
        ], $collection->getBundles('prod'));

        // dev environment excludes the test-only bundle
        $this->assertEquals([
            $bundles[0],
            $bundles[1],
            $bundles[2]
        ], $collection->getBundles('dev'));

        // test environment excludes the dev-only bundle
        $this->assertEquals([
            $bundles[0],
            $bundles[2],
            $bundles[3]
        ], $collection->getBundles('test'));
    }
}

class BundleA extends Bundle
{
}

class BundleB extends Bundle
{
}

class BundleC extends Bundle
{
}

class BundleD extends Bundle
{
}
