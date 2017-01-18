<?php

namespace Pimcore\Cache;

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
     * @var int|null
     */
    protected $defaultLifetime;

    /**
     * @param int|null $defaultLifetime
     */
    public function __construct($defaultLifetime = null)
    {
        $this->defaultLifetime = $defaultLifetime;
    }

    /**
     * Create a cache item with the given key and data
     *
     * @param string $key
     * @param mixed $data
     * @param array $tags
     * @param int|\DateInterval|null|bool $lifetime
     * @param bool $isHit
     * @return CacheItemInterface
     */
    public function createCacheItem($key, $data, array $tags = [], $lifetime = null, $isHit = false)
    {
        $closure = $this->getClosure();

        /** @var CacheItem $item */
        $item = $closure($key, $isHit);
        $item->set($data);
        $item->tag($tags);

        // if lifetime is false, don't set it at all - otherwise set expiry and
        // run through lifetime generation logic
        if (false !== $lifetime) {
            $item->expiresAfter($lifetime);
        }

        return $item;
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

        return $closure($key, false);
    }

    /**
     * @return \Closure
     */
    protected function getClosure()
    {
        if (null === $this->closure) {
            $defaultLifetime = $this->defaultLifetime;

            $this->closure = \Closure::bind(
                function ($key, $isHit) use ($defaultLifetime) {
                    $item = new CacheItem();

                    $item->key   = $key;
                    $item->isHit = $isHit;
                    $item->defaultLifetime = $defaultLifetime;

                    return $item;
                },
                null,
                CacheItem::class
            );
        }

        return $this->closure;
    }
}
