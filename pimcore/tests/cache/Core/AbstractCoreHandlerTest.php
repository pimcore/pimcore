<?php

namespace Pimcore\Tests\Cache\Core;

use PHPUnit\Framework\TestCase;
use Pimcore\Cache\Core\CoreHandler;
use Pimcore\Cache\Core\CoreHandlerInterface;
use Pimcore\Cache\Core\WriteLock;
use Pimcore\Cache\Core\WriteLockInterface;
use Pimcore\Cache\Pool\AbstractCacheItemPool;
use Pimcore\Cache\Pool\PimcoreCacheItemInterface;
use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use Pimcore\Tests\Cache\Traits\LogHandlerTrait;

abstract class AbstractCoreHandlerTest extends TestCase
{
    use LogHandlerTrait;

    /**
     * @var PimcoreCacheItemPoolInterface|AbstractCacheItemPool
     */
    protected $cache;

    /**
     * @var CoreHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $handler;

    /**
     * @var WriteLockInterface
     */
    protected $writeLock;

    /**
     * @var int
     */
    protected $defaultLifetime = 2419200; // 28 days

    /**
     * @var array
     */
    protected $sampleEntries = [
        'A' => ['tag_a', 'tag_ab', 'tag_all'],
        'B' => ['tag_b', 'tag_ab', 'tag_bc', 'tag_all'],
        'C' => ['tag_c', 'tag_bc', 'tag_all']
    ];

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->cache = $this->createCachePool();
        $this->cache->setLogger(static::$logger);

        // make sure we start with a clean state
        $this->cache->clear();

