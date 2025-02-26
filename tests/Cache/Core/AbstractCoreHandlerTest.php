<?php
declare(strict_types=1);

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

namespace Pimcore\Tests\Cache\Core;

use Codeception\Test\Unit;
use DateTime;
use InvalidArgumentException;
use Monolog\Handler\BufferHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit_Framework_MockObject_MockObject;
use Pimcore\Cache\Core\CoreCacheHandler;
use Pimcore\Cache\Core\WriteLock;
use Pimcore\Tests\Support\Helper\Pimcore;
use ReflectionClass;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;

abstract class AbstractCoreHandlerTest extends Unit
{
    protected TagAwareAdapterInterface $cache;

    protected CoreCacheHandler|PHPUnit_Framework_MockObject_MockObject $handler;

    protected WriteLock $writeLock;

    protected int $defaultLifetime = 2419200; // 28 days

    protected array $sampleEntries = [
        'A' => ['tag_a', 'tag_ab', 'tag_all'],
        'B' => ['tag_b', 'tag_ab', 'tag_bc', 'tag_all'],
        'C' => ['tag_c', 'tag_bc', 'tag_all'],
    ];

    protected static Logger $logger;

    /**
     * @var HandlerInterface[]
     */
    protected static array $logHandlers = [];

    protected function setUp(): void
    {
        $this->cache = $this->createCachePool();

        // make sure we start with a clean state
        $this->cache->clear();

        $this->writeLock = $this->createWriteLock();
        $this->handler = $this->createHandlerMock();
    }

    /**
     * Set up a logger with a buffer and a test handler (can be printed to STDOUT on demand)
     *
     */
    protected static function setupLogger(string $name): void
    {
        static::$logHandlers = [
            'buffer' => new BufferHandler(new StreamHandler('php://stdout')),
            'test' => new TestHandler(),
        ];

        static::$logger = new Logger($name, array_values(static::$logHandlers));
    }

    /**
     * Flush buffer handler if TEST_LOG env var is set
     */
    protected static function handleLogOutput(): void
    {
        /** @var BufferHandler $bufferHandler */
        $bufferHandler = static::$logHandlers['buffer'];
        if (!$bufferHandler) {
            return;
        }

        // call tests with TEST_LOG=1 if you need logs (e.g. during development)
        if ((bool)getenv('TEST_LOG')) {
            echo PHP_EOL;
            $bufferHandler->flush();
            echo PHP_EOL;
        } else {
            // just throw the logs away
            $bufferHandler->clear();
        }
    }

    /**
     * Initializes item pool
     *
     */
    abstract protected function createCachePool(): TagAwareAdapterInterface;

    protected function createWriteLock(): WriteLock
    {
        $writeLock = new WriteLock($this->cache);
        $writeLock->setLogger(static::$logger);

        return $writeLock;
    }

    protected function createHandlerMock(): PHPUnit_Framework_MockObject_MockObject|CoreCacheHandler
    {
        $mockMethods = ['isCli'];

        /** @var CoreCacheHandler|PHPUnit_Framework_MockObject_MockObject $handler */
        $handler = $this->getMockBuilder(CoreCacheHandler::class)
            ->setMethods($mockMethods)
            ->setConstructorArgs([
                $this->cache,
                $this->writeLock,
                \Pimcore::getEventDispatcher(),
            ])
            ->getMock();

        $handler->setLogger(static::$logger);

        $pimcoreModule = $this->getModule('\\' . Pimcore::class);

        // mock handler to work in normal (non-cli mode) besides in tests which
        // explicitely define the cache-cli group
        if (in_array('cache-cli', $pimcoreModule->getGroups())) {
            $handler->method('isCli')
                ->willReturn(true);
        } else {
            $handler->method('isCli')
                ->willReturn(false);
        }

        return $handler;
    }

    public static function setUpBeforeClass(): void
    {
        static::setupLogger((new ReflectionClass(__CLASS__))->getShortName());
    }

