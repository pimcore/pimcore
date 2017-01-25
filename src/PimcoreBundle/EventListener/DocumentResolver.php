<?php


namespace PimcoreBundle\EventListener;


use Pimcore\Controller\Router\Route\Frontend;
use Pimcore\Model\Document;
use PimcoreBundle\Service\Request\DocumentResolver as DocumentResolverService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Finds the nearest document for the current request if the routing/document router didn't (e.g. static routes)
 */
class DocumentResolver
{
    /**
     * @var DocumentResolverService
     */
    protected $documentResolverService;

    /**
     * @param DocumentResolverService $documentResolverService
     */
    public function __construct(DocumentResolverService $documentResolverService)
    {
        $this->documentResolverService = $documentResolverService;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ($this->documentResolverService->getDocument($request)) {
            // we already have a document (e.g. set through the document router)
            return;
        }

        $document = $this->findNearestDocument($request);
        if ($document) {
            $this->documentResolverService->setDocument($request, $document);
        }
    }

    /**
     * @param Request $request
     * @return Document|null
     */
    protected function findNearestDocument(Request $request)
    {
        // HACK HACK use the pimcore route for testing - refactor this into a service
        $reflector = new \ReflectionClass(Frontend::class);

        $method = $reflector->getMethod('getNearestDocumentByPath');
        $method->setAccessible(true);

        $nearestDocument = $method->invoke(new Frontend(), $request->getPathInfo());

        return $nearestDocument;
    }
}
