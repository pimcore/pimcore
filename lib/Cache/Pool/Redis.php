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

/**
 * Redis2 item pool with tagging and LUA support.
 *
 * WARNING: LUA mode is only working on standalone modes as it violates Redis EVAL semantics of passing every used key
 * in the KEYS argument when loading tags from an item and building tag item IDs inside the script.
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
    protected $useLua = true;

    /**
     * Lua's unpack() has a limit on the size of the table imposed by
     * the number of Lua stack slots that a C function can use.
     * This value is defined by LUAI_MAXCSTACK in luaconf.h and for Redis it is set to 8000.
     *
     * @see https://github.com/antirez/redis/blob/b903145/deps/lua/src/luaconf.h#L439
     *
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
            static::FIELD_MTIME,
        ];

        foreach ($ids as $id) {
            $pipeline->hMGet(static::PREFIX_KEY . $id, $fields);
        }

        $result = $pipeline->exec();

        foreach ($result as $idx => $entry) {
            if (empty($entry)) {
                continue;
            }

            // we rely on mtime always being set
            if (!isset($entry[static::FIELD_MTIME]) || !$entry[static::FIELD_MTIME]) {
                continue;
            }

            if (null === $entry[static::FIELD_DATA]) {
                continue;
            }

            $value = $this->decodeData($entry[static::FIELD_DATA]);
            $value = $this->unserializeData($value);

            $tags = [];
            $tagData = $entry[static::FIELD_TAGS];

            if (!empty($tagData)) {
                $tags = explode(',', $tagData);
            }

            yield $ids[$idx] => [
                'value' => $value,
                'tags' => $tags,
            ];
        }
    }

    /**
     * Maps response fields indexed by numeric index to an array with values indexed
     * by field name. This is only used when the redis extension is not used as the extension
     * already returns the expected format.
     *
     * @param array $entry
     * @param array $fields
     *
     * @return array
     */
    private function mapResponseIndexes(array $entry, array $fields): array
    {
        $result = [];
        foreach ($fields as $index => $fieldName) {
            if (isset($entry[$index])) {
                $result[$fieldName] = $entry[$index];
            }
        }

        return $result;
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
        if ($this->useLua) {
            $staticKeys = [
                self::SET_IDS,
            ];

            $args = [
                count($staticKeys), // 1
                ($this->notMatchingTags ? 1 : 0), // 2
            ];

            $keys = $this->preprocessIds($ids);
            $keys = array_merge($staticKeys, $keys);

            $script = <<<'LUA'
local setIds = KEYS[1]

local staticKeyCount = ARGV[1]
local notMatchingTags = ARGV[2] == '1'

local itemPrefixLength = string.len('zc:k:')

for i = staticKeyCount + 1, #KEYS do
    local itemKey = KEYS[i]
    local itemId = string.sub(itemKey, itemPrefixLength + 1)

    local itemTagsSerialized = redis.call('HGET', itemKey, 't')

    -- remove data
    redis.call('DEL', itemKey)

    -- remove ID from list of all IDs
    if notMatchingTags then
        redis.call('SREM', setIds, itemId)
    end

    -- update the ID list for each tag
    if (itemTagsSerialized) then
        for tagName in string.gmatch(itemTagsSerialized, "[^,]+") do
            redis.call('SREM', 'zc:ti:' .. tagName, itemId)
        end
    end
end

return true
LUA;

            $sha1 = sha1($script);
            $res = $this->redis->evalSha($sha1, $keys, $args);

            if (null === $res) {
                $res = $this->redis->eval($script, $keys, $args);
            }

            return (bool)$res;
        }

        $totalResult = true;

        foreach ($ids as $id) {
            // Get list of tags for this id
            $tags = explode(',', $this->redis->hGet(static::PREFIX_KEY . $id, static::FIELD_TAGS));

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

            $result = $this->redis->exec();
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

        while ($item = array_shift($this->deferred)) {
            /** @var CacheItem $item */
            try {
                $res = $this->commitItem($item);
            } catch (\Throwable $e) {
                $res = false;

                CacheItem::log(
                    $this->logger,
                    'Failed to commit key "{key}"',
                    [
                        'key' => $item->getKey(),
                        'exception' => $e,
                    ]
                );
            }

            $result = $result && (bool)$res;
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
        $id = $item->getKey();
        $now = time();

        $lifetime = null;
        $expiry = $item->getExpiry();
        $data = $this->serializeData($item->get());
        $tags = $item->getTags();

        if ($expiry) {
            $lifetime = $expiry - $now;
        }

        $values = [
            static::FIELD_DATA => $this->encodeData($data, $this->compressData),
            static::FIELD_TAGS => implode(',', $tags),
            static::FIELD_MTIME => $now,
            static::FIELD_INF => $lifetime ? 0 : 1,
        ];

        if ($this->useLua) {
            $staticKeys = [
                self::SET_IDS,
                self::SET_TAGS,
                self::PREFIX_KEY . $id,
            ];

            $args = [
                count($staticKeys), // 1
                ($this->notMatchingTags ? 1 : 0), // 2
                $id, // 3
                $values[static::FIELD_DATA], // 4
                $values[static::FIELD_TAGS], // 5
                $values[static::FIELD_MTIME], // 6
                $values[static::FIELD_INF], // 7
                min($lifetime, static::MAX_LIFETIME), // 8
            ];

            $keys = $this->preprocessTagIds($tags);
            $keys = array_merge($staticKeys, $keys);

            $script = <<<'LUA'
local setIds = KEYS[1]
local setTags = KEYS[2]

local itemKey = KEYS[3]
local itemId = ARGV[3]

local staticKeyCount = ARGV[1]
local notMatchingTags = ARGV[2] == '1'

local values = {
    d = ARGV[4],
    t = ARGV[5],
    m = ARGV[6],
    i = ARGV[7],
}

local lifetime = ARGV[8]

local tags = {}
local tagPrefixLength = string.len('zc:ti:')

for i = staticKeyCount + 1, #KEYS do
    local tagKey = KEYS[i]
    local tagName = string.sub(tagKey, tagPrefixLength + 1)

    tags[tagKey] = tagName
end

-- fetch currently set tags and remove tag <-> item relation for tags
-- which do not match anymore
-- TODO use JSON instead of csv here!
local oldTagsSerialized = redis.call('HGET', itemKey, 't')

if oldTagsSerialized then
    local inTable = function(val, tbl)
        for _, v in ipairs(tbl) do
            if v == val then
                return true
            end
        end

        return false
    end

    for oldTag in string.gmatch(oldTagsSerialized, "[^,]+") do
        if not inTable(oldTag, tags) then
            -- remove item ID from tags set
            -- e.g. remove nav_foo_32312313 from set zc:ti:navigation
            redis.call('SREM', 'zc:ti:' .. oldTag, itemId)
        end
    end
end

-- write item data
redis.call('HMSET', itemKey, 'd', values.d, 't', values.t, 'm', values.m, 'i', values.i)

-- set expiration for item if a lifetime is set
if values.i == '0' then
    redis.call('EXPIRE', itemKey, lifetime)
end

-- add tag entries in zc:ti:<tagName> format
if next(tags) ~= nil then
    local tagNames = {}

    -- for every tag, add the item ID to the zc:ti:<tagName> set
    for tagKey, tagName in pairs(tags) do
        redis.call('SADD', tagKey, itemId)
        table.insert(tagNames, tagName)
    end

    -- add all tags to zc:tags set
    redis.call('SADD', setTags, unpack(tagNames))
end

-- handle notMatchingTags if configured
if notMatchingTags then
    redis.call('SADD', setIds, itemId)
end

return true
LUA;

            $sha1 = sha1($script);
            $res = $this->redis->evalSha($sha1, $keys, $args);

            if (null === $res) {
                $res = $this->redis->eval($script, $keys, $args);
            }

            return (bool)$res;
        }

        // Get list of tags previously assigned
        $oldTags = $this->redis->hGet(static::PREFIX_KEY . $id, static::FIELD_TAGS);
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
     * @return bool True on success
     */
    protected function doInvalidateTags(array $tags)
    {
        if ($this->useLua) {
            $staticKeys = [
                self::SET_IDS,
                self::SET_TAGS,
            ];

            $args = [
                count($staticKeys), // 1
                ($this->notMatchingTags ? 1 : 0), // 2
                (int)$this->luaMaxCStack, // 3
            ];

            $keys = $this->preprocessTagIds($tags);
            $keys = array_merge($staticKeys, $keys);

            $script = <<<'LUA'
local setIds = KEYS[1]
local setTags = KEYS[2]

local staticKeyCount = ARGV[1]
local notMatchingTags = ARGV[2] == '1'
local luaMaxCStack = ARGV[3]

-- build a list of tags without zc:ti: prefix
local unprefixedTags = {}
local prefixLength = string.len('zc:ti:')

for u = staticKeyCount + 1, #KEYS do
    unprefixedTags[u] = string.sub(KEYS[u], prefixLength + 1)
end

-- iterate in lua max c stack steps
for i = staticKeyCount + 1, #KEYS, luaMaxCStack do
    local unpackLimit = math.min(#KEYS, i + luaMaxCStack - 1)
    local itemsToDel = redis.call('SUNION', unpack(KEYS, i, unpackLimit))

    for _, itemId in ipairs(itemsToDel) do
        local itemKey = 'zc:k:' .. itemId

        -- remove data
        redis.call('DEL', itemKey)

        -- remove ID from list of all IDs
        if notMatchingTags then
            redis.call('SREM', setIds, itemId)
        end
    end

    -- delete all tags in iteration
    redis.call('DEL', unpack(KEYS, i, unpackLimit))

    -- delete tags from zc:tags set for iteration
    redis.call('SREM', setTags, unpack(unprefixedTags, i, unpackLimit))
end

return true
LUA;

            $sha1 = sha1($script);
            $res = $this->redis->evalSha($sha1, $keys, $args);

            if (null === $res) {
                $res = $this->redis->eval($script, $keys, $args);
            }

            return (bool)$res;
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
        if ($this->useLua) {
            $staticKeys = [
                self::SET_IDS,
                self::SET_TAGS,
            ];

            $args = [
                count($staticKeys), // 1
                ($this->notMatchingTags ? 1 : 0), // 2
                (int)$this->luaMaxCStack, // 3
            ];

            $script = <<<'LUA'
local setIds = KEYS[1]
local setTags = KEYS[2]

local staticKeyCount = ARGV[1]
local notMatchingTags = ARGV[2] == '1'
local luaMaxCStack = ARGV[3]

local tagPrefixLength = string.len('zc:ti:')

for i = staticKeyCount + 1, #KEYS do
    local tagKey = KEYS[i]
    local tagName = string.sub(tagKey, tagPrefixLength + 1)
    local tagItems = redis.call('SMEMBERS', tagKey)
    local expired = {}
    local expiredCount = 0
    local notExpiredCount = 0

    for __, itemId in ipairs(tagItems) do
        if redis.call('EXISTS', 'zc:k:' .. itemId) == 0 then
            expiredCount = expiredCount + 1
            expired[expiredCount] = itemId

            -- Redis Lua scripts have a hard limit of 8000 parameters per command
            if expiredCount == luaMaxCStack then
                redis.call('SREM', tagKey, unpack(expired))

                -- Clean up expired ids from ids set
                if notMatchingTags then
                    redis.call('SREM', setIds, unpack(expired))
                end

                expiredCount = 0
                expired = {}
            end
        else
            notExpiredCount = notExpiredCount + 1
        end
    end

    if expiredCount > 0 then
        -- delete expired item ids from tag key zc:t:<tagName>
        redis.call('SREM', tagKey, unpack(expired))

        -- remove expired item ids from global zc:ids set
        if notMatchingTags then
            redis.call('SREM', setIds, unpack(expired))
        end
    end

    -- delete tag key completely if it does not have any items
    -- which are not expired
    if notExpiredCount == 0 then
        redis.call('DEL', tagKey)
        redis.call('SREM', setTags, tagName)
    end
end

return true
LUA;

            $sha1 = $this->redis->script('load', $script);

            $allTags = (array)$this->redis->sMembers(static::SET_TAGS);
            $tagsCount = count($allTags);
            $counter = 0;
            $tagsBatch = [];
            $result = true;

            foreach ($allTags as $tag) {
                $tagsBatch[] = $tag;
                $counter++;

                if (count($tagsBatch) == 10 || $counter == $tagsCount) {
                    $keys = $this->preprocessTagIds($tagsBatch);
                    $keys = array_merge($staticKeys, $keys);

                    $res = $this->redis->evalSha($sha1, $keys, $args);
                    $result = $result && (bool)$res;

                    $tagsBatch = [];

                    /* Give Redis some time to handle other requests */
                    usleep(20000);
                }
            }

            return $result;
        }

        $exists = [];
        $tags = (array)$this->redis->sMembers(static::SET_TAGS);

        foreach ($tags as $tag) {
            // Get list of expired ids for each tag
            $tagMembers = $this->redis->sMembers(static::PREFIX_TAG_IDS . $tag);
            $numTagMembers = count($tagMembers);
            $expired = [];

            $numExpired = 0;
            $numNotExpired = 0;

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
            if ($numExpired === $numTagMembers) {
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
     *
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
     * @param string $item
     * @param int $index
     * @param string $prefix
     */
    protected function preprocess(&$item, $index, $prefix)
    {
        $item = $prefix . $item;
    }

    /**
     * @param array $ids
     *
     * @return array
     */
    protected function preprocessIds($ids)
    {
        array_walk($ids, [$this, 'preprocess'], static::PREFIX_KEY);

        return $ids;
    }

    /**
     * @param array $tags
     *
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
     *
     * @throws \CredisException
     *
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
                throw new \CredisException('Could not compress cache data.');
            }

            return $this->compressPrefix . $data;
        }

        return $data;
    }

    /**
     * @param bool|string $data
     *
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
