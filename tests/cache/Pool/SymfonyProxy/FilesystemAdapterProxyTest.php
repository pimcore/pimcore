<?php

namespace Pimcore\Tests\Cache\Adapter\SymfonyProxy;

use Pimcore\Cache\Pool\CacheItem;
use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use Pimcore\Tests\Cache\Factory;
use Pimcore\Tests\Cache\Pool\SymfonyProxy\Traits\SymfonyProxyTestTrait;
use Pimcore\Tests\Cache\Pool\Traits\CacheItemPoolTestTrait;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Tests\Adapter\AdapterTestCase;

/**
 * @group cache.core.file
 */
class FilesystemAdapterProxyTest extends AdapterTestCase
{
    use SymfonyProxyTestTrait;
    use CacheItemPoolTestTrait {
        createCachePool as _createCachePool;
    }

    protected $skippedTests = [
        'testGetMetadata' => 'Metadata tags are not loaded for performance reasons.',
    ];

    public function createCachePool($defaultLifetime = 0)
    {
        $this->defaultLifetime = $defaultLifetime;

        return $this->_createCachePool();
    }

    public static function tearDownAfterClass(): void
    {
        self::rmdir(sys_get_temp_dir().'/symfony-cache');
    }

    public static function rmdir(string $dir)
    {
        if (!file_exists($dir)) {
            return;
        }
        if (!$dir || 0 !== strpos(\dirname($dir), sys_get_temp_dir())) {
            throw new \Exception(__METHOD__."() operates only on subdirs of system's temp dir");
        }
        $children = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($children as $child) {
            if ($child->isDir()) {
                rmdir($child);
            } else {
                unlink($child);
            }
        }
        rmdir($dir);
    }

    protected function isPruned(CacheItemPoolInterface $cache, string $name): bool
    {
        $getFileMethod = (new \ReflectionObject($cache))->getMethod('getFile');
        $getFileMethod->setAccessible(true);

        return !file_exists($getFileMethod->invoke($cache, $name));
    }

    /**
     * @return PimcoreCacheItemPoolInterface
     */
    protected function buildCachePool()
    {
        return (new Factory())->createFilesystemAdapterProxyItemPool($this->defaultLifetime);
    }

    public function testGet()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        /** @var SymfonyAdapterProxy $cache */
        $cache = $this->createCachePool();
        $cache->clear();

        $value = mt_rand();

        $this->assertSame($value, $cache->get('foo', function (CacheItem $item) use ($value) {
            $this->assertSame('foo', $item->getKey());

            return $value;
        }));

        $item = $cache->getItem('foo');
        $this->assertSame($value, $item->get());

        $isHit = true;
        $this->assertSame($value, $cache->get('foo', function (CacheItem $item) use (&$isHit) {
            $isHit = false;
        }, 0));
        $this->assertTrue($isHit);

        $this->assertNull($cache->get('foo', function (CacheItem $item) use (&$isHit, $value) {
            $isHit = false;
            $this->assertTrue($item->isHit());
            $this->assertSame($value, $item->get());
        }, INF));
        $this->assertFalse($isHit);
    }

    /**
     * No runInSeparateProcess
     * See: https://github.com/symfony/symfony/commit/85c50119f146e1c2e25738d6ac9f02b6cb05a471
     */
    public function testSavingObject()
    {
        parent::testSavingObject();
    }
}
