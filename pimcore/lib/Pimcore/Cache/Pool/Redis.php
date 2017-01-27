<?php

namespace Pimcore\Cache\Pool;

use Pimcore\Cache\Pool\Exception\CacheException;
use Pimcore\Cache\Pool\Exception\InvalidArgumentException;

/**
 * Redis2 item pool with tagging and LUA support.
 *
 * Adapted from https://github.com/colinmollenhour/Cm_Cache_Backend_Redis and from Pimcore\Cache\Backend\Redis2
 */
class Redis extends AbstractCacheItemPool
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
    protected $_redis;

    /**
     * @var bool
     */
    protected $_notMatchingTags = false;

    /**
     * @var int
     */
    protected $_compressTags = 1;

    /**
     * @var int
     */
    protected $_compressData = 1;

    /**
     * @var int
     */
    protected $_compressThreshold = 20480;

    /**
     * @var string
     */
    protected $_compressionLib;

    /**
     * @var string
     */
    protected $_compressPrefix;

    /**
     * @var bool
     */
    protected $_useLua = false;

    /**
     * Lua's unpack() has a limit on the size of the table imposed by
     * the number of Lua stack slots that a C function can use.
     * This value is defined by LUAI_MAXCSTACK in luaconf.h and for Redis it is set to 8000.
     *
     * @see https://github.com/antirez/redis/blob/b903145/deps/lua/src/luaconf.h#L439
     * @var int
     */
    protected $_luaMaxCStack = 5000;

    /**
     * @param \Credis_Client $redis
     * @param array $options
     * @param int $defaultLifetime
     */
    public function __construct(\Credis_Client $redis, $options = [], $defaultLifetime = 0)
    {
        parent::__construct($defaultLifetime);

        $this->_redis = $redis;

        if (isset($options['notMatchingTags'])) {
            $this->_notMatchingTags = (bool)$options['notMatchingTags'];
        }

        if (isset($options['compress_tags'])) {
            $this->_compressTags = (int)$options['compress_tags'];
        }

        if (isset($options['compress_data'])) {
            $this->_compressData = (int)$options['compress_data'];
        }

        if (isset($options['compress_threshold'])) {
            $this->_compressThreshold = (int)$options['compress_threshold'];
        }

        if (isset($options['compression_lib'])) {
            $this->_compressionLib = (string)$options['compression_lib'];
        } elseif (function_exists('snappy_compress')) {
            $this->_compressionLib = 'snappy';
        } elseif (function_exists('lz4_compress')) {
            $this->_compressionLib = 'l4z';
        } elseif (function_exists('lzf_compress')) {
            $this->_compressionLib = 'lzf';
        } else {
            $this->_compressionLib = 'gzip';
        }

        $this->_compressPrefix = substr($this->_compressionLib, 0, 2) . self::COMPRESS_PREFIX;

        if (isset($options['use_lua'])) {
            $this->_useLua = (bool)$options['use_lua'];
        }

        if (isset($options['lua_max_c_stack'])) {
            $this->_luaMaxCStack = (int)$options['lua_max_c_stack'];
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

        $pipeline = $this->_redis->pipeline()->multi();

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
            if (empty($entry) || !$entry[static::FIELD_MTIME]) {
                continue;
            }

            if (null === $entry[static::FIELD_DATA]) {
                continue;
            }

            $value = $this->_decodeData($entry[static::FIELD_DATA]);
            $value = $this->unserializeData($value);

            $tags    = [];
            $tagData = $this->_decodeData($entry[static::FIELD_TAGS]);

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
        return (bool)$this->_redis->exists($id);
    }

    /**
     * Deletes all items in the pool.
     *
     * @param string The prefix used for all identifiers managed by this pool
     *
     * @return bool True if the pool was successfully cleared, false otherwise
     */
    protected function doClear($namespace)
    {
        return $this->_redis->flushDb();
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
            $tags = explode(',', $this->_decodeData($this->_redis->hGet(self::PREFIX_KEY . $id, self::FIELD_TAGS)));

            $this->_redis->pipeline()->multi();

            // Remove data
            $this->_redis->del(self::PREFIX_KEY . $id);

            // Remove id from list of all ids
            if ($this->_notMatchingTags) {
                $this->_redis->sRem(self::SET_IDS, $id);
            }

            // Update the id list for each tag
            foreach ($tags as $tag) {
                $this->_redis->sRem(self::PREFIX_TAG_IDS . $tag, $id);
            }

            $result      = $this->_redis->exec();
            $totalResult = $totalResult && (bool)$result[0];
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
        if ($this->_useLua) {
            // TODO
        }

        $id  = $item->getKey();
        $now = time();

        $lifetime = null;
        $expiry   = $item->getExpiry();
        $data     = $this->serializeData($item->get());
        $tags     = $item->getTags();

        if ($expiry) {
            $lifetime = $expiry - $now;
        }

        // Get list of tags previously assigned
        $oldTags = $this->_decodeData($this->_redis->hGet(self::PREFIX_KEY . $id, self::FIELD_TAGS));
        $oldTags = $oldTags ? explode(',', $oldTags) : [];

        $this->_redis->pipeline()->multi();

        // Set the data
        $result = $this->_redis->hMSet(self::PREFIX_KEY . $id, [
            self::FIELD_DATA  => $this->_encodeData($data, $this->_compressData),
            self::FIELD_TAGS  => $this->_encodeData(implode(',', $tags), $this->_compressTags),
            self::FIELD_MTIME => $now,
            self::FIELD_INF   => $lifetime ? 0 : 1,
        ]);

        if (!$result) {
            throw new CacheException(sprintf('Could not set cache key %s', $id));
        }

        // Set expiration if specified
        if ($lifetime) {
            $this->_redis->expire(self::PREFIX_KEY . $id, min($lifetime, self::MAX_LIFETIME));
        }

        // Process added tags
        if ($tags) {
            // Update the list with all the tags
            $this->_redis->sAdd(self::SET_TAGS, $tags);

            // Update the id list for each tag
            foreach ($tags as $tag) {
                $this->_redis->sAdd(self::PREFIX_TAG_IDS . $tag, $id);
            }
        }

        // Process removed tags
        if ($remTags = ($oldTags ? array_diff($oldTags, $tags) : false)) {
            // Update the id list for each tag
            foreach ($remTags as $tag) {
                $this->_redis->sRem(self::PREFIX_TAG_IDS . $tag, $id);
            }
        }

        // Update the list with all the ids
        if ($this->_notMatchingTags) {
            $this->_redis->sAdd(self::SET_IDS, $id);
        }

        $result = $this->_redis->exec();

        // TODO how to check success?
        return !empty($result);
    }

    /**
     * Invalidates cached items using tags.
     *
     * @param string[] $tags An array of tags to invalidate
     *
     * @throws InvalidArgumentException When $tags is not valid
     *
     * @return bool True on success
     */
    public function invalidateTags(array $tags)
    {
        $ids = $this->getIdsMatchingTags($tags);

        if ($ids) {
            $this->_redis->pipeline()->multi();

            // Remove data
            $this->_redis->del($this->_preprocessIds($ids));

            // Remove ids from list of all ids
            if ($this->_notMatchingTags) {
                $this->_redis->sRem(self::SET_IDS, $ids);
            }

            $this->_redis->exec();
        }

        // TODO how to check success?
        return true;
    }

    /**
     * Return an array of stored cache ids which match given tags
     *
     * In case of multiple tags, a logical AND is made between tags
     *
     * @param array $tags array of tags
     * @return array array of matching cache ids (string)
     */
    public function getIdsMatchingTags($tags = [])
    {
        if ($tags) {
            return (array) $this->_redis->sInter($this->_preprocessTagIds($tags));
        }

        return [];
    }

    /**
     * @param $item
     * @param $index
     * @param $prefix
     */
    protected function _preprocess(&$item, $index, $prefix)
    {
        $item = $prefix . $item;
    }

    /**
     * @param $ids
     * @return array
     */
    protected function _preprocessIds($ids)
    {
        array_walk($ids, [$this, '_preprocess'], self::PREFIX_KEY);

        return $ids;
    }

    /**
     * @param $tags
     * @return array
     */
    protected function _preprocessTagIds($tags)
    {
        array_walk($tags, [$this, '_preprocess'], self::PREFIX_TAG_IDS);

        return $tags;
    }

    /**
     * @param string $data
     * @param int $level
     * @throws \CredisException
     * @return string
     */
    protected function _encodeData($data, $level)
    {
        if ($this->_compressionLib && $level && strlen($data) >= $this->_compressThreshold) {
            switch ($this->_compressionLib) {
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

            return $this->_compressPrefix . $data;
        }

        return $data;
    }

    /**
     * @param bool|string $data
     * @return string
     */
    protected function _decodeData($data)
    {
        if (substr($data, 2, 3) == self::COMPRESS_PREFIX) {
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
