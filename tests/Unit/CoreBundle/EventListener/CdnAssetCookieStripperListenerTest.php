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

use Pimcore\Bundle\CoreBundle\EventListener\CdnAssetCookieStripperListener;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class CdnAssetCookieStripperListenerTest extends TestCase
{
    private function makeEvent(string $path, bool $mainRequest = true, int $statusCode = 200): ResponseEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create($path);
        $response = new Response('', $statusCode);

        // Simulate Pimcore's PersonalizationBundle TargetingListener having set its cookies
        // earlier in the response chain. Two cookies are emitted in production: _pc_tss
        // (session) and _pc_tvs (visitor).
        $response->headers->setCookie(Cookie::create('_pc_tss', 'session-token-value'));
        $response->headers->setCookie(Cookie::create('_pc_tvs', 'visitor-token-value'));

        return new ResponseEvent(
            $kernel,
            $request,
            $mainRequest ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST,
            $response,
        );
    }

    private function dispatch(string $cdnProvider, ResponseEvent $event): Response
    {
        $listener = new CdnAssetCookieStripperListener($cdnProvider);
        $listener->onKernelResponse($event);

        return $event->getResponse();
    }

    public function testStripsSetCookieOnImageThumbnailResponse(): void
    {
        $event = $this->makeEvent('/Sample%20Content/foo/image-thumb__42__product-hero/x.jpg');
        $response = $this->dispatch('fastly', $event);

        $this->assertCount(0, $response->headers->getCookies(), 'All Set-Cookie headers should be stripped on thumbnail responses');
    }

    public function testStripsSetCookieOnVideoThumbnailResponse(): void
    {
        $event = $this->makeEvent('/Sample%20Content/foo/video-thumb__7__hero-video/frame.jpg');
        $response = $this->dispatch('fastly', $event);

        $this->assertCount(0, $response->headers->getCookies(), 'All Set-Cookie headers should be stripped on video thumbnail responses');
    }

    public function testStripsSetCookieOnOriginalAssetResponse(): void
    {
        $event = $this->makeEvent('/var/assets/Sample%20Content/foo.jpg');
        $response = $this->dispatch('fastly', $event);

        $this->assertCount(0, $response->headers->getCookies(), 'All Set-Cookie headers should be stripped on original-asset responses');
    }

    public function testDoesNotStripOnNonAssetResponse(): void
    {
        // Homepage / generic page — cookies must survive so personalization keeps working.
        $event = $this->makeEvent('/some/page');
        $response = $this->dispatch('fastly', $event);

        $this->assertCount(2, $response->headers->getCookies(), 'Set-Cookie headers must be preserved on non-asset responses');
    }

    public function testDoesNotStripOnSubRequest(): void
    {
        // Sub-requests (e.g. ESI fragments rendered server-side) should not have their
        // cookies touched — only the outermost main response is what reaches the CDN.
        $event = $this->makeEvent('/Sample%20Content/foo/image-thumb__1__cfg/x.jpg', mainRequest: false);
        $response = $this->dispatch('fastly', $event);

        $this->assertCount(2, $response->headers->getCookies(), 'Set-Cookie must not be touched on sub-requests');
    }

    public function testDoesNotStripWhenCdnDisabled(): void
    {
        // CDN_PROVIDER='' means no CDN is configured. We must not change response behavior
        // for sites that don't use a CDN, even on asset paths.
        $event = $this->makeEvent('/Sample%20Content/foo/image-thumb__1__cfg/x.jpg');
        $response = $this->dispatch('', $event);

        $this->assertCount(2, $response->headers->getCookies(), 'Cookies must not be stripped when CDN_PROVIDER is empty');
    }

    public function testListenerSubscribesToResponseEventAtLowPriority(): void
    {
        // Must run AFTER PersonalizationBundle's TargetingListener (priority -115) so we
        // strip the cookies it just set. Anything < -115 satisfies the constraint; we use
        // -200 to leave a safety margin for other late listeners.
        $subscribed = CdnAssetCookieStripperListener::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::RESPONSE, $subscribed);

        $entry = $subscribed[KernelEvents::RESPONSE];
        $this->assertIsArray($entry);
        $this->assertSame('onKernelResponse', $entry[0]);
        $this->assertLessThan(-115, $entry[1], 'Listener must run after TargetingListener (priority -115)');
    }
}