        $this->writeLock = $this->createWriteLock();
        $this->handler = $this->createHandlerMock();
    }

    /**
     * Initializes item pool
     *
     * @return PimcoreCacheItemPoolInterface
     */
    abstract protected function createCachePool();

    /**
     * @return WriteLock
     */
    protected function createWriteLock()
    {
        $writeLock = new WriteLock($this->cache);
        $writeLock->setLogger(static::$logger);

        return $writeLock;
    }

    /**
     * @return CoreHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createHandlerMock()
    {
        $mockMethods = ['isCli'];

        // allow to define additional handler mock methods via custom mockHandlerMethods annotation
        $annotations = $this->getAnnotations();
        if (isset($annotations['method']) && isset($annotations['method']['mockHandlerMethods'])) {
            $mockMethods = array_merge(
                $mockMethods,
                explode(',', $annotations['method']['mockHandlerMethods'][0])
            );
        }

        /** @var CoreHandler|\PHPUnit_Framework_MockObject_MockObject $handler */
        $handler = $this->getMockBuilder(CoreHandler::class)
            ->setMethods($mockMethods)
            ->setConstructorArgs([
                $this->cache,
                $this->writeLock
            ])
            ->getMock();

        $handler->setLogger(static::$logger);

        // mock handler to work in normal (non-cli mode) besides in tests which
        // explicitely define the cache-cli group
        if (in_array('cache-cli', $this->getGroups())) {
            $handler->method('isCli')
                ->willReturn(true);
        } else {
            $handler->method('isCli')
                ->willReturn(false);
        }

        return $handler;
    }

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass()
    {
        static::setupLogger((new \ReflectionClass(__CLASS__))->getShortName());
    }

    /**
     * @inheritDoc
     */
    public static function tearDownAfterClass()
    {
        static::handleLogOutput();
    }

    /**
     * @param string $property
     * @param CoreHandlerInterface $handler
     * @return mixed
     */
    protected function getHandlerPropertyValue($property, CoreHandlerInterface $handler = null)
    {
        if (null === $handler) {
            $handler = $this->handler;
        }

        $reflector = new \ReflectionClass($handler);

        $property = $reflector->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($handler);
    }

    /**
     * @param string $key
     * @return bool
     */
    protected function cacheHasItem($key)
    {
        $item = $this->cache->getItem($key);

        return $item->isHit();
    }

    /**
     * Add sample entries to cache
     *
     * @param bool $write
     * @param bool $assertExisting
     */
    protected function buildSampleEntries($write = true, $assertExisting = true)
    {
        foreach ($this->sampleEntries as $key => $tags) {
            $this->handler->save($key, 'test', $tags);
        }

        if ($write) {
            $this->handler->writeSaveQueue();

            if ($assertExisting) {
                foreach (array_keys($this->sampleEntries) as $key) {
                    $this->assertTrue($this->cacheHasItem($key));
                }
            }
        }
    }

    public function testCacheIsEnabledByDefault()
    {
        $this->assertTrue($this->handler->isEnabled());
    }

    public function testLoadReturnsFalseOnMiss()
    {
        $this->assertFalse($this->handler->load('not_existing'));
    }

    public function testLoadReturnsUnserializedItem()
    {
        $timestamp = time();

        $date = new \DateTime();
        $date->setTimestamp($timestamp);

        $this->handler->save('date', $date);
        $this->handler->writeSaveQueue();

        $this->assertTrue($this->cacheHasItem('date'));

        $fetchedDate = $this->handler->load('date');

        $this->assertInstanceOf(\DateTime::class, $fetchedDate);
        $this->assertEquals($timestamp, $date->getTimestamp());
    }

    public function testGetItemIsCacheMiss()
    {
        /** @var PimcoreCacheItemInterface $item */
        $item = $this->handler->getItem('not_existing');

        $this->assertInstanceOf(PimcoreCacheItemInterface::class, $item);
        $this->assertFalse($item->isHit());
    }

    public function testDeferredWrite()
    {
        $this->handler->save('itemA', 'test');

        $this->assertFalse($this->cacheHasItem('itemA'));

        $this->handler->writeSaveQueue();

        $this->assertTrue($this->cacheHasItem('itemA'));
    }

    public function testWriteQueueIsWrittenOnShutdown()
    {
        $this->handler->save('itemA', 'test');

        $this->assertFalse($this->cacheHasItem('itemA'));

        $this->handler->shutdown();

        $this->assertTrue($this->cacheHasItem('itemA'));
    }

    public function testWriteQueueIsEmptyAfterSave()
    {
        $this->buildSampleEntries(false, false);

        $this->assertCount(
            count($this->sampleEntries),
            $this->getHandlerPropertyValue('saveQueue')
        );

        $this->handler->writeSaveQueue();

        $this->assertCount(
            0,
            $this->getHandlerPropertyValue('saveQueue')
        );
    }

    public function testImmediateWrite()
    {
        $this->handler->setForceImmediateWrite(true);
        $this->handler->save('itemA', 'test');

        $this->assertTrue($this->cacheHasItem('itemA'));
    }

    public function testImmediateWriteOnForce()
    {
        $this->handler->save('itemA', 'test', [], null, 0, true);

        $this->assertTrue($this->cacheHasItem('itemA'));
    }

    public function testWriteQueueDoesNotWriteMoreThanMaxItems()
    {
        $maxItems = $this->getHandlerPropertyValue('maxWriteToCacheItems');

        for ($i = 1; $i <= $maxItems; $i++) {
            $this->assertTrue($this->handler->save('item_' . $i, $i));

            $this->assertCount(
                $i,
                $this->getHandlerPropertyValue('saveQueue')
            );
        }

        $this->assertCount(
            $maxItems,
            $this->getHandlerPropertyValue('saveQueue')
        );

        $this->assertFalse($this->handler->save('additional_item', 'foo'));

        $queue = $this->getHandlerPropertyValue('saveQueue');
        for ($i = 1; $i <= $maxItems; $i++) {
            $this->assertArrayHasKey('item_' . $i, $queue);
        }

        $this->assertArrayNotHasKey('additional_item', $queue);

        $this->assertCount(
            $maxItems,
            $this->getHandlerPropertyValue('saveQueue')
        );

        $this->handler->writeSaveQueue();

        for ($i = 1; $i <= $maxItems; $i++) {
            $this->assertTrue($this->handler->getItem('item_' . $i)->isHit());
        }
    }

    public function testWriteQueueRespectsPriority()
    {
        $maxItems = $this->getHandlerPropertyValue('maxWriteToCacheItems');

        for ($i = 1; $i <= $maxItems; $i++) {
            $this->assertTrue($this->handler->save('item_' . $i, $i));

            $this->assertCount(
                $i,
                $this->getHandlerPropertyValue('saveQueue')
            );
        }

        $this->assertCount(
            $maxItems,
            $this->getHandlerPropertyValue('saveQueue')
        );

        $this->assertTrue($this->handler->save('additional_item', 'foo', [], null, 10));

        $queue = $this->getHandlerPropertyValue('saveQueue');

        $this->assertArrayHasKey('additional_item', $queue);

        $this->assertCount(
            $maxItems,
            $this->getHandlerPropertyValue('saveQueue')
        );

        $this->handler->writeSaveQueue();
        $this->assertTrue($this->handler->getItem('additional_item')->isHit());
    }

    public function testNoWriteOnDisabledCache()
    {
        $this->handler->setForceImmediateWrite(true);

        // save the item to the enabled cache and check it was added to the cache
        $this->assertFalse($this->cacheHasItem('item_before'));

        $this->assertTrue(
            $this->handler->save('item_before', 'test', ['before', 'generic'])
        );

        $this->assertTrue($this->cacheHasItem('item_before'));

        $this->handler->disable();
        $this->assertFalse($this->handler->isEnabled());

        // check cache has still the before item
        $this->assertTrue($this->cacheHasItem('item_before'));

        // check new item is not in cache yet
        $this->assertFalse($this->cacheHasItem('item_after'));

        $this->assertFalse(
            $this->handler->save('item_after', 'test', ['after', 'generic'])
        );

        // expect the item not being saved to the cache
        $this->assertFalse($this->cacheHasItem('item_after'));
    }

    /**
     * @group cache-cli
     */
    public function testNoWriteInCliMode()
    {
        $this->assertFalse($this->cacheHasItem('itemA'));
        $this->assertFalse($this->handler->save('itemA', 'test'));

        $this->assertFalse($this->cacheHasItem('itemA'));
        $this->handler->writeSaveQueue();
        $this->assertFalse($this->cacheHasItem('itemA'));
    }

    /**
     * @group cache-cli
     */
    public function testNoWriteInCliModeWithForceImmediateWrite()
    {
        $this->handler->setForceImmediateWrite(true);

        $this->assertFalse($this->cacheHasItem('itemA'));
        $this->assertFalse($this->handler->save('itemA', 'test'));
        $this->assertFalse($this->cacheHasItem('itemA'));
    }

    /**
     * @group cache-cli
     */
    public function testWriteWithForceInCliMode()
    {
        // force writes immediately - no need to write save queue
        $this->assertFalse($this->cacheHasItem('itemA'));
        $this->assertTrue($this->handler->save('itemA', 'test', [], null, 0, true));
        $this->assertTrue($this->cacheHasItem('itemA'));
    }

    /**
     * @group cache-cli
     */
    public function testWriteWithHandleCliOption()
    {
        $this->handler->setHandleCli(true);

        $this->assertFalse($this->cacheHasItem('itemA'));
        $this->assertTrue($this->handler->save('itemA', 'test'));

        $this->assertFalse($this->cacheHasItem('itemA'));
        $this->handler->writeSaveQueue();
        $this->assertTrue($this->cacheHasItem('itemA'));
    }

    /**
     * @group cache-cli
     */
    public function testWriteInCliModeWithHandleCiOptionAndForceImmediateWrite()
    {
        $this->handler->setHandleCli(true);
        $this->handler->setForceImmediateWrite(true);

        $this->assertFalse($this->cacheHasItem('itemA'));
        $this->assertTrue($this->handler->save('itemA', 'test'));
        $this->assertTrue($this->cacheHasItem('itemA'));
    }

    /**
     * @group cache-cli
     * @mockHandlerMethods writeSaveQueue
     */
    public function testNoWriteInCliShutdown()
    {
        // expect that writeSaveQueue is never called
        $this->handler
            ->expects($this->never())
            ->method('writeSaveQueue');

        // enable cli to allow queueing the item
        $this->handler->setHandleCli(true);
        $this->assertTrue($this->handler->save('itemA', 'test'));
        $this->handler->setHandleCli(false);

        $this->handler->shutdown();
    }

    /**
     * @group cache-cli
     * @mockHandlerMethods writeSaveQueue
     */
    public function testWriteInCliShutdownWithHandleCliOption()
    {
        // expect writeSaveQueue to be called on shutdown
        $this->handler
            ->expects($this->once())
            ->method('writeSaveQueue');

        // enable cli to allow queueing the item
        $this->handler->setHandleCli(true);
        $this->assertTrue($this->handler->save('itemA', 'test'));

        $this->handler->shutdown();
    }

    public function testRemove()
    {
        $this->handler->save('itemA', 'test');

        $this->assertFalse($this->cacheHasItem('itemA'));

        $this->handler->writeSaveQueue();

        $this->assertTrue($this->cacheHasItem('itemA'));

        $this->handler->remove('itemA');

        $this->assertFalse($this->cacheHasItem('itemA'));
    }

    public function testClearAll()
    {
        foreach (array_keys($this->sampleEntries) as $key) {
            $this->assertFalse($this->cacheHasItem($key));
        }

        $this->buildSampleEntries(false, false);

        $this->handler->writeSaveQueue();

        foreach (array_keys($this->sampleEntries) as $key) {
            $this->assertTrue($this->cacheHasItem($key));
        }

        $this->handler->clearAll();

        foreach (array_keys($this->sampleEntries) as $key) {
            $this->assertFalse($this->cacheHasItem($key));
        }
    }

    public function tagEntriesProvider()
    {
        return [
            ['tag_a', ['A']],
            ['tag_b', ['B']],
            ['tag_c', ['C']],
            ['tag_ab', ['A', 'B']],
            ['tag_bc', ['B', 'C']],
            ['tag_all', ['A', 'B', 'C']]
        ];
    }

    public function tagsEntriesProvider()
    {
        return array_merge($this->tagEntriesProvider(), [
            [['tag_a', 'tag_b'], ['A', 'B']],
            [['tag_a', 'tag_c'], ['A', 'C']],
            [['tag_b', 'tag_c'], ['B', 'C']],
            [['tag_ab', 'tag_bc'], ['A', 'B', 'C']],
            [['tag_a', 'tag_bc'], ['A', 'B', 'C']],
            [['tag_c', 'tag_ab'], ['A', 'B', 'C']],
        ]);
    }

    protected function runClearedTagEntryAssertions(array $expectedRemoveEntries)
    {
        $allEntries = ['A', 'B', 'C'];

        foreach ($allEntries as $entry) {
            $assertion = !in_array($entry, $expectedRemoveEntries);
            $this->assertEquals($assertion, $this->cacheHasItem($entry));
        }
    }

    /**
     * @dataProvider tagEntriesProvider
     *
     * @param string $tag
     * @param array $expectedRemoveEntries
     */
    public function testClearTag($tag, array $expectedRemoveEntries)
    {
        $this->buildSampleEntries();

        $this->handler->clearTag($tag);
        $this->runClearedTagEntryAssertions($expectedRemoveEntries);
    }

    /**
     * @dataProvider tagsEntriesProvider
     * @skipped
     *
     * @param array $tags
     * @param array $expectedRemoveEntries
     */
    public function testClearTags($tags, array $expectedRemoveEntries)
    {
        $this->buildSampleEntries();

        if (!is_array($tags)) {
            $tags = [$tags];
        }

        $this->handler->clearTags($tags);
        $this->runClearedTagEntryAssertions($expectedRemoveEntries);
    }

    public function testClearedTagIsAddedToClearedTagsList()
    {
        $this->assertEmpty($this->getHandlerPropertyValue('clearedTags'));

        $this->handler->clearTags(['tag_a', 'tag_b', 'output']);

        // output is shifted to shutdown tags (see next test)
        $this->assertEquals(['tag_a', 'tag_b'], $this->getHandlerPropertyValue('clearedTags'));
    }

    public function testClearedTagIsShiftedToShutdownList()
    {
        $this->assertEmpty($this->getHandlerPropertyValue('tagsClearedOnShutdown'));

        $this->handler->clearTags(['tag_a', 'tag_b', 'output']);

        $this->assertEquals(['output'], $this->getHandlerPropertyValue('tagsClearedOnShutdown'));

        $this->handler->clearTagsOnShutdown();

        $this->assertEquals(['tag_a', 'tag_b', 'output'], $this->getHandlerPropertyValue('clearedTags'));
    }

    protected function handleShutdownTagListProcessing($shutdown = false)
    {
        $this->assertEmpty($this->getHandlerPropertyValue('clearedTags'));
        $this->assertEmpty($this->getHandlerPropertyValue('tagsClearedOnShutdown'));

        $this->handler->addTagClearedOnShutdown('foo');
        $this->assertEquals(['foo'], $this->getHandlerPropertyValue('tagsClearedOnShutdown'));

        // call shutdown which in turn should call the clear tags method or call the method directly
        if ($shutdown) {
             $this->handler->shutdown();
        } else {
            $this->handler->clearTagsOnShutdown();
        }

        $this->assertEquals(['foo'], $this->getHandlerPropertyValue('clearedTags'));
    }

    public function testShutdownTagListIsProcessedOnMethodCall()
    {
        $this->handleShutdownTagListProcessing(false);
    }

    public function testShutdownTagListIsProcessedOnShutdown()
    {
        $this->handleShutdownTagListProcessing(true);
    }

    public function testWriteLockIsSetOnRemove()
    {
        $this->assertFalse($this->writeLock->hasLock());

        $this->handler->remove('foo');

        $this->assertTrue($this->writeLock->hasLock());
    }

    public function testWriteLockIsSetOnClearTag()
    {
        $this->assertFalse($this->writeLock->hasLock());

        $this->handler->clearTag('foo');

        $this->assertTrue($this->writeLock->hasLock());
    }

    public function testWriteLockIsSetOnClearTags()
    {
        $this->assertFalse($this->writeLock->hasLock());

        $this->handler->clearTags(['foo']);

        $this->assertTrue($this->writeLock->hasLock());
    }

    public function testWriteLockIsSetOnClearAll()
    {
        $this->assertFalse($this->writeLock->hasLock());

        $this->handler->clearAll();

        $this->assertTrue($this->writeLock->hasLock());
    }

    public function testWriteLockIsSetWhenTagIsAddedForShutdownClear()
    {
        $this->assertFalse($this->writeLock->hasLock());

        $this->handler->addTagClearedOnShutdown('foo');

        $this->assertTrue($this->writeLock->hasLock());
    }

    public function testWriteLockIsRemovedOnShutdown()
    {
        $this->assertFalse($this->writeLock->hasLock());

        $this->handler->clearAll();

        $this->assertTrue($this->writeLock->hasLock());

        $this->handler->shutdown();

        $this->assertFalse($this->writeLock->hasLock());
    }
}
