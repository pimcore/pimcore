<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\EventListener;

use Carbon\Carbon;
use Pimcore\Bundle\PimcoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\PimcoreBundle\Service\Request\PimcoreContextResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class HttpCacheListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse'
        ];
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_ADMIN)) {
            return;
        }

        $response = $event->getResponse();

        // set this headers to avoid problems with proxies, ...
        if ($response) {
            foreach (['no-cache', 'private', 'no-store', 'must-revalidate', 'no-transform'] as $directive) {
                $response->headers->addCacheControlDirective($directive, true);
            }

            foreach (['max-stale', 'post-check', 'pre-check', 'max-age'] as $directive) {
                $response->headers->addCacheControlDirective($directive, 0);
            }

            // this is for mod_pagespeed
            $response->headers->addCacheControlDirective('no-transform', true);

            $response->headers->set('Pragma', 'no-cache', true);
            $response->setExpires(new \DateTime('Tue, 01 Jan 1980 00:00:00 GMT'));
        }
    }
}
