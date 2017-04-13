<?php

namespace Pimcore\Tests\Cache\Adapter\SymfonyProxy;

use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use Pimcore\Cache\Pool\SymfonyAdapterProxy;
use Pimcore\Tests\Cache\Factory;
use Pimcore\Tests\Cache\Pool\SymfonyProxy\Traits\SymfonyProxyTestTrait;
use Pimcore\Tests\Cache\Pool\Traits\CacheItemPoolTestTrait;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Tests\Adapter\ArrayAdapterTest;

/**
 * @group cache.core.array
 */
class ArrayAdapterProxyTest extends ArrayAdapterTest
{
    use SymfonyProxyTestTrait;
    use CacheItemPoolTestTrait {
        createCachePool as _createCachePool;
    }

    public function createCachePool($defaultLifetime = 0)
    {
        $this->defaultLifetime = $defaultLifetime;

        return $this->_createCachePool();
    }

    /**
     * @return PimcoreCacheItemPoolInterface
     */
    protected function buildCachePool()
    {
        return (new Factory())->createArrayAdapterProxyItemPool($this->defaultLifetime);
    }

    public function testGetValuesHitAndMiss()
    {
        /** @var SymfonyAdapterProxy $cache */
        $cache = $this->createCachePool();

        // Hit
        $item = $cache->getItem('foo');
        $item->set('4711');
        $cache->save($item);

        $fooItem = $cache->getItem('foo');
        $this->assertTrue($fooItem->isHit());
        $this->assertEquals('4711', $fooItem->get());

        // Miss (should be present as NULL in $values)
        $cache->getItem('bar');

        /** @var ArrayAdapter $adapter */
        $adapter = $this->getItemsAdapter($this->getTagAwareAdapter($cache));
        $values  = $adapter->getValues();

        $this->assertCount(2 * 2, $values); // value + tag = *2
        $this->assertArrayHasKey('foo', $values);
        $this->assertSame(serialize('4711'), $values['foo']);
        $this->assertArrayHasKey('bar', $values);
        $this->assertNull($values['bar']);
    }
}
