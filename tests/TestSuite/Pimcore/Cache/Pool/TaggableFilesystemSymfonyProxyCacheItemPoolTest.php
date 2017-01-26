<?php

namespace TestSuite\Pimcore\Cache\Adapter;

use Cache\IntegrationTests\TaggableCachePoolTest;
use TestSuite\Pimcore\Cache\Pool\Traits\SymfonyProxy\FilesystemAdapterTrait;

class TaggableFilesystemSymfonyProxyCacheItemPoolTest extends TaggableCachePoolTest
{
    use FilesystemAdapterTrait;

    protected $skippedTests = [
        'testPreviousTag'              => 'Previous tags are not loaded for performance reasons.',
        'testPreviousTagDeferred'      => 'Previous tags are not loaded for performance reasons.',
        'testTagAccessorDuplicateTags' => 'Previous tags are not loaded for performance reasons.',
    ];
}
