<?php

namespace TestSuite\Pimcore\Cache\Adapter;

use Cache\IntegrationTests\TaggableCachePoolTest;
use TestSuite\Pimcore\Cache\Pool\Traits\SymfonyProxy\ArrayAdapterTrait;

class TaggableArraySymfonyProxyCacheItemPoolTest extends TaggableCachePoolTest
{
    use ArrayAdapterTrait;

    protected $skippedTests = [
        'testPreviousTag'              => 'Previous tags are not loaded for performance reasons.',
        'testPreviousTagDeferred'      => 'Previous tags are not loaded for performance reasons.',
        'testTagAccessorDuplicateTags' => 'Previous tags are not loaded for performance reasons.',
    ];
}
