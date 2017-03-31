<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Cache\Pool;

use Pimcore\Cache\Pool\Exception\CacheException;
use Pimcore\Cache\Pool\Exception\InvalidArgumentException;

/**
 * Redis2 item pool with tagging and LUA support.
 *
 * TODO this currently handles tag clearing wrong as potentially orphaned tag entries can lead to items being purged
 * despite being invalid. See the TaggableRedisTest for annotations.
 *
 * Adapted from https://github.com/colinmollenhour/Cm_Cache_Backend_Redis and from Pimcore\Cache\Backend\Redis2
 */
class Redis extends AbstractCacheItemPool implements PurgeableCacheItemPoolInterface
{
    const SET_IDS = 'zc:ids';
    const SET_TAGS = 'zc:tags';

    const PREFIX_KEY = 'zc:k:';
    const PREFIX_TAG_IDS = 'zc:ti:';

    const FIELD_DATA = 'd';
    const FIELD_MTIME = 'm';
    const FIELD_TAGS = 't';
    const FIELD_INF = 'i';

    const MAX_LIFETIME = 2592000; // Redis backend limit
    const COMPRESS_PREFIX = ":\x1f\x8b";

    const LUA_SAVE_SH1 = '1617c9fb2bda7d790bb1aaa320c1099d81825e64';
    const LUA_CLEAN_SH1 = '42ab2fe548aee5ff540123687a2c39a38b54e4a2';
    const LUA_GC_SH1 = 'c00416b970f1aa6363b44965d4cf60ee99a6f065';

    /**
     * @var \Credis_Client
     */
    protected $redis;

    /**
     * @var bool
     */
    protected $notMatchingTags = false;

    /**
     * @var int
     */
    protected $compressTags = 1;

    /**
     * @var int
     */
    protected $compressData = 1;

    /**
     * @var int
     */
    protected $compressThreshold = 20480;

    /**
     * @var string
     */
    protected $compressionLib;

    /**
     * @var string
     */
    protected $compressPrefix;

    /**
     * @var bool
     */
    protected $useLua = false;

    /**
     * Lua's unpack() has a limit on the size of the table imposed by
     * the number of Lua stack slots that a C function can use.
     * This value is defined by LUAI_MAXCSTACK in luaconf.h and for Redis it is set to 8000.
     *
     * @see https://github.com/antirez/redis/blob/b903145/deps/lua/src/luaconf.h#L439
     * @var int
     */
    protected $luaMaxCStack = 5000;

    /**
     * @param \Credis_Client $redis
     * @param array $options
     * @param int $defaultLifetime
     */
    public function __construct(\Credis_Client $redis, $options = [], $defaultLifetime = 0)
    {
        parent::__construct($defaultLifetime);

        $this->redis = $redis;

        if (isset($options['notMatchingTags'])) {
            $this->notMatchingTags = (bool)$options['notMatchingTags'];
        }

        if (isset($options['compress_tags'])) {
            $this->compressTags = (int)$options['compress_tags'];
        }

        if (isset($options['compress_data'])) {
            $this->compressData = (int)$options['compress_data'];
        }

        if (isset($options['compress_threshold'])) {
            $this->compressThreshold = (int)$options['compress_threshold'];
        }

        if (isset($options['compression_lib'])) {
            $this->compressionLib = (string)$options['compression_lib'];
        } elseif (function_exists('snappy_compress')) {
            $this->compressionLib = 'snappy';
        } elseif (function_exists('lz4_compress')) {
            $this->compressionLib = 'l4z';
        } elseif (function_exists('lzf_compress')) {
            $this->compressionLib = 'lzf';
        } else {
            $this->compressionLib = 'gzip';
        }

        $this->compressPrefix = substr($this->compressionLib, 0, 2) . static::COMPRESS_PREFIX;

        if (isset($options['use_lua'])) {
            $this->useLua = (bool)$options['use_lua'];
        }

        if (isset($options['lua_max_c_stack'])) {
            $this->luaMaxCStack = (int)$options['lua_max_c_stack'];
        }
    }

