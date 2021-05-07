<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Tests\Cache\Adapter\SymfonyProxy;

use PHPUnit\Framework\TestCase;
use Pimcore\Cache\Pool\SymfonyAdapterProxy;
use Pimcore\Tests\Cache\Pool\SymfonyProxy\Traits\SymfonyProxyTestTrait;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

class SymfonyProxyTest extends TestCase
{
    use SymfonyProxyTestTrait;

    public function testAdapterIsWrappedInTagAdapter()
    {
        $adapter = new ArrayAdapter();
        $proxyPool = new SymfonyAdapterProxy($adapter);

        $tagAdapter = $this->getTagAwareAdapter($proxyPool);
        $this->assertInstanceOf(TagAwareAdapterInterface::class, $tagAdapter);

        $itemsAdapter = $this->getItemsAdapter($tagAdapter);
        $this->assertEquals($adapter, $itemsAdapter);
    }

    public function testTagAdapterIsNotWrappedAgain()
    {
        $adapter = new ArrayAdapter();
        $tagAdapter = new TagAwareAdapter($adapter);
        $proxyPool = new SymfonyAdapterProxy($tagAdapter);

        $proxyTagAdapter = $this->getTagAwareAdapter($proxyPool);
        $this->assertEquals($tagAdapter, $proxyTagAdapter);

        $itemsAdapter = $this->getItemsAdapter($proxyTagAdapter);
        $this->assertEquals($adapter, $itemsAdapter);
    }
}
