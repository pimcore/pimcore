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

namespace Pimcore\Bundle\PimcoreBundle\EventListener\Frontend;

use Pimcore\Bundle\PimcoreBundle\Service\Document\NearestPathResolver;
use Pimcore\Bundle\PimcoreBundle\Service\Request\DocumentResolver;
use Pimcore\Bundle\PimcoreBundle\Service\Request\PimcoreContextResolver;
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
     * @var NearestPathResolver
     */
    protected $nearestPathResolver;

    /**
     * @var DocumentResolver
     */
    protected $documentResolver;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param NearestPathResolver $nearestPathResolver
     * @param DocumentResolver $documentResolver
     * @param RequestStack $requestStack
     */
    public function __construct(NearestPathResolver $nearestPathResolver, DocumentResolver $documentResolver, RequestStack $requestStack)
    {
        $this->nearestPathResolver = $nearestPathResolver;
        $this->documentResolver    = $documentResolver;
        $this->requestStack        = $requestStack;
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
        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if ($this->documentResolver->getDocument($request)) {
            // we already have a document (e.g. set through the document router)
            return;
        } else {
            // if we're in a sub request and no explicit document is set - try to load document from
            // master request and set it on our sub-request
            if (!$event->isMasterRequest()) {
                $masterRequest = $this->requestStack->getMasterRequest();

                if ($document = $this->documentResolver->getDocument($masterRequest)) {
                    $this->documentResolver->setDocument($request, $document);

                    return;
                }
            }
        }

        // no document found yet - try to find the nearest document by request path
        // this is only done on the master request as a sub-request's pathInfo is _fragment when
        // rendered via actions helper
        if ($event->isMasterRequest()) {
            $document = $this->nearestPathResolver->getNearestDocumentByPath($request);
            if ($document) {
                $this->documentResolver->setDocument($request, $document);
            }
        }
    }
}
