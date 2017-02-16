<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Pimcore\Bundle\PimcoreBundle\Service\Document\DocumentService;
use Pimcore\Bundle\PimcoreBundle\Service\Request\DocumentResolver as DocumentResolverService;
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
class DocumentFallbackListener implements EventSubscriberInterface
{
    /**
     * @var DocumentService
     */
    protected $documentService;

    /**
     * @var DocumentResolverService
     */
    protected $documentResolverService;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param DocumentService $documentService
     * @param DocumentResolverService $documentResolverService
     * @param RequestStack $requestStack
     */
    public function __construct(DocumentService $documentService, DocumentResolverService $documentResolverService, RequestStack $requestStack)
    {
        $this->documentService         = $documentService;
        $this->documentResolverService = $documentResolverService;
        $this->requestStack            = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 5], // higher priority - run before editmode and editable handlers
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

        if ($this->documentResolverService->getDocument($request)) {
            // we already have a document (e.g. set through the document router)
            return;
        } else {
            // if we're in a sub request and no explicit document is set - try to load document from
            // master request and set it on our sub-request
            if (!$event->isMasterRequest()) {
                $masterRequest = $this->requestStack->getMasterRequest();

                if ($document = $this->documentResolverService->getDocument($masterRequest)) {
                    $this->documentResolverService->setDocument($request, $document);

                    return;
                }
            }
        }

        // no document found yet - try to find the nearest document by request path
        // this is only done on the master request as a sub-request's pathInfo is _fragment when
        // rendered via actions helper
        if ($event->isMasterRequest()) {
            $document = $this->documentService->getNearestDocumentByPath($request);
            if ($document) {
                $this->documentResolverService->setDocument($request, $document);
            }
        }
    }
}
