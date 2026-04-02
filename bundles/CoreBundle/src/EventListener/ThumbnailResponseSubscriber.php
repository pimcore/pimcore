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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Removes tracking cookies and irrelevant HTTP headers from thumbnail responses
 * to enable proper browser and CDN caching.
 *
 * @internal
 */
class ThumbnailResponseSubscriber implements EventSubscriberInterface
{
    public function __construct(private bool $enabled = true)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Mark thumbnail requests as stateless early in the request cycle
            KernelEvents::REQUEST => ['onKernelRequest', 1000],
            // Clean response headers late in the response cycle (after other listeners)
            KernelEvents::RESPONSE => ['onKernelResponse', -1001],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Check if this is a thumbnail request
        if ($request->attributes->get('_route') === '_pimcore_service_thumbnail') {
            // Mark request as stateless to prevent unnecessary session handling
            $request->attributes->set('_stateless', true);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Only process thumbnail requests
        if ($request->attributes->get('_route') !== '_pimcore_service_thumbnail') {
            return;
        }

        $response = $event->getResponse();

        // Close and save session if it was started
        if ($request->hasSession() && ($session = $request->getSession())->isStarted()) {
            $session->save();
        }

        // Remove ALL Set-Cookie headers (visitor tracking cookies)
        $cookies = $response->headers->getCookies();
        foreach ($cookies as $cookie) {
            $response->headers->removeCookie(
                $cookie->getName(),
                $cookie->getPath(),
                $cookie->getDomain()
            );
        }
        $response->headers->remove('Set-Cookie');

        // Remove headers not relevant for binary assets
        $response->headers->remove('Content-Language');
        $response->headers->remove('X-Pimcore-Output-Cache-Disable-Reason');
    }
}
