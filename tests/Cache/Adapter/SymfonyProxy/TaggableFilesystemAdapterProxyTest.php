<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Tests\Cache\Adapter\SymfonyProxy;

use Cache\IntegrationTests\TaggableCachePoolTest;
use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use Pimcore\Tests\Cache\Factory;
use Pimcore\Tests\Cache\Pool\SymfonyProxy\Traits\SymfonyProxyTestTrait;
use Pimcore\Tests\Cache\Pool\Traits\CacheItemPoolTestTrait;

/**
 * @group cache.core.file
 */
class TaggableFilesystemAdapterProxyTest extends TaggableCachePoolTest
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
        return (new Factory())->createFilesystemAdapterProxyItemPool($this->defaultLifetime);
    }
}
