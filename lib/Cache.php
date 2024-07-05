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

namespace Pimcore;

use DateInterval;
use Pimcore;
use Pimcore\Cache\Core\CoreCacheHandler;
use Pimcore\Event\CoreCacheEvents;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * This acts as facade for the actual cache implementation and exists primarily for BC reasons.
 */
class Cache
{
    protected static ?CoreCacheHandler $handler = null;

    /**
     * Get the cache handler implementation
     *
     * @internal
     *
     */
    public static function getHandler(): CoreCacheHandler
    {
        if (null === static::$handler) {
            static::$handler = Pimcore::getContainer()->get(CoreCacheHandler::class);
        }

        return static::$handler;
    }

    /**
     * Initialize the cache. This acts mainly as integration point with legacy caches.
     *
     * @internal
     */
    public static function init(): void
    {
        if (Pimcore::hasKernel()) {
            Pimcore::getContainer()
                ->get('event_dispatcher')
                ->dispatch(new GenericEvent(), CoreCacheEvents::INIT);

            if (isset($_REQUEST['pimcore_nocache']) && Pimcore::inDebugMode()) {
                self::getHandler()->setPool(Pimcore::getContainer()->get('pimcore.cache.adapter.null_tag_aware'));
            }
        }
    }

    /**
     * Returns the content of the requested cache entry
     *
     *
     */
    public static function load(string $key): mixed
    {
        return static::getHandler()->load($key);
    }

    /**
     * Save an item to the cache (deferred to shutdown if force is false and forceImmediateWrite is not set)
     *
     *
     */
    public static function save(mixed $data, string $key, array $tags = [], DateInterval|int $lifetime = null, int $priority = 0, bool $force = false): bool
    {
        return static::getHandler()->save($key, $data, $tags, $lifetime, $priority, $force);
    }

    /**
     * Remove an item from the cache
     *
     *
     */
    public static function remove(string $key): bool
    {
        return static::getHandler()->remove($key);
    }

    /**
     * Empty the cache
     *
     */
    public static function clearAll(): bool
    {
        return static::getHandler()->clearAll();
    }

    /**
     * Removes entries from the cache matching the given tag
     *
     *
     */
    public static function clearTag(string $tag): bool
    {
        return static::getHandler()->clearTag($tag);
    }

    /**
     * Removes entries from the cache matching the given tags
     *
     *
     */
    public static function clearTags(array $tags = []): bool
    {
        return static::getHandler()->clearTags($tags);
    }

    /**
     * Adds a tag to the shutdown queue
     *
     */
    public static function addClearTagOnShutdown(string $tag): void
    {
        static::getHandler()->addTagClearedOnShutdown($tag);
    }

    /**
     * Add tag to the list ignored on save. Items with this tag won't be saved to cache.
     *
     */
    public static function addIgnoredTagOnSave(string $tag): void
    {
        static::getHandler()->addTagIgnoredOnSave($tag);
    }

    /**
     * Remove tag from the list ignored on save
     *
     */
    public static function removeIgnoredTagOnSave(string $tag): void
    {
        static::getHandler()->removeTagIgnoredOnSave($tag);
    }

    /**
     * Add tag to the list ignored on clear. Tags in this list won't be cleared via clearTags()
     *
     */
    public static function addIgnoredTagOnClear(string $tag): void
    {
        static::getHandler()->addTagIgnoredOnClear($tag);
    }

    /**
     * Remove tag from the list ignored on clear
     *
     */
    public static function removeIgnoredTagOnClear(string $tag): void
    {
        static::getHandler()->removeTagIgnoredOnClear($tag);
    }

    /**
     * Write and clean up cache
     *
     *
     * @internal
     */
    public static function shutdown(bool $forceWrite = false): void
    {
        static::getHandler()->shutdown($forceWrite);
    }

    /**
     * Disables the complete pimcore cache
     */
    public static function disable(): void
    {
        static::getHandler()->disable();
    }

    /**
     * Enables the pimcore cache
     */
    public static function enable(): void
    {
        static::getHandler()->enable();
    }

    public static function isEnabled(): bool
    {
        return static::getHandler()->isEnabled();
    }

    public static function setForceImmediateWrite(bool $forceImmediateWrite): void
    {
        static::getHandler()->setForceImmediateWrite($forceImmediateWrite);
    }

    public static function getForceImmediateWrite(): bool
    {
        return static::getHandler()->getForceImmediateWrite();
    }
}
