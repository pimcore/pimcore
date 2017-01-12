<?php

namespace Pimcore\Cache\Symfony\Handler;

use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\CacheItem;

/**
 * This class exists only because of the cumbersome way symfony cache defines cache items. As we can't influence the
 * cache item (final class, no setters), we need to bind closures to the item class which alter object data. The shipped
 * cache adapters do it the same way.
 */
class CacheItemFactory implements CacheItemFactoryInterface
{
    /**
     * @var \Closure
     */
    protected $closure;

    /**
     * Create a cache item with the given key and data
     *
     * @param string $key
     * @param mixed $data
     * @param array $tags
     * @param int|\DateInterval|null|bool $lifetime
     * @return CacheItemInterface
     */
    public function createCacheItem($key, $data, array $tags = [], $lifetime = null)
    {
        $closure = $this->getClosure();

        return $closure($key, $data, $tags, $lifetime);
    }

    /**
     * Create an empty cache item
     *
     * @param $key
     * @return CacheItemInterface
     */
    public function createEmptyCacheItem($key)
    {
        $closure = $this->getClosure();

        return $closure($key, null, [], false);
    }

    /**
     * @return \Closure
     */
    protected function getClosure()
    {
        if (null === $this->closure) {
            $this->closure = \Closure::bind(
                function ($key, $data, $tags, $lifetime) {
                    $item = new CacheItem();
                    $item->key = $key;

                    if (null !== $data) {
                        $item->value = $data;
                    }

                    if (!empty($tags)) {
                        $item->tag($tags);
                    }

                    // if lifetime is false, don't set it at all - otherwise set expiry and
                    // run through lifetime generation logic
                    if (false !== $lifetime) {
                        $item->expiresAfter($lifetime);
                    }

                    return $item;
                },
                null,
                CacheItem::class
            );
        }

        return $this->closure;
    }
}
