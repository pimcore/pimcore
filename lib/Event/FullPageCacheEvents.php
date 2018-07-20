<?php

declare(strict_types=1);

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

namespace Pimcore\Event;

final class FullPageCacheEvents
{
    /**
     * Fired when the full page chage determines if it should disable
     * the cache due to existing session data. Keys handled in this
     * event will be ignored when checking if the session has any data.
     *
     * @Event("Pimcore\Event\Cache\FullPage\IgnoredSessionKeysEvent")
     *
     * @var string
     */
    const IGNORED_SESSION_KEYS = 'pimcore.cache.full_page.ignored_session_keys';

    /**
     * Fired to determine if a response should be cached.
     *
     * @Event("Pimcore\Event\Cache\FullPage\CacheResponseEvent")
     *
     * @var string
     */
    const CACHE_RESPONSE = 'pimcore.cache.full_page.cache_response';

    /**
     * Fired before the response is written to cache. Can be used to set or purge
     * data on the cached response.
     *
     * @Event("Pimcore\Event\Cache\FullPage\PrepareResponseEvent")
     *
     * @var string
     */
    const PREPARE_RESPONSE = 'pimcore.cache.full_page.prepare_response';
}
