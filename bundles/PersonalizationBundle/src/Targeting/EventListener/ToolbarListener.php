<?php

declare(strict_types=1);

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

namespace Pimcore\Bundle\PersonalizationBundle\Targeting\EventListener;

use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\PersonalizationBundle\Event\Targeting\RenderToolbarEvent;
use Pimcore\Bundle\PersonalizationBundle\Event\Targeting\TargetingEvent;
use Pimcore\Bundle\PersonalizationBundle\Event\TargetingEvents;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Debug\OverrideHandler;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Debug\TargetingDataCollector;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Model\VisitorInfo;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Service\TargetingEnableService;
use Pimcore\Bundle\PersonalizationBundle\Targeting\VisitorInfoStorageInterface;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\Response\CodeInjector;
use Pimcore\Model\Document;
use Pimcore\Tool\Authentication;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ToolbarListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    private VisitorInfoStorageInterface $visitorInfoStorage;

    private DocumentResolver $documentResolver;

    private TargetingDataCollector $targetingDataCollector;

    private OverrideHandler $overrideHandler;

    private EventDispatcherInterface $eventDispatcher;

    private EngineInterface $templatingEngine;

    private CodeInjector $codeInjector;

    private TargetingEnableService $targetingEnableService;

    public function __construct(
        VisitorInfoStorageInterface $visitorInfoStorage,
        DocumentResolver $documentResolver,
        TargetingDataCollector $targetingDataCollector,
        OverrideHandler $overrideHandler,
        EventDispatcherInterface $eventDispatcher,
        EngineInterface $templatingEngine,
        CodeInjector $codeInjector,
        TargetingEnableService $targetingEnableService
    ) {
        $this->visitorInfoStorage = $visitorInfoStorage;
        $this->documentResolver = $documentResolver;
        $this->targetingDataCollector = $targetingDataCollector;
        $this->overrideHandler = $overrideHandler;
        $this->eventDispatcher = $eventDispatcher;
        $this->templatingEngine = $templatingEngine;
        $this->codeInjector = $codeInjector;
        $this->targetingEnableService = $targetingEnableService;
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            TargetingEvents::PRE_RESOLVE => ['onPreResolve', -10],
            KernelEvents::RESPONSE => ['onKernelResponse', -127],
        ];
    }

    public function onPreResolve(TargetingEvent $event): void
    {
        $request = $event->getRequest();
        if (!$this->requestCanDebug($request)) {
            return;
        }

        // handle overrides from request data
        $this->overrideHandler->handleRequest($request);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->targetingEnableService->isTargetingEnabled()) {
            return;
        }
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->requestCanDebug($request)) {
            return;
        }

        // only inject toolbar if there's a visitor info
        if (!$this->visitorInfoStorage->hasVisitorInfo()) {
            return;
        }

        $document = $this->documentResolver->getDocument($request);
        $visitorInfo = $this->visitorInfoStorage->getVisitorInfo();
        $data = $this->collectTemplateData($visitorInfo, $document);

        $overrideForm = $this->overrideHandler->getForm($request);
        $data['overrideForm'] = $overrideForm->createView();

        $this->injectToolbar(
            $event->getResponse(),
            $data
        );
    }

    private function requestCanDebug(Request $request): bool
    {
        if ($request->attributes->has('pimcore_targeting_debug')) {
            return (bool)$request->attributes->get('pimcore_targeting_debug');
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return false;
        }

        // only inject toolbar for logged in admin users
        $adminUser = Authentication::authenticateSession($request);
        if (!$adminUser) {
            return false;
        }

        $cookieValue = (bool)$request->cookies->get('pimcore_targeting_debug');
        if (!$cookieValue) {
            return false;
        }

        $request->attributes->set('pimcore_targeting_debug', true);

        return true;
    }

    private function collectTemplateData(VisitorInfo $visitorInfo, Document $document = null): array
    {
        $token = substr(hash('sha256', uniqid((string)mt_rand(), true)), 0, 6);

        $tdc = $this->targetingDataCollector;

        $data = [
            'token' => $token,
            'visitorInfo' => $tdc->collectVisitorInfo($visitorInfo),
            'targetGroups' => $tdc->collectTargetGroups($visitorInfo),
            'rules' => $tdc->collectMatchedRules($visitorInfo),
            'documentTargetGroup' => $tdc->collectDocumentTargetGroup($document),
            'documentTargetGroups' => $tdc->collectDocumentTargetGroupMapping(),
            'storage' => $tdc->collectStorage($visitorInfo),
        ];

        return $data;
    }

    private function injectToolbar(Response $response, array $data): void
    {
        $event = new RenderToolbarEvent('@PimcorePersonalization/Targeting/toolbar/toolbar.html.twig', $data);

        $this->eventDispatcher->dispatch($event, TargetingEvents::RENDER_TOOLBAR);

        $code = $this->templatingEngine->render(
            $event->getTemplate(),
            $event->getData()
        );

        $this->codeInjector->inject(
            $response,
            $code,
            CodeInjector::SELECTOR_BODY,
            CodeInjector::POSITION_END
        );
    }
}
