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

namespace Pimcore\Cdn;

interface PurgeClientInterface
{
    /**
     * Purge all CDN cache entries tagged with the given surrogate key.
     */
    public function purgeByTag(string $tag): void;

    /**
     * Purge multiple tags in a single API call where the CDN supports batching.
     *
     * @param string[] $tags
     */
    public function purgeByTags(array $tags): void;

    /**
     * Purge a specific URL from the CDN cache.
     *
     * Used for targeted manual operations (the pimcore:cdn:purge CLI command, a future admin UI
     * "force refresh" action, or debugging) and, for original assets served statically from
     * /var/assets, by the event-driven purge listener when CDN_BASE_URL is configured — those
     * responses never carry a Surrogate-Key/Cache-Tag header, so they can only be invalidated by
     * URL. Thumbnail invalidation remains tag-based via purgeByTag. Implementations must treat
     * this as a best-effort operation — URL-based purge is not available on all providers
     * (e.g., Varnish with BAN-based purge).
     */
    public function purgeByUrl(string $url): void;
}
