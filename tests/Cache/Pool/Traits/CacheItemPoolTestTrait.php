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

namespace Pimcore\Tests\Cache\Pool\Traits;

use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use Pimcore\Tests\Cache\Traits\LogHandlerTrait;
use Psr\Cache\CacheItemPoolInterface;

trait CacheItemPoolTestTrait
{
    use LogHandlerTrait;

    /**
     * @var int
     */
    protected $defaultLifetime = 0;

    /**
     * @var CacheItemPoolInterface
     */
    protected $cache;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::setupLogger((new \ReflectionClass(__CLASS__))->getShortName());
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        static::handleLogOutput();
    }

    /**
     * @return PimcoreCacheItemPoolInterface that is used in the tests
     */
    public function createCachePool()
    {
        $itemPool = $this->buildCachePool();
        $itemPool->setLogger(static::$logger);

        $this->cache = $itemPool;

        return $itemPool;
    }

    /**
     * @return PimcoreCacheItemPoolInterface
     */
    abstract protected function buildCachePool();
}