    public static function tearDownAfterClass(): void
    {
        static::handleLogOutput();
    }

    protected function getHandlerPropertyValue(string $property, CoreCacheHandler $handler = null): mixed
    {
        if (null === $handler) {
            $handler = $this->handler;
        }

        $reflector = new ReflectionClass($handler);
        $property = $reflector->getProperty($property);

        return $property->getValue($handler);
    }

    protected function cacheHasItem(string $key): bool
    {
        $item = $this->cache->getItem($key);

        return $item->isHit();
    }

    /**
     * Add sample entries to cache
     *
     */
    protected function buildSampleEntries(bool $write = true, bool $assertExisting = true): void
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

    public function testCacheIsEnabledByDefault(): void
    {
        $this->assertTrue($this->handler->isEnabled());
    }

    /**
     * Invalid keys is defined on abstract CachePool test
     *
     * @dataProvider invalidKeys
     *
     */
    public function testExceptionOnInvalidItemKeySave(string $key): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->handler->save($key, 'foo');
    }

    /**
     * Invalid keys is defined on abstract CachePool test
     *
     * @dataProvider invalidKeys
     *
     */
    public function testExceptionOnInvalidItemKeyRemove(string $key): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->handler->remove($key);
    }

    public function testLoadReturnsFalseOnMiss(): void
    {
        $this->assertFalse($this->handler->load('not_existing'));
    }

    public function testLoadReturnsUnserializedItem(): void
    {
        $timestamp = time();

        $date = new DateTime();
        $date->setTimestamp($timestamp);

        $this->handler->save('date', $date);
        $this->handler->writeSaveQueue();

        $this->assertTrue($this->cacheHasItem('date'));

        $fetchedDate = $this->handler->load('date');

        $this->assertInstanceOf(DateTime::class, $fetchedDate);
        $this->assertEquals($timestamp, $date->getTimestamp());
    }

    public function testGetItemIsCacheMiss(): void
    {
        /** @var CacheItem $item */
        $item = $this->handler->getItem('not_existing');

        $this->assertInstanceOf(CacheItem::class, $item);
        $this->assertFalse($item->isHit());
    }

    public function testDeferredWrite(): void
    {
        $this->handler->save('itemA', 'test');

        $this->assertFalse($this->cacheHasItem('itemA'));

        $this->handler->writeSaveQueue();

        $this->assertTrue($this->cacheHasItem('itemA'));
    }

    public function testWriteQueueIsWrittenOnShutdown(): void
    {
        $this->handler->save('itemA', 'test');

        $this->assertFalse($this->cacheHasItem('itemA'));

        $this->handler->shutdown();

        $this->assertTrue($this->cacheHasItem('itemA'));
    }

    public function testWriteQueueIsEmptyAfterSave(): void
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

    public function testImmediateWrite(): void
    {
        $this->handler->setForceImmediateWrite(true);
        $this->handler->save('itemA', 'test');

        $this->assertTrue($this->cacheHasItem('itemA'));
    }

    public function testImmediateWriteOnForce(): void
    {
        $this->handler->save('itemA', 'test', [], null, 0, true);

        $this->assertTrue($this->cacheHasItem('itemA'));
    }

    public function testWriteQueueDoesNotWriteMoreThanMaxItems(): void
    {
        $maxItems = $this->getHandlerPropertyValue('maxWriteToCacheItems');

        for ($i = 1; $i <= $maxItems; $i++) {
            $this->assertTrue($this->handler->save('item_' . $i, $i));
            $this->handler->cleanupQueue();

            $this->assertCount(
                $i,
                $this->getHandlerPropertyValue('saveQueue')
            );
        }

        $this->assertCount(
            $maxItems,
            $this->getHandlerPropertyValue('saveQueue')
        );

        $this->handler->save('additional_item', 'foo');
        $this->handler->cleanupQueue();

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

    public function testWriteQueueRespectsPriority(): void
    {
        $maxItems = $this->getHandlerPropertyValue('maxWriteToCacheItems');

        for ($i = 1; $i <= $maxItems; $i++) {
            $this->assertTrue($this->handler->save('item_' . $i, $i));
            $this->handler->cleanupQueue();

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
        $this->handler->cleanupQueue();

        $queue = $this->getHandlerPropertyValue('saveQueue');

        $this->assertArrayHasKey('additional_item', $queue);

        $this->assertCount(
            $maxItems,
            $this->getHandlerPropertyValue('saveQueue')
        );

        $this->handler->writeSaveQueue();
        $this->assertTrue($this->handler->getItem('additional_item')->isHit());
    }

    public function testNoWriteOnDisabledCache(): void
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
    public function testNoWriteInCliMode(): void
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
    public function testNoWriteInCliModeWithForceImmediateWrite(): void
    {
        $this->handler->setForceImmediateWrite(true);

        $this->assertFalse($this->cacheHasItem('itemA'));
        $this->assertFalse($this->handler->save('itemA', 'test'));
        $this->assertFalse($this->cacheHasItem('itemA'));
    }

    /**
     * @group cache-cli
     */
    public function testWriteWithForceInCliMode(): void
    {
        // force writes immediately - no need to write save queue
        $this->assertFalse($this->cacheHasItem('itemA'));
        $this->assertTrue($this->handler->save('itemA', 'test', [], null, 0, true));
        $this->assertTrue($this->cacheHasItem('itemA'));
    }

    /**
     * @group cache-cli
     */
    public function testWriteWithHandleCliOption(): void
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
    public function testWriteInCliModeWithHandleCiOptionAndForceImmediateWrite(): void
    {
        $this->handler->setHandleCli(true);
        $this->handler->setForceImmediateWrite(true);

        $this->assertFalse($this->cacheHasItem('itemA'));
        $this->assertTrue($this->handler->save('itemA', 'test'));
        $this->assertTrue($this->cacheHasItem('itemA'));
    }

    public function testRemove(): void
    {
        $this->handler->save('itemA', 'test');

        $this->assertFalse($this->cacheHasItem('itemA'));

        $this->handler->writeSaveQueue();

        $this->assertTrue($this->cacheHasItem('itemA'));

        $this->handler->remove('itemA');

        $this->assertFalse($this->cacheHasItem('itemA'));
    }

    public function testClearAll(): void
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

    public function tagEntriesProvider(): array
    {
        return [
            ['tag_a', ['A']],
            ['tag_b', ['B']],
            ['tag_c', ['C']],
            ['tag_ab', ['A', 'B']],
            ['tag_bc', ['B', 'C']],
            ['tag_all', ['A', 'B', 'C']],
        ];
    }

    public function tagsEntriesProvider(): array
    {
        return [
            [['tag_a', 'tag_b'], ['A', 'B']],
            [['tag_a', 'tag_c'], ['A', 'C']],
            [['tag_b', 'tag_c'], ['B', 'C']],
            [['tag_ab', 'tag_bc'], ['A', 'B', 'C']],
            [['tag_a', 'tag_bc'], ['A', 'B', 'C']],
            [['tag_c', 'tag_ab'], ['A', 'B', 'C']],
        ];
    }

    protected function runClearedTagEntryAssertions(array $expectedRemoveEntries): void
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
     */
    public function testClearTag(string $tag, array $expectedRemoveEntries): void
    {
        $this->buildSampleEntries();

        $this->handler->clearTag($tag);
        $this->runClearedTagEntryAssertions($expectedRemoveEntries);
    }

    /**
     * @dataProvider tagsEntriesProvider
     *
     * @skipped
     *
     */
    public function testClearTags(array $tags, array $expectedRemoveEntries): void
    {
        $this->buildSampleEntries();

        $this->handler->clearTags($tags);
        $this->runClearedTagEntryAssertions($expectedRemoveEntries);
    }

    public function testClearedTagIsAddedToClearedTagsList(): void
    {
        $this->assertEmpty($this->getHandlerPropertyValue('clearedTags'));

        $this->handler->clearTags(['tag_a', 'tag_b', 'output']);

        // output is shifted to shutdown tags (see next test)
        $this->assertEquals(['tag_a' => true, 'tag_b' => true], $this->getHandlerPropertyValue('clearedTags'));
    }

    public function testClearedTagIsShiftedToShutdownList(): void
    {
        $this->assertEmpty($this->getHandlerPropertyValue('tagsClearedOnShutdown'));

        $this->handler->clearTags(['tag_a', 'tag_b', 'output']);

        $this->assertEquals(['output'], $this->getHandlerPropertyValue('tagsClearedOnShutdown'));

        $this->handler->clearTagsOnShutdown();

        $this->assertEquals(['tag_a' => true, 'tag_b' => true, 'output' => true], $this->getHandlerPropertyValue('clearedTags'));
    }

    protected function handleShutdownTagListProcessing(bool $shutdown = false): void
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

        $this->assertEquals(['foo' => true], $this->getHandlerPropertyValue('clearedTags'));
    }

    public function testShutdownTagListIsProcessedOnMethodCall(): void
    {
        $this->handleShutdownTagListProcessing(false);
    }

    public function testShutdownTagListIsProcessedOnShutdown(): void
    {
        $this->handleShutdownTagListProcessing(true);
    }

    public function testForceCacheIsWrittenWhenWriteLockIsDisabled(): void
    {
        $this->handler->save('itemA', 'test', [], null, null, true);

        $this->assertTrue($this->cacheHasItem('itemA'));

        $this->writeLock->lock();
        $this->writeLock->disable();

        $this->handler->save('itemB', 'test', [], null, null, true);

        $this->assertTrue($this->cacheHasItem('itemB'));
    }

    public function testWriteLockIsSetOnRemove(): void
    {
        $this->assertFalse($this->writeLock->hasLock());

        $this->handler->remove('foo');

        $this->assertTrue($this->writeLock->hasLock());
    }

    public function testWriteLockIsSetOnClearTag(): void
    {
        $this->assertFalse($this->writeLock->hasLock());

        $this->handler->clearTag('foo');

        $this->assertTrue($this->writeLock->hasLock());
    }

    public function testWriteLockIsSetOnClearTags(): void
    {
        $this->assertFalse($this->writeLock->hasLock());

        $this->handler->clearTags(['foo']);

        $this->assertTrue($this->writeLock->hasLock());
    }

    public function testWriteLockIsSetOnClearAll(): void
    {
        $this->assertFalse($this->writeLock->hasLock());

        $this->handler->clearAll();

        $this->assertTrue($this->writeLock->hasLock());
    }

    public function testWriteLockIsSetWhenTagIsAddedForShutdownClear(): void
    {
        $this->assertFalse($this->writeLock->hasLock());

        $this->handler->addTagClearedOnShutdown('foo');

        $this->assertTrue($this->writeLock->hasLock());
    }

    public function testWriteLockIsRemovedOnShutdown(): void
    {
        $this->assertFalse($this->writeLock->hasLock());

        $this->handler->clearAll();

        $this->assertTrue($this->writeLock->hasLock());

        $this->handler->shutdown();

        $this->assertFalse($this->writeLock->hasLock());
    }

    /**
     * Data provider for invalid keys.
     *
     */
    public static function invalidKeys(): array
    {
        return [
            ['{str'],
            ['rand{'],
            ['rand{str'],
            ['rand}str'],
            ['rand(str'],
            ['rand)str'],
            ['rand/str'],
            ['rand\\str'],
            ['rand@str'],
            ['rand:str'],
        ];
    }
}