    /**
     * Fetches several cache items.
     *
     * @param array $ids The cache identifiers to fetch
     *
     * @return array|\Traversable The corresponding values found in the cache
     */
    protected function doFetch(array $ids)
    {
        if (empty($ids)) {
            return;
        }

        $ids = array_values($ids);

        $pipeline = $this->redis->pipeline()->multi();

        $fields = [
            static::FIELD_DATA,
            static::FIELD_TAGS,
            static::FIELD_MTIME
        ];

        $fieldIndexes = array_flip($fields);

        foreach ($ids as $id) {
            $pipeline->hMGet(static::PREFIX_KEY . $id, [
                static::FIELD_DATA,
                static::FIELD_TAGS,
                static::FIELD_MTIME
            ]);
        }

        $result = $pipeline->exec();

        foreach ($result as $idx => $entry) {
            // we rely on mtime always being set
            if (empty($entry) || !isset($entry[$fieldIndexes[static::FIELD_MTIME]]) || !$entry[$fieldIndexes[static::FIELD_MTIME]]) {
                continue;
            }

            if (null === $entry[$fieldIndexes[static::FIELD_DATA]]) {
                continue;
            }

            $value = $this->decodeData($entry[$fieldIndexes[static::FIELD_DATA]]);
            $value = $this->unserializeData($value);

            $tags    = [];
            $tagData = $this->decodeData($entry[$fieldIndexes[static::FIELD_TAGS]]);

            if (!empty($tagData)) {
                $tags = explode(',', $tagData);
            }

            yield $ids[$idx] => [
                'value' => $value,
                'tags'  => $tags
            ];
        }
    }

    /**
     * Confirms if the cache contains specified cache item.
     *
     * @param string $id The identifier for which to check existence
     *
     * @return bool True if item exists in the cache, false otherwise
     */
    protected function doHave($id)
    {
        $result = $this->redis->exists(static::PREFIX_KEY . $id);

        return (bool)$result;
    }

    /**
     * Deletes all items in the pool.
     *
     * @param string $namespace The prefix used for all identifiers managed by this pool
     *
     * @return bool True if the pool was successfully cleared, false otherwise
     */
    protected function doClear($namespace)
    {
        return $this->redis->flushDb();
    }

    /**
     * Removes multiple items from the pool.
     *
     * @param array $ids An array of identifiers that should be removed from the pool
     *
     * @return bool True if the items were successfully removed, false otherwise
     */
    protected function doDelete(array $ids)
    {
        $totalResult = true;

        // TODO implement a better way for multiple items! (multi for whole set?)
        foreach ($ids as $id) {
            // Get list of tags for this id
            $tags = explode(',', $this->decodeData($this->redis->hGet(static::PREFIX_KEY . $id, static::FIELD_TAGS)));

            $this->redis->pipeline()->multi();

            // Remove data
            $this->redis->del(static::PREFIX_KEY . $id);

            // Remove id from list of all ids
            if ($this->notMatchingTags) {
                $this->redis->sRem(static::SET_IDS, $id);
            }

            // Update the id list for each tag
            foreach ($tags as $tag) {
                $this->redis->sRem(static::PREFIX_TAG_IDS . $tag, $id);
            }

            $result      = $this->redis->exec();
            $totalResult = $totalResult && count($result) > 0 && $result[0] !== false;
        }

        return $totalResult;
    }

    /**
     * Persists any deferred cache items.
     *
     * @return bool
     *   True if all not-yet-saved items were successfully saved or there were none. False otherwise.
     */
    public function commit()
    {
        if (empty($this->deferred)) {
            return true;
        }

        $result = true;

        // TODO implement a better way for multiple items!

        /** @var CacheItem $item */
        while ($item = array_shift($this->deferred)) {
            $result = $result && $this->commitItem($item);
        }

        return $result;
    }

