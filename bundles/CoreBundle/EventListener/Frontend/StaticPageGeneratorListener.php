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

namespace Pimcore\Bundle\CoreBundle\EventListener\Frontend;

use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\StaticPageContextAwareTrait;
use Pimcore\Config;
use Pimcore\Document\StaticPageGenerator;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Logger;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Site;
use Pimcore\Tool\Storage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class StaticPageGeneratorListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;
    use StaticPageContextAwareTrait;

    public function __construct(
        protected StaticPageGenerator $staticPageGenerator,
        protected DocumentResolver $documentResolver,
        protected RequestHelper $requestHelper,
        private Config $config
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            DocumentEvents::POST_ADD => 'onPostAddUpdateDeleteDocument',
            DocumentEvents::POST_DELETE => 'onPostAddUpdateDeleteDocument',
            DocumentEvents::POST_UPDATE => 'onPostAddUpdateDeleteDocument',
            KernelEvents::REQUEST => ['onKernelRequest', 510], //this must run before targeting listener
            KernelEvents::RESPONSE => ['onKernelResponse', -120], //this must run after code injection listeners
        ];
    }

    /**
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        if ($this->requestHelper->isFrontendRequestByAdmin($request)) {
            return;
        }

        $config = $this->config['documents'];

        if (!$config['static_page_router']['enabled']) {
            return;
        }

        $routePattern = $config['static_page_router']['route_pattern'];
        if (!empty($routePattern) && !@preg_match($routePattern, $request->getPathInfo())) {
            return;
        }

        $storage = Storage::get('document_static');

        try {
            $path = '';
            $filename = urldecode($request->getPathInfo());

            if (Site::isSiteRequest()) {
                if ($request->getPathInfo() === '/') {
                    $filename = '/' . Site::getCurrentSite()->getRootDocument()->getKey();
                } else {
                    $path = Site::getCurrentSite()->getRootPath();
                }
            }
            $filename = $path .  $filename  . '.html';

            if ($storage->fileExists($filename)) {
                $content = $storage->read($filename);
                $date = date(\DateTime::ISO8601, $storage->lastModified($filename));

                $reponse = new Response(
                    $content, Response::HTTP_OK, [
                    'Content-Type' => 'text/html',
                    'X-Pimcore-Static-Page-Last-Modified' => $date,
                ]
                );

                $event->setResponse($reponse);
            }
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
        }
    }

    /**
     * @param ResponseEvent $event
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        if (\Pimcore\Tool::isFrontendRequestByAdmin($request)) {
            return;
        }

        //return if request is from StaticPageGenerator
        if ($request->attributes->has('pimcore_static_page_generator')) {
            return;
        }

        // only inject analytics code on non-admin requests
        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)
            && !$this->matchesStaticPageContext($request)) {
            return;
        }

        $document = $this->documentResolver->getDocument();

        if ($document instanceof Page && $document->getStaticGeneratorEnabled()) {
            $response = $event->getResponse()->getContent();
            $this->staticPageGenerator->generate($document, ['response' => $response]);
        }
    }

    /**
     * @param DocumentEvent $e
     */
    public function onPostAddUpdateDeleteDocument(DocumentEvent $e)
    {
        $document = $e->getDocument();

        if ($e->hasArgument('saveVersionOnly') || $e->hasArgument('autoSave')) {
            return;
        }

        if ($document instanceof PageSnippet) {
            try {
                if ($document->getStaticGeneratorEnabled()
                    || $this->staticPageGenerator->pageExists($document)) {
                    $this->staticPageGenerator->remove($document);
                }
            } catch (\Exception $e) {
                Logger::error((string) $e);

                return;
            }
        }
    }
}
