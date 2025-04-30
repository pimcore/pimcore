<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
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

    /**
     * Fired before the response is written to cache. Can be used to add tags
     * to the cached response.
     *
     * @Event("Pimcore\Event\Cache\FullPage\PrepareTagsEvent")
     *
     * @var string
     */
    const PREPARE_TAGS = 'pimcore.cache.full_page.prepare_tags';
}
