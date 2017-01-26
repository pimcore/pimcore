<?php

namespace TestSuite\Pimcore\Cache\Adapter;

use Cache\IntegrationTests\TaggableCachePoolTest;
use Pimcore\Cache\Pool\SymfonyAdapterProxyCacheItemPool;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use TestSuite\Pimcore\Cache\Pool\Traits\SymfonyProxyTestTrait;

class TagArraySymfonyProxyCacheItemPoolTest extends TaggableCachePoolTest
{
    use SymfonyProxyTestTrait;

    protected $skippedTests = [
        'testPreviousTag'              => 'Previous tags are not loaded for performance reasons.',
        'testPreviousTagDeferred'      => 'Previous tags are not loaded for performance reasons.',
        'testTagAccessorDuplicateTags' => 'Previous tags are not loaded for performance reasons.',
    ];

    /**
     * @return CacheItemPoolInterface that is used in the tests
     */
    public function createCachePool($defaultLifetime = 0)
    {
        $arrayAdapter = new ArrayAdapter($defaultLifetime, false);
        $tagAdapter   = new TagAwareAdapter($arrayAdapter);

        return new SymfonyAdapterProxyCacheItemPool($tagAdapter);
    }
}
