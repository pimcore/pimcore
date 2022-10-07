<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Pimcore\Http\RequestHelper;
use Pimcore\Http\RequestMatcherFactory;
use Symfony\Bundle\WebProfilerBundle\EventListener\WebDebugToolbarListener as SymfonyWebDebugToolbarListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Disables the web debug toolbar for frontend requests by admins (iframes inside admin interface)
 *
 * @internal
 */
class WebDebugToolbarListener implements EventSubscriberInterface
{
    /**
     * @var RequestMatcherInterface[]
     */
    protected $excludeMatchers;

    public function __construct(
        protected RequestHelper $requestHelper,
        protected RequestMatcherFactory $requestMatcherFactory,
        protected ?SymfonyWebDebugToolbarListener $debugToolbarListener,
        protected EventDispatcherInterface $eventDispatcher,
        protected array $excludeRoutes
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelResponse', -118],
        ];
    }

    /**
     * @param RequestEvent $event
     */
    public function onKernelResponse(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // do not show toolbar on frontend-admin requests
        if ($this->requestHelper->isFrontendRequestByAdmin($request)) {
            $this->disableWebDebugToolbar();
        }

        // do not show toolbar on excluded routes (pimcore.web_profiler.toolbar.excluded_routes config entry)
        foreach ($this->getExcludeMatchers() as $excludeMatcher) {
            if ($excludeMatcher->matches($request)) {
                $this->disableWebDebugToolbar();
            }
        }
    }

    /**
     * @return RequestMatcherInterface[]
     */
    protected function getExcludeMatchers()
    {
        if (null === $this->excludeMatchers) {
            $this->excludeMatchers = $this->requestMatcherFactory->buildRequestMatchers($this->excludeRoutes);
        }

        return $this->excludeMatchers;
    }

    protected function disableWebDebugToolbar(): void
    {
        if ($this->debugToolbarListener) {
            $this->eventDispatcher->removeSubscriber($this->debugToolbarListener);
        }
    }
}
