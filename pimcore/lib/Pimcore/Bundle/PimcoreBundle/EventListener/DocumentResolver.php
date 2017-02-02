<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Pimcore\Bundle\PimcoreBundle\Controller\DocumentAwareInterface;
use Pimcore\Controller\Router\Route\Frontend;
use Pimcore\Model\Document;
use Pimcore\Bundle\PimcoreBundle\Service\Request\DocumentResolver as DocumentResolverService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DocumentResolver implements EventSubscriberInterface
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

    /**
     * Finds the nearest document for the current request if the routing/document router didn't (e.g. static routes)
     *
     * @param GetResponseEvent $event
     */
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
     * Injects document into DocumentAware controllers
     *
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $callable = $event->getController();

        if (is_array($callable)) {
            $controller = $callable[0];

            if ($controller instanceof DocumentAwareInterface) {
                $document = $this->documentResolverService->getDocument($event->getRequest());
                if ($document) {
                    $controller->setDocument($document);
                }
            }
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST    => ['onKernelRequest', 5], // higher priority - run before editmode and editable handlers
            KernelEvents::CONTROLLER => 'onKernelController'
        ];
    }
}
