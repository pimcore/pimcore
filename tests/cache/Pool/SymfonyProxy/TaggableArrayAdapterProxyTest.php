<?php

namespace Pimcore\Tests\Cache\Adapter\SymfonyProxy;

use Cache\IntegrationTests\TaggableCachePoolTest;
use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use Pimcore\Tests\Cache\Factory;
use Pimcore\Tests\Cache\Pool\SymfonyProxy\Traits\SymfonyProxyTestTrait;
use Pimcore\Tests\Cache\Pool\Traits\CacheItemPoolTestTrait;

/**
 * @group cache.core.array
 */
class TaggableArrayAdapterProxyTest extends TaggableCachePoolTest
{
    use SymfonyProxyTestTrait;
    use CacheItemPoolTestTrait;

    protected $skippedTests = [
        'testPreviousTag' => 'Previous tags are not loaded for performance reasons.',
        'testPreviousTagDeferred' => 'Previous tags are not loaded for performance reasons.',
        'testTagAccessorDuplicateTags' => 'Previous tags are not loaded for performance reasons.',
    ];

    /**
     * @return PimcoreCacheItemPoolInterface
     */
    protected function buildCachePool()
    {
        return (new Factory())->createArrayAdapterProxyItemPool($this->defaultLifetime);
    }
}
