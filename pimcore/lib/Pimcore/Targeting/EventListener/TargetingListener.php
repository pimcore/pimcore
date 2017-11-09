<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Targeting\EventListener;

use Pimcore\Analytics\Piwik\Event\TrackingDataEvent;
use Pimcore\Analytics\Piwik\Tracker;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\EnabledTrait;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\ResponseInjectionTrait;
use Pimcore\Event\Analytics\PiwikEvents;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Targeting\TargetGroupResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TargetingListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;
    use ResponseInjectionTrait;
    use EnabledTrait;

    /**
     * @var DocumentResolver
     */
    private $documentResolver;

    /**
     * @var TargetGroupResolver
     */
    private $targetGroupResolver;

    /**
     * @var RequestHelper
     */
    private $requestHelper;

    public function __construct(
        DocumentResolver $documentResolver,
        TargetGroupResolver $targetGroupResolver,
        RequestHelper $requestHelper
    )
    {
        $this->documentResolver    = $documentResolver;
        $this->targetGroupResolver = $targetGroupResolver;
        $this->requestHelper       = $requestHelper;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            PiwikEvents::CODE_TRACKING_DATA => 'onPiwikTrackingData',

            // needs to run before ElementListener to make sure there's a
            // resolved VisitorInfo when the document is loaded
            KernelEvents::REQUEST           => ['onKernelRequest', 10],
        ];
    }

    public function onPiwikTrackingData(TrackingDataEvent $event)
    {
        if (!$this->enabled) {
            return;
        }

        $event->getBlock(Tracker::BLOCK_BEFORE_SCRIPT_TAG)->append(
            '<script type="text/javascript" src="/pimcore/static6/js/frontend/targeting_id.js"></script>'
        );

        $event->getBlock(Tracker::BLOCK_AFTER_TRACK)->append(
            '_paq.push([ function() { pimcore.Targeting.setVisitorId(this.getVisitorId()); } ]);'
        );
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->enabled) {
            return;
        }

        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if (!$this->requestHelper->isFrontendRequest($request) || $this->requestHelper->isFrontendRequestByAdmin($request)) {
            return;
        }

        if (!$this->targetGroupResolver->isTargetingConfigured()) {
            return;
        }

        $visitorInfo = $this->targetGroupResolver->resolve($request);

        // propagate response (e.g. redirect) to request handling
        if ($visitorInfo->hasResponse()) {
            $event->setResponse($visitorInfo->getResponse());
        }
    }
}
