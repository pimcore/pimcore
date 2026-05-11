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

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 *
 * Removes Set-Cookie response headers from CDN-cacheable asset and thumbnail responses.
 *
 * Why: Pimcore's PersonalizationBundle TargetingListener (priority -115) sets visitor and
 * session cookies (_pc_tss, _pc_tvs) on every frontend response, including image/video
 * thumbnails and original /var/assets/ URLs. CDNs (Fastly, Varnish, Cloudflare, ...) treat
 * any response with Set-Cookie as private by default and refuse to cache it, defeating the
 * Surrogate-Key tagging that CdnSurrogateKeyListener attaches to those same responses.
 *
 * What: At priority -200 (after TargetingListener) we strip Set-Cookie on responses whose
 * request path matches the same patterns CdnSurrogateKeyListener tags. The cookies were
 * never useful for an asset/thumbnail anyway (the browser does not act on them when fetching
 * an image), so removing them has no client-visible effect.
 *
 * Gating: Only active when CDN_PROVIDER env var is set. Sites without a CDN keep their
 * original cookie behavior unchanged.
 */
class CdnAssetCookieStripperListener implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire('%env(CDN_PROVIDER)%')]
        private readonly string $cdnProvider,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // Priority -200 runs after PersonalizationBundle\TargetingListener (priority -115),
        // which is when the targeting cookies are written. Anything strictly less than -115
        // satisfies the constraint; -200 leaves a safety margin for other late listeners.
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -200],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // CDN disabled — leave cookie behavior untouched for non-CDN sites.
        if ($this->cdnProvider === '') {
            return;
        }

        $path = $event->getRequest()->getPathInfo();

        // Match the same paths that CdnSurrogateKeyListener tags. If the path is not a CDN
        // candidate, leave it alone — generic pages still need their personalization cookies.
        if (!preg_match(CdnSurrogateKeyListener::THUMBNAIL_PATTERN, $path)
            && !preg_match(CdnSurrogateKeyListener::ORIGINAL_ASSET_PATTERN, $path)) {
            return;
        }

        $response = $event->getResponse();

        // Symfony stores cookies in a queue accessed by getCookies()/clearCookie(). The
        // raw 'Set-Cookie' header is rebuilt from this queue at send time, so removing the
        // header alone is not enough — we must clear the queue.
        foreach ($response->headers->getCookies() as $cookie) {
            $response->headers->removeCookie($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
        }
    }
}
