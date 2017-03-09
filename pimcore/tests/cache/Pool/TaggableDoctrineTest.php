<?php

namespace Pimcore\Tests\Cache\Pool;

use Cache\IntegrationTests\TaggableCachePoolTest;
use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use Pimcore\Tests\Cache\Factory;
use Pimcore\Tests\Cache\Pool\Traits\CacheItemPoolTestTrait;

class TaggableDoctrineTest extends TaggableCachePoolTest
{
    use CacheItemPoolTestTrait;

    protected $skippedTests = [
        'testPreviousTag'              => 'Previous tags are not loaded for performance reasons.',
        'testPreviousTagDeferred'      => 'Previous tags are not loaded for performance reasons.',
        'testTagAccessorDuplicateTags' => 'Previous tags are not loaded for performance reasons.',
    ];

    /**
     * @return PimcoreCacheItemPoolInterface
     */
    protected function buildCachePool()
    {
        $db = getenv('TEST_MYSQL_DB');
        if (!$db) {
            $this->markTestSkipped('TEST_MYSQL_DB env var is not configured');
        }

        return (new Factory())->createDoctrineItemPool($this->defaultLifetime);
    }
}
