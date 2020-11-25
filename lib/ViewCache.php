<?php


namespace Pimcore;


class ViewCache
{
    const VIEW_CACHE_PREFIX = 'pimcore_viewcache_';
    const WEBP_POSTFIX = 'webp';

    protected $captureEnabled = [];

    /**
     * @param string $name
     * @return string
     */
    public function getCacheKey(string $name) {
        $key = self::VIEW_CACHE_PREFIX . $name;

        if (Tool\Frontend::hasWebpSupport()) {
            $key .= self::WEBP_POSTFIX;
        }

        return $key;
    }

    /**
     * @param string $name
     */
    public function clearKey(string $name) {
        \Pimcore\Cache::remove(self::VIEW_CACHE_PREFIX . $name);
        \Pimcore\Cache::remove(self::VIEW_CACHE_PREFIX . $name . self::WEBP_POSTFIX);
    }


    /**
     * @param $content
     * @param string $key
     * @param array $tags
     * @param null $lifetime
     */
    public function saveToViewCache($content, string $name, array $tags, $lifetime = null) {
        \Pimcore\Cache::save($content, $this->getCacheKey($name), $tags, $lifetime, 996, true);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function loadFromViewCache(string $name) {
        return \Pimcore\Cache::load($this->getCacheKey($name));
    }

    /**
     * @param string $name
     */
    public function enableCapture(string $name) {
        $this->captureEnabled[$this->getCacheKey($name)] = true;
    }

    /**
     * @param string $name
     */
    public function disableCapture(string $name) {
        $this->captureEnabled[$this->getCacheKey($name)] = false;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isCaptureEnabled(string $name) {
        $key = $this->getCacheKey($name);
        return isset($this->captureEnabled[$key]) && $this->captureEnabled[$key] === true;
    }

    /**
     * Output the content.
     *
     * @param string $content the content, either rendered or retrieved directly from the cache.
     * @param string $key the cache key
     * @param bool $isLoadedFromCache true if the content origins from the cache and hasn't been created "live".
     */
    public function outputContent($content, string $key, bool $isLoadedFromCache)
    {
        echo $content;
    }
}