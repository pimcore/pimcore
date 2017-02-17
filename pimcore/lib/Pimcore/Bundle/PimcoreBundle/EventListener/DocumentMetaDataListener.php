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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Pimcore\Bundle\PimcoreBundle\Service\Document\DocumentService;
use Pimcore\Bundle\PimcoreBundle\Service\Request\DocumentResolver as DocumentResolverService;
use Pimcore\Bundle\PimcoreBundle\Templating\Helper\HeadMeta;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds Meta Data entries of document to HeadMeta view helper
 */
class DocumentMetaDataListener implements EventSubscriberInterface
{
    /**
     * @var DocumentResolverService
     */
    protected $documentResolverService;

    /**
     * @var HeadMeta
     */
    protected $headMeta;


    /**
     * @param DocumentService $documentService
     * @param DocumentResolverService $documentResolverService
     * @param RequestStack $requestStack
     */
    public function __construct(DocumentResolverService $documentResolverService, HeadMeta $headMeta)
    {
        $this->documentResolverService = $documentResolverService;
        $this->headMeta = $headMeta;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest'],
        ];
    }

    /**
     * Finds the nearest document for the current request if the routing/document router didn't (e.g. static routes)
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        //just add meta data on master request
        if (!$event->isMasterRequest()) {
           return;
        }

        $request = $event->getRequest();
        $document = $this->documentResolverService->getDocument($request);

        //check if document is set and if route is a document route for exactly that document
        if($document && $request->get("_route") == "document_" . $document->getId()) {
            if (is_array($document->getMetaData())) {
                foreach ($document->getMetaData() as $meta) {
                    $this->headMeta->addRaw($meta);
                }
            }
        }
    }
}
