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

use Pimcore\HttpKernel\Bundle\DependentBundleInterface;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Pimcore\HttpKernel\BundleCollection\Item;
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
            new BundleD,
        ];
    }

    public function testAddBundle()
    {
        foreach ($this->bundles as $bundle) {
            $this->collection->addBundle($bundle);
        }

        $this->assertEquals($this->bundles, $this->collection->getBundles('prod'));
    }

    public function testAddBundleAsString()
    {
        $identifiers = [];

        foreach ($this->bundles as $bundle) {
            $className = get_class($bundle);
            $identifiers[] = $className;

            $this->collection->addBundle($className);
        }

        $this->assertEquals($identifiers, $this->collection->getIdentifiers());
    }

    public function testAddBundles()
    {
        $this->collection->addBundles($this->bundles);

        $this->assertEquals($this->bundles, $this->collection->getBundles('prod'));
    }

    public function testAddBundlesAsString()
    {
        $identifiers = [];

        foreach ($this->bundles as $bundle) {
            $className = get_class($bundle);
            $identifiers[] = $className;
        }

        $this->collection->addBundles($identifiers);

        $this->assertEquals($identifiers, $this->collection->getIdentifiers());
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
            $bundles[2],
        ], $collection->getBundles('dev'));

        // test environment excludes the dev-only bundle
        $this->assertEquals([
            $bundles[0],
            $bundles[2],
            $bundles[3],
        ], $collection->getBundles('test'));
    }

    /**
     * @group only
     */
    public function testDependenciesAreRegistered()
    {
        $collection = new BundleCollection();
        $collection->addBundle(new BundleE());

        $this->assertEquals([
            BundleE::class,
            BundleF::class,
        ], $collection->getIdentifiers());

        $this->assertTrue($collection->hasItem(BundleE::class));
        $this->assertTrue($collection->hasItem(BundleF::class));
    }

    /**
     * @group only
     */
    public function testDependenciesOfDependenciesAreRegistered()
    {
        $collection = new BundleCollection();
        $collection->addBundle(new BundleI());

        $this->assertEquals([
            BundleI::class,
            BundleA::class,
            BundleB::class,
            BundleE::class,
            BundleF::class,
        ], $collection->getIdentifiers());

        $this->assertTrue($collection->hasItem(BundleA::class));
        $this->assertTrue($collection->hasItem(BundleB::class));
        $this->assertTrue($collection->hasItem(BundleE::class));
        $this->assertTrue($collection->hasItem(BundleF::class));
        $this->assertTrue($collection->hasItem(BundleI::class));
    }

    /**
     * @group only2
     */
    public function testDependentCircularReferencesAreIgnored()
    {
        $collection = new BundleCollection();

        // BundleH is now implicitely added and tries to add BundleG with prio 5
        $collection->addBundle(new BundleG, 10);

        $this->assertEquals([
            BundleG::class,
            BundleH::class,
        ], $collection->getIdentifiers());

        $this->assertTrue($collection->hasItem(BundleG::class));
        $this->assertTrue($collection->hasItem(BundleH::class));

        $this->assertEquals(10, $collection->getItem(BundleG::class)->getPriority());
        $this->assertEquals(8, $collection->getItem(BundleH::class)->getPriority());
    }

    /**
     * @group only2
     */
    public function testItemsAreNotOverwrittenByDependencies()
    {
        $collection = new BundleCollection();

        // add BundleH explicitely
        $collection->addBundle(new BundleH, 50);

        // BundleG tries to add BundleH, but it will be ignored as it is already registered
        $collection->addBundle(new BundleG, 10);

        // BundleJ will try to add BundleH again with prio 9
        $collection->addBundle(new BundleJ());

        $this->assertEquals([
            BundleH::class,
            BundleG::class,
            BundleJ::class,
        ], $collection->getIdentifiers());

        $this->assertTrue($collection->hasItem(BundleG::class));
        $this->assertTrue($collection->hasItem(BundleH::class));
        $this->assertTrue($collection->hasItem(BundleJ::class));

        $this->assertEquals(5, $collection->getItem(BundleG::class)->getPriority()); // as set in BundleH dependency
        $this->assertEquals(50, $collection->getItem(BundleH::class)->getPriority()); // as set here when adding the item
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

class BundleE extends Bundle implements DependentBundleInterface
{
    public static function registerDependentBundles(BundleCollection $collection)
    {
        $collection->addBundle(new BundleF());
    }
}

class BundleF extends Bundle
{
}

class BundleG extends Bundle implements DependentBundleInterface
{
    public static function registerDependentBundles(BundleCollection $collection)
    {
        $collection->addBundle(new BundleH, 8);
    }
}

class BundleH extends Bundle implements DependentBundleInterface
{
    public static function registerDependentBundles(BundleCollection $collection)
    {
        $collection->addBundle(new BundleG, 5);
    }
}

class BundleI extends Bundle implements DependentBundleInterface
{
    public static function registerDependentBundles(BundleCollection $collection)
    {
        $collection->addBundle(new BundleA);
        $collection->addBundle(new BundleB);
        $collection->addBundle(new BundleE);
    }
}

class BundleJ extends Bundle implements DependentBundleInterface
{
    public static function registerDependentBundles(BundleCollection $collection)
    {
        $collection->addBundle(new BundleH(), 9);
    }
}
