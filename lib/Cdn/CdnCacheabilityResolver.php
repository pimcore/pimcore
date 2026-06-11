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

use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 *
 * Single source of truth for "should the CDN cache this response?". Consumed by both
 * CdnSurrogateKeyListener (whether to emit Surrogate-Key/Cache-Tag) and
 * CdnAssetCookieStripperListener (whether to strip personalization cookies), so the two
 * cannot drift apart.
 */
class CdnCacheabilityResolver
{
    public const THUMBNAIL_PATTERN = '#(?:^|/)(image|video)-thumb__(\d+)__([a-zA-Z0-9_\-]+)/#';

    public const ORIGINAL_ASSET_PATTERN = '#^' . AssetWebPath::PREFIX . '/#';

    /**
     * @param string[] $excludedPaths Regular-expression patterns matched against the rawurldecoded request path info.
     */
    public function __construct(
        #[Autowire('%env(CDN_PROVIDER)%')]
        private readonly string $cdnProvider,
        #[Autowire('%pimcore.cdn.excluded_paths%')]
        private readonly array $excludedPaths,
        private readonly PimcoreContextResolver $contextResolver,
    ) {
    }

    public function isCdnCacheable(Request $request, Response $response): bool
    {
        // Cheap scalar/header guards first; the expensive path regexes run last.

        // 1. CDN enabled.
        if ($this->cdnProvider === '') {
            return false;
        }

        // 2. Only cacheable HTTP methods (GET/HEAD) — a 2xx to a POST/PUT/DELETE is never CDN-cached.
        if (!$request->isMethodCacheable()) {
            return false;
        }

        // 3. 2xx only — error/redirect responses are never CDN-cached.
        if (!$response->isSuccessful()) {
            return false;
        }

        // 4. No query string — signed/dynamic URLs (auth, cache-busters) bypass the public cache.
        if ($request->getQueryString() !== null) {
            return false;
        }

        // 5. Not a backend/admin request — those (e.g. tree-preview thumbnails) are never public-CDN-cached.
        if ($this->contextResolver->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_ADMIN)) {
            return false;
        }

        // 6. Response does not carry `no-store` (the only reliable opt-out; see hasRestrictiveCacheControl()).
        if ($this->hasRestrictiveCacheControl($response)) {
            return false;
        }

        // Symfony does not urldecode pathInfo, so a browser request for "/Car Images/ö.jpg"
        // arrives as "/Car%20Images/%C3%B6.jpg". Decode it so operator-written excluded_paths
        // (human-readable) match, and so callers hash/match the same form the purge side uses.
        $path = rawurldecode($request->getPathInfo());

        // 7. Not an operator-excluded path (regex — kept after the cheap guards above).
        foreach ($this->excludedPaths as $pattern) {
            if (preg_match($pattern, $path)) {
                return false;
            }
        }

        // 8. Path is an asset/thumbnail path (regex, last).
        return preg_match(self::THUMBNAIL_PATTERN, $path)
            || preg_match(self::ORIGINAL_ASSET_PATTERN, $path);
    }

    private function hasRestrictiveCacheControl(Response $response): bool
    {
        // Only `no-store` is a reliable "do not cache" signal. Symfony's ResponseHeaderBag
        // emits a default `no-cache, private` when no Cache-Control is set, so `private` and
        // `no-cache` cannot be distinguished from the framework default and must NOT be treated
        // as opt-outs (that would suppress tagging on ordinary asset responses). A project that
        // wants to keep a gated asset response off the CDN must send `Cache-Control: no-store`.
        return $response->headers->hasCacheControlDirective('no-store');
    }
}