    /**
     * @param PimcoreCacheItemInterface $item
     *
     * @return bool
     *
     * @throws CacheException
     */
    protected function commitItem(PimcoreCacheItemInterface $item)
    {
        $id  = $item->getKey();
        $now = time();

        $lifetime = null;
        $expiry   = $item->getExpiry();
        $data     = $this->serializeData($item->get());
        $tags     = $item->getTags();

        if ($expiry) {
            $lifetime = $expiry - $now;
        }

        $values = [
            static::FIELD_DATA  => $this->encodeData($data, $this->compressData),
            static::FIELD_TAGS  => $this->encodeData(implode(',', $tags), $this->compressTags),
            static::FIELD_MTIME => $now,
            static::FIELD_INF   => $lifetime ? 0 : 1,
        ];

        if ($this->useLua) {
            $sArgs = [
                static::PREFIX_KEY,
                static::FIELD_DATA,
                static::FIELD_TAGS,
                static::FIELD_MTIME,
                static::FIELD_INF,
                static::SET_TAGS,
                static::PREFIX_TAG_IDS,
                static::SET_IDS,
                $id,
                $values[static::FIELD_DATA],
                $values[static::FIELD_TAGS],
                $values[static::FIELD_MTIME],
                $values[static::FIELD_INF],
                min($lifetime, static::MAX_LIFETIME),
                $this->notMatchingTags ? 1 : 0
            ];

            $res = $this->redis->evalSha(static::LUA_SAVE_SH1, $tags, $sArgs);
            if (is_null($res)) {
                $script =
                    "local oldTags = redis.call('HGET', ARGV[1]..ARGV[9], ARGV[3]) " .
                    "redis.call('HMSET', ARGV[1]..ARGV[9], ARGV[2], ARGV[10], ARGV[3], ARGV[11], ARGV[4], ARGV[12], ARGV[5], ARGV[13]) " .
                    "if (ARGV[13] == '0') then " .
                    "redis.call('EXPIRE', ARGV[1]..ARGV[9], ARGV[14]) " .
                    "end " .
                    "if next(KEYS) ~= nil then " .
                    "redis.call('SADD', ARGV[6], unpack(KEYS)) " .
                    "for _, tagname in ipairs(KEYS) do " .
                    "redis.call('SADD', ARGV[7]..tagname, ARGV[9]) " .
                    "end " .
                    "end " .
                    "if (ARGV[15] == '1') then " .
                    "redis.call('SADD', ARGV[8], ARGV[9]) " .
                    "end " .
                    "if (oldTags ~= false) then " .
                    "return oldTags " .
                    "else " .
                    "return '' " .
                    "end";

                $res = $this->redis->eval($script, $tags, $sArgs);
            }

            // Process removed tags if cache entry already existed
            if ($res) {
                $oldTags = explode(',', $this->decodeData($res));
                if ($remTags = ($oldTags ? array_diff($oldTags, $tags) : false)) {
                    // Update the id list for each tag
                    foreach ($remTags as $tag) {
                        $this->redis->sRem(static::PREFIX_TAG_IDS . $tag, $id);
                    }
                }
            }

            return true;
        }

        // Get list of tags previously assigned
        $oldTags = $this->decodeData($this->redis->hGet(static::PREFIX_KEY . $id, static::FIELD_TAGS));
        $oldTags = $oldTags ? explode(',', $oldTags) : [];

        $this->redis->pipeline()->multi();

        // Set the data
        $result = $this->redis->hMSet(static::PREFIX_KEY . $id, $values);

        if (!$result) {
            throw new CacheException(sprintf('Could not set cache key %s', $id));
        }

        // Set expiration if specified
        if ($lifetime) {
            $this->redis->expire(static::PREFIX_KEY . $id, min($lifetime, static::MAX_LIFETIME));
        }

        // Process added tags
        if ($tags) {
            // Update the list with all the tags
            $this->redis->sAdd(static::SET_TAGS, $tags);

            // Update the id list for each tag
            foreach ($tags as $tag) {
                $this->redis->sAdd(static::PREFIX_TAG_IDS . $tag, $id);
            }
        }

        // Process removed tags
        if ($remTags = ($oldTags ? array_diff($oldTags, $tags) : false)) {
            // Update the id list for each tag
            foreach ($remTags as $tag) {
                $this->redis->sRem(static::PREFIX_TAG_IDS . $tag, $id);
            }
        }

        // Update the list with all the ids
        if ($this->notMatchingTags) {
            $this->redis->sAdd(static::SET_IDS, $id);
        }

        $result = $this->redis->exec();

        // TODO how to check success?
        return !empty($result);
    }

