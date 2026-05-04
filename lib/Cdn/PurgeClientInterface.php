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
     * Intended for targeted manual operations: admin-triggered single-asset purge CLI command,
     * future admin UI "force refresh" action, or debugging. Event-driven invalidation uses
     * purgeByTag exclusively. Implementations must treat this as a best-effort operation —
     * URL-based purge is not available on all providers (e.g., Varnish with BAN-based purge).
     */
    public function purgeByUrl(string $url): void;
}
