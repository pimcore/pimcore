<?php

namespace TestSuite\Pimcore\Cache\Adapter;

use Pimcore\Cache\Pool\SymfonyAdapterProxyCacheItemPool;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Tests\Adapter\ArrayAdapterTest;
use TestSuite\Pimcore\Cache\Pool\Traits\SymfonyProxy\ArrayAdapterTrait;

class ArraySymfonyProxyCacheItemPoolTest extends ArrayAdapterTest
{
    use ArrayAdapterTrait {
        createCachePool as _createCachePool;
    }

    public function createCachePool($defaultLifetime = 0)
    {
        $this->defaultLifetime = $defaultLifetime;
        return $this->_createCachePool();
    }

    public function testGetValuesHitAndMiss()
    {
        /** @var SymfonyAdapterProxyCacheItemPool $cache */
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
