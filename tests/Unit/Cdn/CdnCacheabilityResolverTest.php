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
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CdnCacheabilityResolverTest extends TestCase
{
    private function resolver(string $provider = 'fastly', array $excluded = []): CdnCacheabilityResolver
    {
        return new CdnCacheabilityResolver($provider, $excluded);
    }

    private function req(string $uri): Request
    {
        return Request::create($uri);
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
        $r = $this->resolver(provider: '');
        self::assertFalse($r->isCdnCacheable($this->req('/var/assets/folder/x.jpg'), $this->res()));
    }

    public function testNon2xxNotCacheable(): void
    {
        $r = $this->resolver();
        self::assertFalse($r->isCdnCacheable($this->req('/var/assets/folder/x.jpg'), $this->res(403)));
        self::assertFalse($r->isCdnCacheable($this->req('/var/assets/folder/x.jpg'), $this->res(302)));
    }

    public function testQueryStringNotCacheable(): void
    {
        $r = $this->resolver();
        self::assertFalse($r->isCdnCacheable($this->req('/var/assets/folder/x.jpg?sig=abc'), $this->res()));
    }

    public function testExcludedPathNotCacheable(): void
    {
        $r = $this->resolver(excluded: ['#^/var/assets/private/#']);
        self::assertFalse($r->isCdnCacheable($this->req('/var/assets/private/secret.jpg'), $this->res()));
        self::assertTrue($r->isCdnCacheable($this->req('/var/assets/public/ok.jpg'), $this->res()));
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
