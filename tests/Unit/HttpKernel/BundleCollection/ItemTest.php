<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Unit\HttpKernel\BundleCollection;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\HttpKernel\Bundle\DependentBundleInterface;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Pimcore\HttpKernel\BundleCollection\Item;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ItemTest extends TestCase
{
    public function testGetBundle(): void
    {
        $bundle = new ItemTestBundleA();
        $item = new Item(new ItemTestBundleA());

        $this->assertEquals($bundle, $item->getBundle());
    }

    public function testGetBundleIdentifier(): void
    {
        $item = new Item(new ItemTestBundleA());

        $this->assertEquals(ItemTestBundleA::class, $item->getBundleIdentifier());
    }

    public function testEmptyEnvironmentsMatchesAnyEnvironment(): void
    {
        $item = new Item(new ItemTestBundleA(), 0, []);
        foreach (['prod', 'dev', 'test'] as $environment) {
            $this->assertTrue($item->matchesEnvironment($environment));
        }
    }

    public function testItemMatchesEnvironment(): void
    {
        $item = new Item(new ItemTestBundleA(), 0, ['dev']);

        $this->assertTrue($item->matchesEnvironment('dev'));
        $this->assertFalse($item->matchesEnvironment('prod'));
        $this->assertFalse($item->matchesEnvironment('test'));
    }

    public function testItemWithMultipleEnvironments(): void
    {
        $item = new Item(new ItemTestBundleA(), 0, ['dev', 'test']);

        $this->assertTrue($item->matchesEnvironment('dev'));
        $this->assertTrue($item->matchesEnvironment('test'));
        $this->assertFalse($item->matchesEnvironment('prod'));
    }

    public function testIsPimcoreBundle(): void
    {
        $itemA = new Item(new ItemTestBundleA());
        $itemB = new Item(new ItemTestBundleB());

        $this->assertFalse($itemA->isPimcoreBundle());
        $this->assertTrue($itemB->isPimcoreBundle());
    }

    public function testRegistersDependencies(): void
    {
        $collection = new BundleCollection();

        $collection->add(new Item(new ItemTestBundleC()));

        $this->assertEquals([
            ItemTestBundleC::class,
            ItemTestBundleA::class,
        ], $collection->getIdentifiers());
    }
}

class ItemTestBundleA extends Bundle
{
}

class ItemTestBundleB extends AbstractPimcoreBundle
{
}

class ItemTestBundleC extends Bundle implements DependentBundleInterface
{
    public static function registerDependentBundles(BundleCollection $collection): void
    {
        $collection->add(new Item(new ItemTestBundleA()));
    }
}
