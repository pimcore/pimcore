<?php

namespace Pimcore\Tests\Cache\Adapter\SymfonyProxy;

use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use Symfony\Component\Cache\Tests\Adapter\FilesystemAdapterTest;
use Pimcore\Tests\Cache\Factory;
use Pimcore\Tests\Cache\Pool\SymfonyProxy\Traits\SymfonyProxyTestTrait;
use Pimcore\Tests\Cache\Pool\Traits\CacheItemPoolTestTrait;

/**
 * @group cache.core.file
 */
class FilesystemAdapterProxyTest extends FilesystemAdapterTest
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
        return (new Factory())->createFilesystemAdapterProxyItemPool($this->defaultLifetime);
    }
}
