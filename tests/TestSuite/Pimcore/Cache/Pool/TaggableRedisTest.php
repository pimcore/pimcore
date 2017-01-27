<?php

namespace TestSuite\Pimcore\Cache\Pool;

use Cache\IntegrationTests\TaggableCachePoolTest;
use TestSuite\Pimcore\Cache\Pool\Traits\CacheItemPoolTestTrait;
use TestSuite\Pimcore\Cache\Pool\Traits\RedisItemPoolTrait;

/**
 * @group Redis
 */
class TaggableRedisTest extends TaggableCachePoolTest
{
    use CacheItemPoolTestTrait;
    use RedisItemPoolTrait;

    /**
     * @group redred
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
        // tag1: key, key2 <-- this is wrong

        $this->assertFalse($this->cache->hasItem('key'), 'Item should be cleared when tag is invalidated');
        $this->assertTrue($this->cache->hasItem('key2'), 'Item should be cleared when tag is invalidated');

        // Create a new item (no tags)
        $item = $this->cache->getItem('key')->set('value');
        $this->cache->save($item);

        // key: <no tags>
        // key2: tag1
        // -
        // tag1: key, key2 <-- this is wrong

        $this->cache->invalidateTags(['tag2']);
        $this->assertTrue($this->cache->hasItem('key'), 'Item key list should be removed when clearing the tags');

        // key: <no tags>
        // key2: tag1
        // -
        // tag1: key, key2 <-- this is wrong

        $this->cache->invalidateTags(['tag1']);
        $this->assertTrue($this->cache->hasItem('key'), 'Item key list should be removed when clearing the tags');

        // key is also removed as tag1 still points to key
        // <empty>
        // -
        // <empty>
    }
}
