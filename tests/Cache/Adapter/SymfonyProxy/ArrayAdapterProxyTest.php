<?php

namespace Pimcore\Tests\Cache\Adapter\SymfonyProxy;

use Pimcore\Cache\Pool\CacheItem;
use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use Pimcore\Cache\Pool\SymfonyAdapterProxy;
use Pimcore\Tests\Cache\Factory;
use Pimcore\Tests\Cache\Pool\SymfonyProxy\Traits\SymfonyProxyTestTrait;
use Pimcore\Tests\Cache\Pool\Traits\CacheItemPoolTestTrait;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Tests\Adapter\AdapterTestCase;

/**
 * @group cache.core.array
 */
class ArrayAdapterProxyTest extends AdapterTestCase
{
    use SymfonyProxyTestTrait;
    use CacheItemPoolTestTrait {
        createCachePool as _createCachePool;
    }

    protected $skippedTests = [
        'testGetMetadata' => 'ArrayAdapter does not keep metadata.',
        'testDeferredSaveWithoutCommit' => 'Assumes a shared cache which ArrayAdapter is not.',
        'testSaveWithoutExpire' => 'Assumes a shared cache which ArrayAdapter is not.',
    ];

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
        $values = $adapter->getValues();

        $this->assertCount(2 * 2, $values); // value + tag = *2
        $this->assertArrayHasKey('foo', $values);
        $this->assertSame(serialize('4711'), $values['foo']);
        $this->assertArrayHasKey('bar', $values);
        $this->assertNull($values['bar']);
    }

    public function testGet()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        /** @var SymfonyAdapterProxy $cache */
        $cache = $this->createCachePool();
        $cache->clear();

        $value = mt_rand();

        $this->assertSame($value, $cache->get('foo', function (CacheItem $item) use ($value) {
            $this->assertSame('foo', $item->getKey());

            return $value;
        }));

        $item = $cache->getItem('foo');
        $this->assertSame($value, $item->get());

        $isHit = true;
        $this->assertSame($value, $cache->get('foo', function (CacheItem $item) use (&$isHit) {
            $isHit = false;
        }, 0));
        $this->assertTrue($isHit);

        $this->assertNull($cache->get('foo', function (CacheItem $item) use (&$isHit, $value) {
            $isHit = false;
            $this->assertTrue($item->isHit());
            $this->assertSame($value, $item->get());
        }, INF));
        $this->assertFalse($isHit);
    }

    /**
     * No runInSeparateProcess
     * See: https://github.com/symfony/symfony/commit/85c50119f146e1c2e25738d6ac9f02b6cb05a471
     */
    public function testSavingObject()
    {
        parent::testSavingObject();
    }
}
