<?php
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

namespace Pimcore\Bundle\CoreBundle\EventListener\Frontend;

use Pimcore\Model\Document;
use Pimcore\Service\Request\DocumentResolver;
use Pimcore\Service\Request\PimcoreContextResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * If no document was found on the active request (not set by router or by initiator of a sub-request), try to find and
 * set a fallback document:
 *
 *  - if request is a sub-request, try to read document from master request
 *  - if all fails, try to find the nearest document by path
 */
class DocumentFallbackListener extends AbstractFrontendListener implements EventSubscriberInterface
{
    /**
     * @var Document\Service
     */
    protected $documentService;

    /**
     * @var DocumentResolver
     */
    protected $documentResolver;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param Document\Service $documentService
     * @param DocumentResolver $documentResolver
     * @param RequestStack $requestStack
     */
    public function __construct(Document\Service $documentService, DocumentResolver $documentResolver, RequestStack $requestStack)
    {
        $this->documentService  = $documentService;
        $this->documentResolver = $documentResolver;
        $this->requestStack     = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            // priority must be before
            // -> Symfony\Component\HttpKernel\EventListener\LocaleListener::onKernelRequest()
            // -> Pimcore\Bundle\CoreBundle\EventListener\Frontend\EditmodeListener::onKernelRequest()
            KernelEvents::REQUEST => ['onKernelRequest', 20],
        ];
    }

    /**
     * Finds the nearest document for the current request if the routing/document router didn't (e.g. static routes)
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if ($this->documentResolver->getDocument($request)) {
            // we already have a document (e.g. set through the document router)
            return;
        } else {
            // if we're in a sub request and no explicit document is set - try to load document from
            // parent and/or master request and set it on our sub-request
            if (!$event->isMasterRequest()) {
                $parentRequest = $this->requestStack->getParentRequest();
                $masterRequest = $this->requestStack->getMasterRequest();

                $eligibleRequests = [];

                if (null !== $parentRequest) {
                    $eligibleRequests[] = $parentRequest;
                }

                if ($masterRequest !== $parentRequest) {
                    $eligibleRequests[] = $masterRequest;
                }

                foreach ($eligibleRequests as $eligibleRequest) {
                    if ($document = $this->documentResolver->getDocument($eligibleRequest)) {
                        $this->documentResolver->setDocument($request, $document);

                        return;
                    }
                }
            }
        }

        // no document found yet - try to find the nearest document by request path
        // this is only done on the master request as a sub-request's pathInfo is _fragment when
        // rendered via actions helper
        if ($event->isMasterRequest()) {
            $document = $this->documentService->getNearestDocumentByPath($request->getPathInfo());
            if ($document) {
                $this->documentResolver->setDocument($request, $document);
            }
        }
    }
}
