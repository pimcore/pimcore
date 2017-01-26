<?php

namespace TestSuite\Pimcore\Cache\Pool\Traits\SymfonyProxy;

use Pimcore\Cache\Pool\SymfonyAdapterProxyCacheItemPool;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use TestSuite\Pimcore\Cache\Pool\Traits\SymfonyProxyTestTrait;

trait ArrayAdapterTrait
{
    use SymfonyProxyTestTrait;

    /**
     * @return SymfonyAdapterProxyCacheItemPool that is used in the tests
     */
    public function createCachePool()
    {
        $arrayAdapter = new ArrayAdapter($this->defaultLifetime, false);
        $tagAdapter   = new TagAwareAdapter($arrayAdapter);

        $itemPool = new SymfonyAdapterProxyCacheItemPool($tagAdapter);
        $itemPool->setLogger(static::$logger);

        return $itemPool;
    }
}
