<?php
namespace Pimcore\Cache\Core;

use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerInterface;

interface CoreHandlerInterface
{
    /**
     * @return LoggerInterface
     */
    public function getLogger();

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setEnabled($enabled);

    /**
     * @return $this
     */
    public function enable();

    /**
     * @return $this
     */
    public function disable();

    /**
     * @return bool
     */
    public function isEnabled();

    /**
     * @return bool
     */
    public function getHandleCli();

    /**
     * @param bool $handleCli
     * @return $this
     */
    public function setHandleCli($handleCli);

    /**
     * @return bool
     */
    public function getForceImmediateWrite();

    /**
     * @param bool $forceImmediateWrite
     * @return $this
     */
    public function setForceImmediateWrite($forceImmediateWrite);

    /**
     * Load data from cache (retrieves data from cache item)
     *
     * @param $key
     * @return bool|mixed
     */
    public function load($key);

    /**
     * Get PSR-6 cache item
     *
     * @param $key
     * @return CacheItemInterface
     */
    public function getItem($key);

    /**
     * Save data to cache
     *
     * @param string $key
     * @param string $data
     * @param array $tags
     * @param int|\DateInterval|null $lifetime
     * @param bool $force
     * @return bool
     */
    public function save($key, $data, array $tags = [], $lifetime = null, $force = false);

    /**
     * Remove a cache item
     *
     * @param $key
     * @return bool
     */
    public function remove($key);

    /**
     * Empty the cache
     *
     * @return bool
     */
    public function clearAll();

    /**
     * @param string $tag
     * @return bool
     */
    public function clearTag($tag);

    /**
     * @param string[] $tags
     * @return bool
     */
    public function clearTags(array $tags);

    /**
     * Clears all tags stored in tagsClearedOnShutdown, this function is executed during Pimcore shutdown
     *
     * @return bool
     */
    public function clearTagsOnShutdown();

    /**
     * Adds a tag to the shutdown queue, see clearTagsOnShutdown
     *
     * @param string $tag
     * @return $this
     */
    public function addTagClearedOnShutdown($tag);

    /**
     * @param string $tag
     * @return $this
     */
    public function addTagIgnoredOnSave($tag);

    /**
     * @param string $tag
     * @return $this
     */
    public function removeTagIgnoredOnSave($tag);

    /**
     * @param string $tag
     * @return $this
     */
    public function addTagIgnoredOnClear($tag);

    /**
     * @param string $tag
     * @return $this
     */
    public function removeTagIgnoredOnClear($tag);

    /**
     * Writes save queue to the cache
     *
     * @return bool
     */
    public function writeSaveQueue();

    /**
     * Shut down pimcore - write cache entries and clean up
     *
     * @param bool $forceWrite
     * @return $this
     */
    public function shutdown($forceWrite = false);
}