    /**
     * Invalidates cached items using tags.
     *
     * @param string[] $tags An array of tags to invalidate
     *
     * @throws \Psr\Cache\InvalidArgumentException When $tags is not valid
     *
     * @return bool True on success
     */
    protected function doInvalidateTags(array $tags)
    {
        if ($this->useLua) {
            $pTags = $this->preprocessTagIds($tags);
            $sArgs = [
                static::PREFIX_KEY,
                static::SET_TAGS,
                static::SET_IDS,
                ($this->notMatchingTags ? 1 : 0),
                (int)$this->luaMaxCStack
            ];

            if (!$this->redis->evalSha(static::LUA_CLEAN_SH1, $pTags, $sArgs)) {
                $script =
                    "for i = 1, #KEYS, ARGV[5] do " .
                    "local keysToDel = redis.call('SUNION', unpack(KEYS, i, math.min(#KEYS, i + ARGV[5] - 1))) " .
                    "for _, keyname in ipairs(keysToDel) do " .
                    "redis.call('DEL', ARGV[1]..keyname) " .
                    "if (ARGV[4] == '1') then " .
                    "redis.call('SREM', ARGV[3], keyname) " .
                    "end " .
                    "end " .
                    "redis.call('DEL', unpack(KEYS, i, math.min(#KEYS, i + ARGV[5] - 1))) " .
                    "redis.call('SREM', ARGV[2], unpack(KEYS, i, math.min(#KEYS, i + ARGV[5] - 1))) " .
                    "end " .
                    "return true";

                $this->redis->eval($script, $pTags, $sArgs);
            }

            return true;
        }

        $ids = $this->getIdsMatchingAnyTags($tags);

        $this->redis->pipeline()->multi();

        if ($ids) {
            // Remove data
            $this->redis->del($this->preprocessIds($ids));

            // Remove ids from list of all ids
            if ($this->notMatchingTags) {
                $this->redis->sRem(static::SET_IDS, $ids);
            }
        }

        // Remove tag id lists
        $this->redis->del($this->preprocessTagIds($tags));

        // Remove tags from list of tags
        $this->redis->sRem(static::SET_TAGS, $tags);

        $this->redis->exec();

        return true;
    }

    /**
     * Runs maintenance tasks which could take a long time. Should only be called from maintenance scripts.
     *
     * @return bool True on success
     */
    public function purge()
    {
        return $this->collectGarbage();
    }

    /**
     * Clean up tag id lists since as keys expire the ids remain in the tag id lists
     */
    protected function collectGarbage()
    {
        // Clean up expired keys from tag id set and global id set

        if ($this->useLua) {
            $sArgs = [static::PREFIX_KEY,
                static::SET_TAGS,
                static::SET_IDS,
                static::PREFIX_TAG_IDS,
                ($this->notMatchingTags ? 1 : 0)
            ];

            $allTags   = (array)$this->redis->sMembers(static::SET_TAGS);
            $tagsCount = count($allTags);
            $counter   = 0;
            $tagsBatch = [];

            foreach ($allTags as $tag) {
                $tagsBatch[] = $tag;
                $counter++;
                if (count($tagsBatch) == 10 || $counter == $tagsCount) {
                    if (!$this->redis->evalSha(static::LUA_GC_SH1, $tagsBatch, $sArgs)) {
                        $script =
                            "local tagKeys = {} " .
                            "local expired = {} " .
                            "local expiredCount = 0 " .
                            "local notExpiredCount = 0 " .
                            "for _, tagName in ipairs(KEYS) do " .
                            "tagKeys = redis.call('SMEMBERS', ARGV[4]..tagName) " .
                            "for __, keyName in ipairs(tagKeys) do " .
                            "if (redis.call('EXISTS', ARGV[1]..keyName) == 0) then " .
                            "expiredCount = expiredCount + 1 " .
                            "expired[expiredCount] = keyName " .
                            /* Redis Lua scripts have a hard limit of 8000 parameters per command */
                            "if (expiredCount == 7990) then " .
                            "redis.call('SREM', ARGV[4]..tagName, unpack(expired)) " .
                            "if (ARGV[5] == '1') then " .
                            "redis.call('SREM', ARGV[3], unpack(expired)) " .
                            "end " .
                            "expiredCount = 0 " .
                            "expired = {} " .
                            "end " .
                            "else " .
                            "notExpiredCount = notExpiredCount + 1 " .
                            "end " .
                            "end " .
                            "if (expiredCount > 0) then " .
                            "redis.call('SREM', ARGV[4]..tagName, unpack(expired)) " .
                            "if (ARGV[5] == '1') then " .
                            "redis.call('SREM', ARGV[3], unpack(expired)) " .
                            "end " .
                            "end " .
                            "if (notExpiredCount == 0) then " .
                            "redis.call ('DEL', ARGV[4]..tagName) " .
                            "redis.call ('SREM', ARGV[2], tagName) " .
                            "end " .
                            "expired = {} " .
                            "expiredCount = 0 " .
                            "notExpiredCount = 0 " .
                            "end " .
                            "return true";

                        $this->redis->eval($script, $tagsBatch, $sArgs);
                    }

                    $tagsBatch = [];

                    /* Give Redis some time to handle other requests */
                    usleep(20000);
                }
            }

            return true;
        }

        $exists = [];
        $tags   = (array)$this->redis->sMembers(static::SET_TAGS);

        foreach ($tags as $tag) {
            // Get list of expired ids for each tag
            $tagMembers    = $this->redis->sMembers(static::PREFIX_TAG_IDS . $tag);
            $numTagMembers = count($tagMembers);
            $expired       = [];
            $numExpired    = $numNotExpired = 0;

            if ($numTagMembers) {
                while ($id = array_pop($tagMembers)) {
                    if (!isset($exists[$id])) {
                        $exists[$id] = $this->redis->exists(static::PREFIX_KEY . $id);
                    }
                    if ($exists[$id]) {
                        $numNotExpired++;
                    } else {
                        $numExpired++;
                        $expired[] = $id;

                        // Remove incrementally to reduce memory usage
                        if (count($expired) % 100 == 0 && $numNotExpired > 0) {
                            $this->redis->sRem(static::PREFIX_TAG_IDS . $tag, $expired);
                            if ($this->notMatchingTags) { // Clean up expired ids from ids set
                                $this->redis->sRem(static::SET_IDS, $expired);
                            }
                            $expired = [];
                        }
                    }
                }
                if (!count($expired)) {
                    continue;
                }
            }

            // Remove empty tags or completely expired tags
            if ($numExpired == $numTagMembers) {
                $this->redis->del(static::PREFIX_TAG_IDS . $tag);
                $this->redis->sRem(static::SET_TAGS, $tag);
            } elseif (count($expired)) {
                // Clean up expired ids from tag ids set
                $this->redis->sRem(static::PREFIX_TAG_IDS . $tag, $expired);
                if ($this->notMatchingTags) { // Clean up expired ids from ids set
                    $this->redis->sRem(static::SET_IDS, $expired);
                }
            }

            unset($expired);
        }

        // Clean up global list of ids for ids with no tag
        if ($this->notMatchingTags) {
            // TODO
        }
    }

