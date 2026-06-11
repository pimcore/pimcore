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

namespace Pimcore\Tests\Unit\CoreBundle\EventListener;

use Pimcore\Bundle\CoreBundle\EventListener\CdnSurrogateKeyListener;
use Pimcore\Cdn\CdnAssetTag;
use Pimcore\Cdn\CdnCacheabilityResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class CdnSurrogateKeyListenerTest extends TestCase
{
    private function makeEvent(string $path, int $statusCode = 200, bool $mainRequest = true): ResponseEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create($path);
        $response = new Response('', $statusCode);

        return new ResponseEvent(
            $kernel,
            $request,
            $mainRequest ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST,
            $response,
        );
    }

    private function dispatch(string $cdnProvider, ResponseEvent $event): Response
    {
        $listener = new CdnSurrogateKeyListener(
            new CdnCacheabilityResolver($cdnProvider, [], $this->createMock(PimcoreContextResolver::class)),
            new CdnAssetTag(),
        );
        $listener->onKernelResponse($event);

        return $event->getResponse();
    }

    // -----------------------------------------------------------------------
    // Thumbnail URLs
    // -----------------------------------------------------------------------

    public function testImageThumbnailUrlEmitsThreeCorrectTags(): void
    {
        $path = '/var/tmp/thumbnails/image-thumb__42__product-hero/image.jpg';
        $event = $this->makeEvent($path);
        $response = $this->dispatch('fastly', $event);

        $this->assertSame('asset-42 thumb-product-hero asset-42-thumb-product-hero', $response->headers->get('Surrogate-Key'));
        $this->assertSame('asset-42 thumb-product-hero asset-42-thumb-product-hero', $response->headers->get('Cache-Tag'));
    }

    public function testVideoThumbnailUrlEmitsThreeCorrectTags(): void
    {
        $path = '/var/tmp/thumbnails/video-thumb__7__hero-video/frame_001.jpg';
        $event = $this->makeEvent($path);
        $response = $this->dispatch('fastly', $event);

        $this->assertSame('asset-7 thumb-hero-video asset-7-thumb-hero-video', $response->headers->get('Surrogate-Key'));
    }

    public function testThumbnailTagsContainAssetId(): void
    {
        $event = $this->makeEvent('/var/tmp/thumbnails/image-thumb__123__my-config/img.png');
        $response = $this->dispatch('fastly', $event);

        $this->assertStringContainsString('asset-123', $response->headers->get('Surrogate-Key'));
    }

    public function testThumbnailTagsContainConfigName(): void
    {
        $event = $this->makeEvent('/var/tmp/thumbnails/image-thumb__1__my-config/img.png');
        $response = $this->dispatch('fastly', $event);

        $this->assertStringContainsString('thumb-my-config', $response->headers->get('Surrogate-Key'));
    }

    public function testThumbnailTagsContainCombinedAssetIdAndConfig(): void
    {
        $event = $this->makeEvent('/var/tmp/thumbnails/image-thumb__5__banner/img.png');
        $response = $this->dispatch('fastly', $event);

        $this->assertStringContainsString('asset-5-thumb-banner', $response->headers->get('Surrogate-Key'));
    }

    // -----------------------------------------------------------------------
    // Original asset URLs
    // -----------------------------------------------------------------------

    public function testOriginalAssetUrlEmitsPathHashTag(): void
    {
        $path = '/var/assets/products/image.jpg';
        $expectedHash = substr(hash('sha256', $path), 0, 12);

        $event = $this->makeEvent($path);
        $response = $this->dispatch('fastly', $event);

        $this->assertSame('asset-path-' . $expectedHash, $response->headers->get('Surrogate-Key'));
        $this->assertSame('asset-path-' . $expectedHash, $response->headers->get('Cache-Tag'));
    }

    public function testOriginalAssetHashIsTwelveHexChars(): void
    {
        $event = $this->makeEvent('/var/assets/some/file.pdf');
        $response = $this->dispatch('fastly', $event);

        $tag = $response->headers->get('Surrogate-Key');
        $this->assertMatchesRegularExpression('/^asset-path-[0-9a-f]{12}$/', $tag);
    }

    public function testOriginalAssetHashMatchesPurgeListenerHash(): void
    {
        // CdnPurgeListener computes: substr(hash('sha256', '/var/assets' . $asset->getFullPath()), 0, 12)
        // CdnSurrogateKeyListener computes: substr(hash('sha256', $path), 0, 12)  where $path = /var/assets/...
        // Both must produce the same hash for the same asset.
        $fullPath = '/brand/logo.svg';
        $requestPath = '/var/assets' . $fullPath;

        $expectedHash = substr(hash('sha256', $requestPath), 0, 12);

        $event = $this->makeEvent($requestPath);
        $response = $this->dispatch('fastly', $event);

        $this->assertSame('asset-path-' . $expectedHash, $response->headers->get('Surrogate-Key'));
    }

    public function testEncodedOriginalAssetUrlHashesDecodedPath(): void
    {
        // Browsers percent-encode the request ("/Car Images/Mötley.jpg" arrives as
        // "/Car%20Images/M%C3%B6tley.jpg" and Symfony does not decode pathInfo), but the
        // purge side hashes the decoded real path — the listener must hash the decoded
        // form or the tags never match for filenames needing encoding.
        $event = $this->makeEvent('/var/assets/Car%20Images/M%C3%B6tley.jpg');
        $response = $this->dispatch('fastly', $event);

        $expectedHash = substr(hash('sha256', '/var/assets/Car Images/Mötley.jpg'), 0, 12);
        $this->assertSame('asset-path-' . $expectedHash, $response->headers->get('Surrogate-Key'));
    }

    // -----------------------------------------------------------------------
    // Guards: CDN disabled, non-2xx, sub-request, non-matching path
    // -----------------------------------------------------------------------

    public function testEmptyCdnProviderSkipsHeaderInjection(): void
    {
        $event = $this->makeEvent('/var/tmp/thumbnails/image-thumb__1__cfg/img.jpg');
        $response = $this->dispatch('', $event);

        $this->assertNull($response->headers->get('Surrogate-Key'));
        $this->assertNull($response->headers->get('Cache-Tag'));
    }

    public function test4xxResponseSkipsHeaderInjection(): void
    {
        $event = $this->makeEvent('/var/tmp/thumbnails/image-thumb__1__cfg/img.jpg', 404);
        $response = $this->dispatch('fastly', $event);

        $this->assertNull($response->headers->get('Surrogate-Key'));
    }

    public function test5xxResponseSkipsHeaderInjection(): void
    {
        $event = $this->makeEvent('/var/tmp/thumbnails/image-thumb__1__cfg/img.jpg', 503);
        $response = $this->dispatch('fastly', $event);

        $this->assertNull($response->headers->get('Surrogate-Key'));
    }

    public function testSubRequestSkipsHeaderInjection(): void
    {
        $event = $this->makeEvent('/var/tmp/thumbnails/image-thumb__1__cfg/img.jpg', 200, false);
        $response = $this->dispatch('fastly', $event);

        $this->assertNull($response->headers->get('Surrogate-Key'));
    }

    public function testNonMatchingPathSkipsHeaderInjection(): void
    {
        $event = $this->makeEvent('/admin/asset/list');
        $response = $this->dispatch('fastly', $event);

        $this->assertNull($response->headers->get('Surrogate-Key'));
        $this->assertNull($response->headers->get('Cache-Tag'));
    }

    public function testRootPathSkipsHeaderInjection(): void
    {
        $event = $this->makeEvent('/');
        $response = $this->dispatch('fastly', $event);

        $this->assertNull($response->headers->get('Surrogate-Key'));
    }

    // -----------------------------------------------------------------------
    // Both headers are always set together
    // -----------------------------------------------------------------------

    public function testSurrogateKeyAndCacheTagAreIdentical(): void
    {
        $event = $this->makeEvent('/var/tmp/thumbnails/image-thumb__99__promo/img.jpg');
        $response = $this->dispatch('cloudflare', $event);

        $this->assertNotNull($response->headers->get('Surrogate-Key'));
        $this->assertSame(
            $response->headers->get('Surrogate-Key'),
            $response->headers->get('Cache-Tag'),
        );
    }

    // -----------------------------------------------------------------------
    // Edge cases in thumbnail pattern
    // -----------------------------------------------------------------------

    public function testThumbnailConfigNameWithHyphensAndUnderscores(): void
    {
        $path = '/var/tmp/thumbnails/image-thumb__3__my_config-v2/img.jpg';
        $event = $this->makeEvent($path);
        $response = $this->dispatch('fastly', $event);

        $this->assertStringContainsString('thumb-my_config-v2', $response->headers->get('Surrogate-Key'));
    }

    public function testThumbnailWithNestedSubpath(): void
    {
        $path = '/var/tmp/thumbnails/image-thumb__55__hero/subdir/image.webp';
        $event = $this->makeEvent($path);
        $response = $this->dispatch('fastly', $event);

        $this->assertStringContainsString('asset-55', $response->headers->get('Surrogate-Key'));
        $this->assertStringContainsString('thumb-hero', $response->headers->get('Surrogate-Key'));
    }

    // -----------------------------------------------------------------------
    // Public thumbnail URLs without the /var/tmp/thumbnails/ prefix
    //
    // The _pimcore_service_thumbnail route accepts URLs of the form
    // `{prefix}{type}-thumb__{id}__{name}/{filename}` where {prefix} is any path
    // (`.*`). Deployments that do NOT rewrite the request URI to include
    // `/var/tmp/thumbnails/` (e.g. plain nginx/Apache reverse proxies in front
    // of Pimcore) still need surrogate keys emitted; otherwise tag-based
    // invalidation is impossible on those origins.
    // -----------------------------------------------------------------------

    public function testPublicImageThumbnailUrlWithoutInternalPrefixEmitsTags(): void
    {
        $path = '/products/category/image-thumb__42__product-hero/image.jpg';
        $event = $this->makeEvent($path);
        $response = $this->dispatch('fastly', $event);

        $this->assertSame(
            'asset-42 thumb-product-hero asset-42-thumb-product-hero',
            $response->headers->get('Surrogate-Key'),
        );
        $this->assertSame(
            'asset-42 thumb-product-hero asset-42-thumb-product-hero',
            $response->headers->get('Cache-Tag'),
        );
    }

    public function testPublicVideoThumbnailUrlWithoutInternalPrefixEmitsTags(): void
    {
        $path = '/media/video-thumb__7__hero-video/frame_001.jpg';
        $event = $this->makeEvent($path);
        $response = $this->dispatch('fastly', $event);

        $this->assertSame(
            'asset-7 thumb-hero-video asset-7-thumb-hero-video',
            $response->headers->get('Surrogate-Key'),
        );
    }

    public function testThumbnailUrlAtPathRootWithoutPrefixEmitsTags(): void
    {
        $path = '/image-thumb__1__cfg/img.jpg';
        $event = $this->makeEvent($path);
        $response = $this->dispatch('fastly', $event);

        $this->assertStringContainsString('asset-1', $response->headers->get('Surrogate-Key'));
        $this->assertStringContainsString('thumb-cfg', $response->headers->get('Surrogate-Key'));
    }

    // -----------------------------------------------------------------------
    // Thumbnail name char-class alignment with Asset\Service::extractThumbnailInfoFromUri
    //
    // The canonical Pimcore regex (models/Asset/Service.php) constrains thumbnail
    // names to [a-zA-Z0-9_\-]+. We mirror that constraint so the listener never
    // emits tags for paths that Asset routing wouldn't accept as a thumbnail.
    // -----------------------------------------------------------------------

    public function testThumbnailNameWithDotIsNotMatched(): void
    {
        // A dot is outside [a-zA-Z0-9_\-] — Pimcore routing would reject this as a
        // thumbnail name, so the listener must not emit thumbnail tags for it.
        $path = '/var/tmp/thumbnails/image-thumb__1__bad.name/img.jpg';
        $event = $this->makeEvent($path);
        $response = $this->dispatch('fastly', $event);

        $this->assertNull($response->headers->get('Surrogate-Key'));
    }

    public function testThumbnailNameWithSpaceIsNotMatched(): void
    {
        $path = '/var/tmp/thumbnails/image-thumb__1__bad name/img.jpg';
        $event = $this->makeEvent($path);
        $response = $this->dispatch('fastly', $event);

        $this->assertNull($response->headers->get('Surrogate-Key'));
    }

    public function testThumbnailNameWithPercentIsNotMatched(): void
    {
        $path = '/var/tmp/thumbnails/image-thumb__1__bad%name/img.jpg';
        $event = $this->makeEvent($path);
        $response = $this->dispatch('fastly', $event);

        $this->assertNull($response->headers->get('Surrogate-Key'));
    }

    public function testThumbnailNameWithMixedCaseLettersAndDigitsIsMatched(): void
    {
        // Belt-and-suspenders coverage of the [a-zA-Z0-9_\-]+ class.
        $path = '/var/tmp/thumbnails/image-thumb__1__MyConfig_v2-XL_99/img.jpg';
        $event = $this->makeEvent($path);
        $response = $this->dispatch('fastly', $event);

        $this->assertStringContainsString('thumb-MyConfig_v2-XL_99', $response->headers->get('Surrogate-Key'));
    }

    // -----------------------------------------------------------------------
    // New guards: excluded path, no-store; query-string variants stay tagged
    // -----------------------------------------------------------------------

    public function testEmitsSamePathTagsForQueryStringRequest(): void
    {
        // The CDN caches ?v=3 variants under the full URL; tagging them with the same
        // path-derived tags is what lets an asset purge reach every cached variant.
        $listener = new CdnSurrogateKeyListener(
            new CdnCacheabilityResolver('fastly', [], $this->createMock(PimcoreContextResolver::class)),
            new CdnAssetTag(),
        );
        $event = $this->makeEvent('/var/assets/folder/x.jpg?v=3', 200);

        $listener->onKernelResponse($event);

        $expectedHash = substr(hash('sha256', '/var/assets/folder/x.jpg'), 0, 12);
        self::assertSame('asset-path-' . $expectedHash, $event->getResponse()->headers->get('Surrogate-Key'));
    }

    public function testDoesNotEmitTagsForExcludedPath(): void
    {
        $listener = new CdnSurrogateKeyListener(
            new CdnCacheabilityResolver('fastly', ['#^/var/assets/private/#'], $this->createMock(PimcoreContextResolver::class)),
            new CdnAssetTag(),
        );
        $event = $this->makeEvent('/var/assets/private/secret.jpg', 200);

        $listener->onKernelResponse($event);

        self::assertFalse($event->getResponse()->headers->has('Surrogate-Key'));
    }

    public function testDoesNotEmitTagsWhenResponseHasNoStore(): void
    {
        $listener = new CdnSurrogateKeyListener(
            new CdnCacheabilityResolver('fastly', [], $this->createMock(PimcoreContextResolver::class)),
            new CdnAssetTag(),
        );
        $event = $this->makeEvent('/var/assets/folder/x.jpg', 200);
        $event->getResponse()->headers->set('Cache-Control', 'no-store');

        $listener->onKernelResponse($event);

        self::assertFalse($event->getResponse()->headers->has('Surrogate-Key'));
    }
}
