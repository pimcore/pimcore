<?php

namespace TestSuite\Pimcore\Cache\Pool\Traits\SymfonyProxy;

use Pimcore\Cache\Pool\SymfonyAdapterProxyCacheItemPool;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use TestSuite\Pimcore\Cache\Pool\Traits\SymfonyProxyTestTrait;

trait FilesystemAdapterTrait
{
    use SymfonyProxyTestTrait;

    /**
     * @return SymfonyAdapterProxyCacheItemPool that is used in the tests
     */
    public function createCachePool()
    {
        $filesystemAdapter = new FilesystemAdapter('', $this->defaultLifetime);
        $tagAdapter        = new TagAwareAdapter($filesystemAdapter);

        $itemPool = new SymfonyAdapterProxyCacheItemPool($tagAdapter);
        $itemPool->setLogger(static::$logger);

        return $itemPool;
    }
}
