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
use Pimcore\Event\Targeting\TargetingEvent;
use Pimcore\Event\TargetingEvents;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Staticroute;
use Pimcore\Model\Tool\Targeting\Persona as TargetGroup;
use Pimcore\Targeting\ActionHandler\ActionHandlerInterface;
use Pimcore\Targeting\ActionHandler\DelegatingActionHandler;
use Pimcore\Targeting\TargetGroupResolver;
use Pimcore\Targeting\TargetingStorageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
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
     * @var DelegatingActionHandler|ActionHandlerInterface
     */
    private $actionHandler;

    /**
     * @var TargetingStorageInterface
     */
    private $targetingStorage;

    /**
     * @var RequestHelper
     */
    private $requestHelper;

    public function __construct(
        DocumentResolver $documentResolver,
        TargetGroupResolver $targetGroupResolver,
        ActionHandlerInterface $actionHandler,
        TargetingStorageInterface $targetingStorage,
        RequestHelper $requestHelper
    )
    {
        $this->documentResolver    = $documentResolver;
        $this->targetGroupResolver = $targetGroupResolver;
        $this->actionHandler       = $actionHandler;
        $this->targetingStorage    = $targetingStorage;
        $this->requestHelper       = $requestHelper;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            PiwikEvents::CODE_TRACKING_DATA => 'onPiwikTrackingData',
            TargetingEvents::PRE_RESOLVE    => 'onPreResolve',

            // needs to run before ElementListener to make sure there's a
            // resolved VisitorInfo when the document is loaded
            KernelEvents::REQUEST           => ['onKernelRequest', 10],
            KernelEvents::RESPONSE          => ['onKernelResponse']
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

    /**
     * Handles target groups configured on the document settings panel. If a document
     * has configured target groups, the assign_target_group will be manually called
     * for that target group before starting to match other conditions.
     *
     * @param TargetingEvent $event
     */
    public function onPreResolve(TargetingEvent $event)
    {
        $request  = $event->getRequest();
        $document = $this->documentResolver->getDocument($request);

        if (!$document || !$document instanceof Page || null !== Staticroute::getCurrentRoute()) {
            return;
        }

        // read and normalize target group IDs from document
        $targetGroups = trim((string)$document->getPersonas());
        $targetGroups = explode(',', $targetGroups);
        $targetGroups = array_filter(array_map(function ($tg) {
            return !empty($tg) ? (int)$tg : null;
        }, $targetGroups));

        if (empty($targetGroups)) {
            return;
        }

        $visitorInfo = $event->getVisitorInfo();
        foreach ($targetGroups as $targetGroup) {
            $this->actionHandler->apply($visitorInfo, [
                'type'        => 'assign_target_group',
                'targetGroup' => $targetGroup
            ]);
        }
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

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$this->enabled) {
            return;
        }

        if (!$this->targetingStorage->hasVisitorInfo()) {
            return;
        }

        // TODO do this only if a document has a target group set? currently we do this as soon as any target group is assigned
        $visitorInfo = $this->targetingStorage->getVisitorInfo();
        if (0 === count($visitorInfo->getTargetGroups())) {
            return;
        }

        $response = $event->getResponse();

        // set response to private as soon as we have matching target groups
        // TODO remove and rely on the vary header set below
        $response->setPrivate();

        // set a vary header and assign matched target groups
        $targetGroupIds = array_map(function (TargetGroup $targetGroup) {
            return $targetGroup->getId();
        }, $visitorInfo->getTargetGroups());

        $headerName = 'X-Pimcore-TG';

        $vary = $response->getVary();
        if (!in_array($headerName, $vary)) {
            $vary[] = $headerName;
        }

        $response->setVary($vary);
        $response->headers->set($headerName, implode(',', $targetGroupIds));
    }
}
