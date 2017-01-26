<?php

namespace TestSuite\Pimcore\Cache\Adapter;

use Cache\IntegrationTests\TaggableCachePoolTest;
use TestSuite\Pimcore\Cache\Pool\Traits\PdoMysqlCacheItemPoolTestTrait;

class TagPdoMysqlCacheItemPoolTest extends TaggableCachePoolTest
{
    use PdoMysqlCacheItemPoolTestTrait;

    protected $skippedTests = [
        'testPreviousTag'              => 'Previous tags are not loaded for performance reasons.',
        'testPreviousTagDeferred'      => 'Previous tags are not loaded for performance reasons.',
        'testTagAccessorDuplicateTags' => 'Previous tags are not loaded for performance reasons.',
    ];
}
