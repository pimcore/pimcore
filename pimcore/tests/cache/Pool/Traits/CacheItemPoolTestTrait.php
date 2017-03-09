<?php

namespace Pimcore\Tests\Cache\Pool\Traits;

use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use Pimcore\Tests\Cache\Traits\LogHandlerTrait;

trait CacheItemPoolTestTrait
{
    use LogHandlerTrait;

    /**
     * @var int
     */
    protected $defaultLifetime = 0;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::setupLogger((new \ReflectionClass(__CLASS__))->getShortName());
    }

    public static function tearDownAfterClass()
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

        return $itemPool;
    }

    /**
     * @return PimcoreCacheItemPoolInterface
     */
    abstract protected function buildCachePool();
}
