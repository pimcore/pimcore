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

use Pimcore\Bundle\CoreBundle\EventListener\Traits\EnabledTrait;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\ResponseInjectionTrait;
use Pimcore\Debug\Traits\StopwatchTrait;
use Pimcore\Event\Targeting\TargetingEvent;
use Pimcore\Event\TargetingEvents;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Targeting\ActionHandler\ActionHandlerInterface;
use Pimcore\Targeting\ActionHandler\AssignTargetGroup;
use Pimcore\Targeting\ActionHandler\DelegatingActionHandler;
use Pimcore\Targeting\ActionHandler\ResponseTransformingActionHandlerInterface;
use Pimcore\Targeting\Code\TargetingCodeGenerator;
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
    use StopwatchTrait;
    use PimcoreContextAwareTrait;
    use EnabledTrait;
    use ResponseInjectionTrait;

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

    /**
     * @var TargetingCodeGenerator
     */
    private $codeGenerator;

    public function __construct(
        VisitorInfoResolver $visitorInfoResolver,
        ActionHandlerInterface $actionHandler,
        VisitorInfoStorageInterface $visitorInfoStorage,
        RequestHelper $requestHelper,
        TargetingCodeGenerator $codeGenerator
    ) {
        $this->visitorInfoResolver = $visitorInfoResolver;
        $this->actionHandler = $actionHandler;
        $this->visitorInfoStorage = $visitorInfoStorage;
        $this->requestHelper = $requestHelper;
        $this->codeGenerator = $codeGenerator;
    }

    public static function getSubscribedEvents()
    {
        return [
            // needs to run before ElementListener to make sure there's a
            // resolved VisitorInfo when the document is loaded
            KernelEvents::REQUEST => ['onKernelRequest', 7],
            KernelEvents::RESPONSE => ['onKernelResponse', -115],
            TargetingEvents::PRE_RESOLVE => 'onPreResolve',
        ];
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->enabled) {
            return;
        }

        if ($event->getRequest()->cookies->has('pimcore_targeting_disabled')) {
            $this->disable();

            return;
        }

        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        // only apply targeting for GET requests
        // this may revised in later versions
        if ('GET' !== $request->getMethod()) {
            return;
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if (!$this->requestHelper->isFrontendRequest($request) || $this->requestHelper->isFrontendRequestByAdmin($request)) {
            return;
        }

        if (!$this->visitorInfoResolver->isTargetingConfigured()) {
            return;
        }

        $this->startStopwatch('Targeting:resolveVisitorInfo', 'targeting');

        $visitorInfo = $this->visitorInfoResolver->resolve($request);

        $this->stopStopwatch('Targeting:resolveVisitorInfo');

        // propagate response (e.g. redirect) to request handling
        if ($visitorInfo->hasResponse()) {
            $event->setResponse($visitorInfo->getResponse());
        }
    }

    public function onPreResolve(TargetingEvent $event)
    {
        $this->startStopwatch('Targeting:loadStoredAssignments', 'targeting');

        /** @var AssignTargetGroup $assignTargetGroupHandler */
        $assignTargetGroupHandler = $this->actionHandler->getActionHandler('assign_target_group');
        $assignTargetGroupHandler->loadStoredAssignments($event->getVisitorInfo()); // load previously assigned target groups

        $this->stopStopwatch('Targeting:loadStoredAssignments');
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
        $response = $event->getResponse();

        if ($event->isMasterRequest()) {
            $this->startStopwatch('Targeting:responseActions', 'targeting');

            // handle recorded actions on response
            $this->handleResponseActions($visitorInfo, $response);

            $this->stopStopwatch('Targeting:responseActions');

            if ($this->visitorInfoResolver->isTargetingConfigured()) {
                $this->injectTargetingCode($response, $visitorInfo);
            }
        }

        // check if the visitor info influences the response
        if ($this->appliesPersonalization($visitorInfo)) {
            // set response to private as soon as we apply personalization
            $response->setPrivate();
        }
    }

    private function injectTargetingCode(Response $response, VisitorInfo $visitorInfo)
    {
        if (!$this->isHtmlResponse($response)) {
            return;
        }

        $code = $this->codeGenerator->generateCode($visitorInfo);
        if (empty($code)) {
            return;
        }

        $this->injectBeforeHeadEnd($response, $code);
    }

    private function handleResponseActions(VisitorInfo $visitorInfo, Response $response)
    {
        $actions = $this->getResponseActions($visitorInfo);
        if (empty($actions)) {
            return;
        }

        foreach ($actions as $type => $typeActions) {
            $handler = $this->actionHandler->getActionHandler($type);
            if (!$handler instanceof ResponseTransformingActionHandlerInterface) {
                throw new \RuntimeException(sprintf(
                    'The "%s" action handler does not implement ResponseTransformingActionHandlerInterface',
                    $type
                ));
            }

            $handler->transformResponse($visitorInfo, $response, $typeActions);
        }
    }

    private function getResponseActions(VisitorInfo $visitorInfo): array
    {
        $actions = [];

        if (!$visitorInfo->hasActions()) {
            return $actions;
        }

        foreach ($visitorInfo->getActions() as $action) {
            $type = $action['type'] ?? null;
            $scope = $action['scope'] ?? null;

            if (empty($type) || empty($scope) || $scope !== VisitorInfo::ACTION_SCOPE_RESPONSE) {
                continue;
            }

            if (!is_array($actions[$type])) {
                $actions[$type] = [$action];
            } else {
                $actions[$type][] = $action;
            }
        }

        return $actions;
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
