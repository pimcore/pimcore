<?php

namespace TestSuite\Pimcore\Cache\Adapter;

use Cache\IntegrationTests\CachePoolTest;
use Pimcore\Cache\Pool\SymfonyAdapterProxyCacheItemPool;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

class ArraySymfonyProxyCacheItemPoolTest extends CachePoolTest
{
    /**
     * @return CacheItemPoolInterface that is used in the tests
     */
    public function createCachePool()
    {
        $arrayAdapter = new ArrayAdapter(3600, false);
        $tagAdapter   = new TagAwareAdapter($arrayAdapter);

        return new SymfonyAdapterProxyCacheItemPool($tagAdapter, 3600);
    }
}