    /**
     * Return an array of stored cache ids which match any given tags
     *
     * In case of multiple tags, a logical OR is made between tags
     *
     * @param array $tags array of tags
     * @return array array of any matching cache ids (string)
     */
    protected function getIdsMatchingAnyTags($tags = [])
    {
        if ($tags) {
            return (array)$this->redis->sUnion($this->preprocessTagIds($tags));
        }

        return [];
    }

    /**
     * @param $item
     * @param $index
     * @param $prefix
     */
    protected function preprocess(&$item, $index, $prefix)
    {
        $item = $prefix . $item;
    }

    /**
     * @param $ids
     * @return array
     */
    protected function preprocessIds($ids)
    {
        array_walk($ids, [$this, 'preprocess'], static::PREFIX_KEY);

        return $ids;
    }

    /**
     * @param $tags
     * @return array
     */
    protected function preprocessTagIds($tags)
    {
        array_walk($tags, [$this, 'preprocess'], static::PREFIX_TAG_IDS);

        return $tags;
    }

    /**
     * @param string $data
     * @param int $level
     * @throws \CredisException
     * @return string
     */
    protected function encodeData($data, $level)
    {
        if ($this->compressionLib && $level && strlen($data) >= $this->compressThreshold) {
            switch ($this->compressionLib) {
                case 'snappy':
                    $data = snappy_compress($data);
                    break;
                case 'lzf':
                    $data = lzf_compress($data);
                    break;
                case 'l4z':
                    $data = lz4_compress($data, ($level > 1 ? true : false));
                    break;
                case 'gzip':
                    $data = gzcompress($data, $level);
                    break;
                default:
                    throw new \CredisException("Unrecognized 'compression_lib'.");
            }
            if (!$data) {
                throw new \CredisException("Could not compress cache data.");
            }

            return $this->compressPrefix . $data;
        }

        return $data;
    }

    /**
     * @param bool|string $data
     * @return string
     */
    protected function decodeData($data)
    {
        if (substr($data, 2, 3) == static::COMPRESS_PREFIX) {
            switch (substr($data, 0, 2)) {
                case 'sn':
                    return snappy_uncompress(substr($data, 5));
                case 'lz':
                    return lzf_decompress(substr($data, 5));
                case 'l4':
                    return lz4_uncompress(substr($data, 5));
                case 'gz':
                case 'zc':
                    return gzuncompress(substr($data, 5));
            }
        }

        return $data;
    }
}
