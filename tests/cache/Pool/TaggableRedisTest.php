<?php

namespace Pimcore\Tests\Cache\Pool;

use Cache\IntegrationTests\TaggableCachePoolTest;
use Pimcore\Cache\Pool\Redis;
use Pimcore\Tests\Cache\Pool\Traits\CacheItemPoolTestTrait;
use Pimcore\Tests\Cache\Pool\Traits\RedisItemPoolTrait;

/**
 * @group cache.core.redis
 */
class TaggableRedisTest extends TaggableCachePoolTest
{
    use CacheItemPoolTestTrait;
    use RedisItemPoolTrait;

    protected $skippedTests = [
        'testInvalidateTag' => 'Invalidate tags is currently not working properly on Redis.',
    ];

    /**
     * The redis pool does not clear all tag -> item relations properly, resulting in added items potentially having
     * tags which were set in earlier writes as the tag -> cacheItem relation is not cleared properly. See comments below.
     */
    public function testInvalidateTag()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('key')->set('value');
        $item->setTags(['tag1', 'tag2']);
        $this->cache->save($item);

        $item = $this->cache->getItem('key2')->set('value');
        $item->setTags(['tag1']);
        $this->cache->save($item);

        // key: tag1, tag2
        // key2: tag1
        // -
        // tag1: key, key2
        // tag2: key

        $this->cache->invalidateTag('tag2');

        // key2: tag1
        // -
        // tag1: key, key2 <-- this is wrong, key shouldn't be in the list anymore

        $this->assertFalse($this->cache->hasItem('key'), 'Item should be cleared when tag is invalidated');
        $this->assertTrue($this->cache->hasItem('key2'), 'Item should be cleared when tag is invalidated');

        // Create a new item (no tags)
        $item = $this->cache->getItem('key')->set('value');
        $this->cache->save($item);

        // key: <no tags>
        // key2: tag1
        // -
        // tag1: key, key2 <-- this is wrong, key shouldn't be in the list anymore

        $this->cache->invalidateTags(['tag2']);
        $this->assertTrue($this->cache->hasItem('key'), 'Item key list should be removed when clearing the tags');

        // key: <no tags>
        // key2: tag1
        // -
        // tag1: key, key2 <-- this is wrong, key shouldn't be in the list anymore

        $this->cache->invalidateTags(['tag1']);

        // key is also removed as tag1 still points to key
        // <empty>
        // -
        // <empty>

        // the following fails as the cache pool behaves wrong
        // $this->assertTrue($this->cache->hasItem('key'), 'Item key list should be removed when clearing the tags');
    }

    public function testInvalidateTagsAreClearedFromSet()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        /** @var Redis $cache */
        $cache = $this->cache;
        $connection = $this->getRedisConnection($cache);

        $this->assertEmpty($connection->sMembers(Redis::SET_TAGS));

        $foo = $cache->getItem('foo');
        $foo->setTags(['A1']);

        $bar = $cache->getItem('bar');
        $bar->setTags(['A1', 'A2']);

        $baz = $cache->getItem('baz');
        $baz->setTags(['A2', 'A3']);

        $cache->save($foo);
        $cache->save($bar);
        $cache->save($baz);

        $this->assertArraysAreSameIgnoringOrder(['A1', 'A2', 'A3'], $connection->sMembers(Redis::SET_TAGS));

        $this->assertTrue($cache->getItem('foo')->isHit());
        $this->assertTrue($cache->getItem('bar')->isHit());
        $this->assertTrue($cache->getItem('baz')->isHit());

        $cache->invalidateTag('A1');

        $this->assertFalse($cache->getItem('foo')->isHit());
        $this->assertFalse($cache->getItem('bar')->isHit());
        $this->assertTrue($cache->getItem('baz')->isHit());

        $this->assertArraysAreSameIgnoringOrder(['A2', 'A3'], $connection->sMembers(Redis::SET_TAGS));

        $cache->invalidateTags(['A2', 'A3']);

        $this->assertFalse($cache->getItem('foo')->isHit());
        $this->assertFalse($cache->getItem('bar')->isHit());
        $this->assertFalse($cache->getItem('baz')->isHit());

        $this->assertEmpty($connection->sMembers(Redis::SET_TAGS));
    }

    private function assertArraysAreSameIgnoringOrder(array $expected, array $test)
    {
        sort($expected);
        sort($test);

        $this->assertEquals($expected, $test);
    }
}
