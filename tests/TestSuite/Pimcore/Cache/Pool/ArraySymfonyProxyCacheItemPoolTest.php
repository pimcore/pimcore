<?php

namespace TestSuite\Pimcore\Cache\Adapter;

use Pimcore\Cache\Pool\SymfonyAdapterProxyCacheItemPool;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Tests\Adapter\ArrayAdapterTest;

class ArraySymfonyProxyCacheItemPoolTest extends ArrayAdapterTest
{
    /**
     * @return CacheItemPoolInterface that is used in the tests
     */
    public function createCachePool($defaultLifetime = 0)
    {
        $arrayAdapter = new ArrayAdapter($defaultLifetime, false);
        $tagAdapter   = new TagAwareAdapter($arrayAdapter);

        return new SymfonyAdapterProxyCacheItemPool($tagAdapter);
    }

    /**
     * Extracts core symfony adapter from item pool and TagAwareAdapter
     *
     * @param SymfonyAdapterProxyCacheItemPool $itemPool
     * @return ArrayAdapter
     */
    protected function getAdapter(SymfonyAdapterProxyCacheItemPool $itemPool)
    {
        $poolReflector = new \ReflectionClass($itemPool);

        $adapterProperty  = $poolReflector->getProperty('adapter');
        $adapterProperty->setAccessible(true);

        $tagAwareAdapter = $adapterProperty->getValue($itemPool);

        $tagAwareReflector = new \ReflectionClass($tagAwareAdapter);

        $itemsAdapterProperty = $tagAwareReflector->getProperty('itemsAdapter');
        $itemsAdapterProperty->setAccessible(true);

        return $itemsAdapterProperty->getValue($tagAwareAdapter);
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
        $adapter = $this->getAdapter($cache);
        $values  = $adapter->getValues();

        $this->assertCount(2 * 2, $values); // value + tag = *2
        $this->assertArrayHasKey('foo', $values);
        $this->assertSame(serialize('4711'), $values['foo']);
        $this->assertArrayHasKey('bar', $values);
        $this->assertNull($values['bar']);
    }
}
