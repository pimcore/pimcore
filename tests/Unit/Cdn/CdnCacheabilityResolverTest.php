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

namespace Pimcore\Tests\Unit\Cdn;

use Pimcore\Cdn\CdnCacheabilityResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CdnCacheabilityResolverTest extends TestCase
{
    private function resolver(string $provider = 'fastly', array $excluded = [], bool $isAdmin = false): CdnCacheabilityResolver
    {
        $contextResolver = $this->createMock(PimcoreContextResolver::class);
        $contextResolver->method('matchesPimcoreContext')->willReturn($isAdmin);

        return new CdnCacheabilityResolver($provider, $excluded, $contextResolver);
    }

    private function req(string $uri, string $method = 'GET'): Request
    {
        return Request::create($uri, $method);
    }

    private function res(int $status = 200, ?string $cacheControl = null): Response
    {
        $response = new Response('', $status);
        if ($cacheControl !== null) {
            $response->headers->set('Cache-Control', $cacheControl);
        }

        return $response;
    }

    public function testThumbnailPathIsCacheable(): void
    {
        $r = $this->resolver();
        self::assertTrue($r->isCdnCacheable($this->req('/foo/image-thumb__1__cfg/x.jpg'), $this->res()));
    }

    public function testOriginalAssetPathIsCacheable(): void
    {
        $r = $this->resolver();
        self::assertTrue($r->isCdnCacheable($this->req('/var/assets/folder/x.jpg'), $this->res()));
    }

    public function testDisabledWhenCdnProviderEmpty(): void
    {
        $r = $this->resolver('');
        self::assertFalse($r->isCdnCacheable($this->req('/var/assets/folder/x.jpg'), $this->res()));
    }

    public function testNonCacheableHttpMethodNotCacheable(): void
    {
        // Only GET/HEAD responses may be CDN-cached; a 2xx to a POST must not be tagged.
        $r = $this->resolver();
        self::assertFalse($r->isCdnCacheable($this->req('/var/assets/folder/x.jpg', 'POST'), $this->res()));
        self::assertFalse($r->isCdnCacheable($this->req('/var/assets/folder/x.jpg', 'DELETE'), $this->res()));
    }

    public function testHeadMethodIsCacheable(): void
    {
        $r = $this->resolver();
        self::assertTrue($r->isCdnCacheable($this->req('/var/assets/folder/x.jpg', 'HEAD'), $this->res()));
    }

    public function testAdminContextNotCacheable(): void
    {
        // Backend/admin requests (e.g. tree-preview thumbnails) must never be public-CDN-cached.
        $r = $this->resolver(isAdmin: true);
        self::assertFalse($r->isCdnCacheable($this->req('/foo/image-thumb__1__cfg/x.jpg'), $this->res()));
    }

    public function testNon2xxNotCacheable(): void
    {
        $r = $this->resolver();
        self::assertFalse($r->isCdnCacheable($this->req('/var/assets/folder/x.jpg'), $this->res(403)));
        self::assertFalse($r->isCdnCacheable($this->req('/var/assets/folder/x.jpg'), $this->res(302)));
    }

    public function testQueryStringIsStillCacheable(): void
    {
        // The CDN caches query-string variants regardless (its cache key includes the
        // query) — refusing to tag them would only make those objects unpurgeable.
        // Signed/gated URLs opt out via no-store or excluded_paths instead.
        $r = $this->resolver();
        self::assertTrue($r->isCdnCacheable($this->req('/var/assets/folder/x.jpg?v=3'), $this->res()));
    }

    public function testExcludedPathNotCacheable(): void
    {
        $r = $this->resolver(excluded: ['#^/var/assets/private/#']);
        self::assertFalse($r->isCdnCacheable($this->req('/var/assets/private/secret.jpg'), $this->res()));
        self::assertTrue($r->isCdnCacheable($this->req('/var/assets/public/ok.jpg'), $this->res()));
    }

    public function testContextResolverNotConsultedForNonAssetPaths(): void
    {
        // matchesPimcoreContext() may invoke the context guesser, which scans its route
        // patterns and writes the _pimcore_context attribute onto the request as a side
        // effect. Ordinary page responses (the vast majority) must bail out on the cheap
        // path patterns before that ever happens.
        $contextResolver = $this->createMock(PimcoreContextResolver::class);
        $contextResolver->expects(self::never())->method('matchesPimcoreContext');

        $r = new CdnCacheabilityResolver('fastly', [], $contextResolver);

        self::assertFalse($r->isCdnCacheable($this->req('/checkout'), $this->res()));
    }

    public function testRequestEligibilityIsMemoizedAcrossCalls(): void
    {
        // Both response listeners (surrogate keys, cookie stripper) consult the resolver
        // on every main response — the request-side checks must only run once per request.
        $contextResolver = $this->createMock(PimcoreContextResolver::class);
        $contextResolver->expects(self::once())->method('matchesPimcoreContext')->willReturn(false);

        $r = new CdnCacheabilityResolver('fastly', [], $contextResolver);
        $request = $this->req('/var/assets/folder/x.jpg');

        self::assertTrue($r->isCdnCacheable($request, $this->res()));
        self::assertTrue($r->isCdnCacheable($request, $this->res()));
    }

    public function testExcludedPathWrittenHumanReadableMatchesEncodedRequest(): void
    {
        // Operators write exclusions for the human-readable path; the browser requests the
        // percent-encoded form (Symfony does not decode pathInfo). The resolver must match
        // against the decoded path or the exclusion silently fails and the asset is
        // publicly cached at the CDN.
        $r = $this->resolver(excluded: ['#^/var/assets/Privat Bilder/#']);
        self::assertFalse($r->isCdnCacheable($this->req('/var/assets/Privat%20Bilder/secret.jpg'), $this->res()));
    }

    public function testNoStoreResponseIsNotCacheable(): void
    {
        $r = $this->resolver();
        self::assertFalse($r->isCdnCacheable($this->req('/var/assets/folder/x.jpg'), $this->res(200, 'no-store')));
    }

    public function testExplicitPrivateDoesNotBlockCaching(): void
    {
        // `private` (and `no-cache`) are indistinguishable from Symfony's default Cache-Control,
        // so they are intentionally NOT treated as opt-outs — only `no-store` is. A normal asset
        // response stays cacheable; gated controllers must use `no-store`.
        $r = $this->resolver();
        self::assertTrue($r->isCdnCacheable($this->req('/var/assets/folder/x.jpg'), $this->res(200, 'private')));
    }

    public function testNonAssetPathNotCacheable(): void
    {
        $r = $this->resolver();
        self::assertFalse($r->isCdnCacheable($this->req('/some/page'), $this->res()));
    }
}
