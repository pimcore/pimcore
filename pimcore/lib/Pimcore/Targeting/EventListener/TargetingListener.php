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
use Pimcore\Targeting\ActionHandler\ActionHandlerInterface;
use Pimcore\Targeting\ActionHandler\AssignTargetGroup;
use Pimcore\Targeting\ActionHandler\DelegatingActionHandler;
use Pimcore\Targeting\Model\VisitorInfo;
use Pimcore\Targeting\VisitorInfoResolver;
use Pimcore\Targeting\VisitorInfoStorageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
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
     * @var VisitorInfoResolver
     */
    private $visitorInfoResolver;

    /**
     * @var DelegatingActionHandler|ActionHandlerInterface
     */
    private $actionHandler;

    /**
     * @var VisitorInfoStorageInterface
     */
    private $visitorInfoStorage;

    /**
     * @var RequestHelper
     */
    private $requestHelper;

    public function __construct(
        DocumentResolver $documentResolver,
        VisitorInfoResolver $visitorInfoResolver,
        ActionHandlerInterface $actionHandler,
        VisitorInfoStorageInterface $visitorInfoStorage,
        RequestHelper $requestHelper
    )
    {
        $this->documentResolver    = $documentResolver;
        $this->visitorInfoResolver = $visitorInfoResolver;
        $this->actionHandler       = $actionHandler;
        $this->visitorInfoStorage  = $visitorInfoStorage;
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
            KernelEvents::REQUEST           => ['onKernelRequest', 7],
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
            '_paq.push([ function() { pimcore.targeting.setVisitorId(this.getVisitorId()); } ]);'
        );
    }

    public function onPreResolve(TargetingEvent $event)
    {
        /** @var AssignTargetGroup $assignTargetGroupHandler */
        $assignTargetGroupHandler = $this->actionHandler->getActionHandler('assign_target_group');
        $assignTargetGroupHandler->loadStoredAssignments($event->getVisitorInfo()); // load previously assigned target groups

        $this->assignDocumentTargetGroups($event);
    }

    /**
     * Handles target groups configured on the document settings panel. If a document
     * has configured target groups, the assign_target_group will be manually called
     * for that target group before starting to match other conditions.
     *
     * @param TargetingEvent $event
     */
    private function assignDocumentTargetGroups(TargetingEvent $event)
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

        if (!$this->visitorInfoResolver->isTargetingConfigured()) {
            return;
        }

        $visitorInfo = $this->visitorInfoResolver->resolve($request);

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

        if (!$this->visitorInfoStorage->hasVisitorInfo()) {
            return;
        }

        $visitorInfo = $this->visitorInfoStorage->getVisitorInfo();
        $response    = $event->getResponse();

        // inject frontend actions into response
        $this->addResponseActions($response, $visitorInfo);

        // check if the visitor info influences the response
        if ($this->appliesPersonalization($visitorInfo)) {
            // set response to private as soon as we apply personalization
            $response->setPrivate();
        }
    }

    private function addResponseActions(Response $response, VisitorInfo $visitorInfo)
    {
        if (!$this->isHtmlResponse($response)) {
            return;
        }

        // filter frontend actions
        $actions = array_filter($visitorInfo->getActions(), function (array $action) {
            return ($action['scope'] ?? null) === 'frontend';
        });

        if (empty($actions)) {
            return;
        }

        $code = <<<EOL
<script type="text/javascript">
    window.pimcore = window.pimcore || {};
    window.pimcore.targeting = window.pimcore.targeting || {};
    window.pimcore.targeting.actions = %s;
</script>
EOL;

        $code = sprintf($code, json_encode($actions));

        $this->injectBeforeHeadEnd($response, $code);
    }

    private function appliesPersonalization(VisitorInfo $visitorInfo): bool
    {
        if (count($visitorInfo->getTargetGroupAssignments()) > 0) {
            return true;
        }

        if ($visitorInfo->hasActions()) {
            return true;
        }

        if ($visitorInfo->hasResponse()) {
            return true;
        }

        return false;
    }
}
