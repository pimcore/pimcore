<?php

namespace Pimcore\Tests\Cache\Adapter\SymfonyProxy;

use PHPUnit\Framework\TestCase;
use Pimcore\Cache\Pool\SymfonyAdapterProxy;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Pimcore\Tests\Cache\Pool\SymfonyProxy\Traits\SymfonyProxyTestTrait;

class SymfonyProxyTest extends TestCase
{
    use SymfonyProxyTestTrait;

    public function testAdapterIsWrappedInTagAdapter()
    {
        $adapter   = new ArrayAdapter();
        $proxyPool = new SymfonyAdapterProxy($adapter);

        $tagAdapter = $this->getTagAwareAdapter($proxyPool);
        $this->assertInstanceOf(TagAwareAdapterInterface::class, $tagAdapter);

        $itemsAdapter = $this->getItemsAdapter($tagAdapter);
        $this->assertEquals($adapter, $itemsAdapter);
    }

    public function testTagAdapterIsNotWrappedAgain()
    {
        $adapter    = new ArrayAdapter();
        $tagAdapter = new TagAwareAdapter($adapter);
        $proxyPool  = new SymfonyAdapterProxy($tagAdapter);

        $proxyTagAdapter = $this->getTagAwareAdapter($proxyPool);
        $this->assertEquals($tagAdapter, $proxyTagAdapter);

        $itemsAdapter = $this->getItemsAdapter($proxyTagAdapter);
        $this->assertEquals($adapter, $itemsAdapter);
    }
}
