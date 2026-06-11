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
     * Request attribute under which the request-side eligibility verdict is memoized —
     * both response listeners (surrogate keys at priority 0, cookie stripper at -200)
     * consult this resolver on every main response, and the path/context checks only
     * depend on the request.
     */
    private const ATTRIBUTE_REQUEST_ELIGIBLE = '_pimcore_cdn_request_eligible';

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

        // NOTE: a query string is deliberately NOT a disqualifier. The CDN caches such
        // responses regardless (its cache key includes the query), so refusing to tag them
        // would only make the cached objects unreachable for tag purges, and skipping the
        // cookie strip would turn them into uncacheable hit-for-pass traffic. Signed/gated
        // URLs opt out via `Cache-Control: no-store` or `excluded_paths` instead.

        // 4. Response does not carry `no-store` (the only reliable opt-out; see hasRestrictiveCacheControl()).
        if ($this->hasRestrictiveCacheControl($response)) {
            return false;
        }

        // 5.-7. Request-side eligibility (path patterns, exclusions, admin context) — memoized
        // per request since both response listeners evaluate it.
        return $this->isRequestEligible($request);
    }

    private function isRequestEligible(Request $request): bool
    {
        if ($request->attributes->has(self::ATTRIBUTE_REQUEST_ELIGIBLE)) {
            return (bool) $request->attributes->get(self::ATTRIBUTE_REQUEST_ELIGIBLE);
        }

        $eligible = $this->resolveRequestEligibility($request);
        $request->attributes->set(self::ATTRIBUTE_REQUEST_ELIGIBLE, $eligible);

        return $eligible;
    }

    private function resolveRequestEligibility(Request $request): bool
    {
        // Symfony does not urldecode pathInfo, so a browser request for "/Car Images/ö.jpg"
        // arrives as "/Car%20Images/%C3%B6.jpg". Decode it so operator-written excluded_paths
        // (human-readable) match, and so callers hash/match the same form the purge side uses.
        $path = rawurldecode($request->getPathInfo());

        // 5. Path is an asset/thumbnail path — anchored regexes, checked FIRST so the vast
        // majority of requests (ordinary pages) bail out on two cheap pattern misses.
        if (!preg_match(self::THUMBNAIL_PATTERN, $path)
            && !preg_match(self::ORIGINAL_ASSET_PATTERN, $path)) {
            return false;
        }

        // 6. Not an operator-excluded path.
        foreach ($this->excludedPaths as $pattern) {
            if (preg_match($pattern, $path)) {
                return false;
            }
        }

        // 7. Not a backend/admin request — those (e.g. tree-preview thumbnails) are never
        // public-CDN-cached. Deliberately LAST: matchesPimcoreContext() may invoke the
        // context guesser, which scans its route patterns and writes the resolved
        // _pimcore_context attribute onto the request as a side effect — that work and
        // mutation must only ever happen for actual asset responses.
        return !$this->contextResolver->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_ADMIN);
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
