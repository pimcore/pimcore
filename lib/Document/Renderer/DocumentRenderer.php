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

namespace Pimcore\Document\Renderer;

use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Http\RequestHelper;
use Pimcore\Localization\LocaleService;
use Pimcore\Model\Document;
use Pimcore\Routing\Dynamic\DocumentRouteHandler;
use Pimcore\Targeting\Document\DocumentTargetingConfigurator;
use Pimcore\Templating\Helper\Placeholder\ContainerService;
use Pimcore\Templating\Renderer\ActionRenderer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface;

class DocumentRenderer implements DocumentRendererInterface
{
    /**
     * @var RequestHelper
     */
    private $requestHelper;

    /**
     * @var ActionRenderer
     */
    private $actionRenderer;

    /**
     * @var FragmentRendererInterface
     */
    private $fragmentRenderer;

    /**
     * @var DocumentRouteHandler
     */
    private $documentRouteHandler;

    /**
     * @var DocumentTargetingConfigurator
     */
    private $targetingConfigurator;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var LocaleService
     */
    private $localeService;

    /**
     * @param RequestHelper $requestHelper
     * @param ActionRenderer $actionRenderer
     * @param FragmentRendererInterface $fragmentRenderer
     * @param DocumentRouteHandler $documentRouteHandler
     * @param DocumentTargetingConfigurator $targetingConfigurator
     * @param EventDispatcherInterface $eventDispatcher
     * @param LocaleService $localeService
     */
    public function __construct(
        RequestHelper $requestHelper,
        ActionRenderer $actionRenderer,
        FragmentRendererInterface $fragmentRenderer,
        DocumentRouteHandler $documentRouteHandler,
        DocumentTargetingConfigurator $targetingConfigurator,
        EventDispatcherInterface $eventDispatcher,
        LocaleService $localeService
    ) {
        $this->requestHelper = $requestHelper;
        $this->actionRenderer = $actionRenderer;
        $this->fragmentRenderer = $fragmentRenderer;
        $this->documentRouteHandler = $documentRouteHandler;
        $this->targetingConfigurator = $targetingConfigurator;
        $this->eventDispatcher = $eventDispatcher;
        $this->localeService = $localeService;
    }

    /**
     * @required
     *
     * @param ContainerService $containerService
     */
    public function setContainerService(ContainerService $containerService)
    {
        // we have to ensure that the ContainerService was initialized at the time this service is created
        // this is necessary, since the ContainerService registers a listener for DocumentEvents::RENDERER_PRE_RENDER
        // which wouldn't be called if the ContainerService would be lazy initialized when the first
        // placeholder service/templating helper is used during the rendering process
    }

    /**
     * @inheritdoc
     */
    public function render(Document\PageSnippet $document, array $attributes = [], array $query = [], array $options = []): string
    {
        $this->eventDispatcher->dispatch(
            DocumentEvents::RENDERER_PRE_RENDER,
            new DocumentEvent($document)
        );

        // apply best matching target group (if any)
        $this->targetingConfigurator->configureTargetGroup($document);

        // add document route to request if no route is set
        // this is needed for logic relying on the current route (e.g. pimcoreUrl helper)
        if (!isset($attributes['_route'])) {
            $route = $this->documentRouteHandler->buildRouteForDocument($document);
            if (null !== $route) {
                $attributes['_route'] = $route->getRouteKey();
            }
        }

        try {
            $request = $this->requestHelper->getCurrentRequest();
        } catch (\Exception $e) {
            $request = $this->requestHelper->createRequestWithContext();
        }

        $documentLocale = $document->getProperty('language');
        $tempLocale = $this->localeService->getLocale();
        if ($documentLocale) {
            $this->localeService->setLocale($documentLocale);
            $request->setLocale($documentLocale);
        }

        $uri = $this->actionRenderer->createDocumentReference($document, $attributes, $query);
        $response = $this->fragmentRenderer->render($uri, $request, $options);

        $this->localeService->setLocale($tempLocale);

        $this->eventDispatcher->dispatch(
            DocumentEvents::RENDERER_POST_RENDER,
            new DocumentEvent($document)
        );

        return $response->getContent();
    }
}
