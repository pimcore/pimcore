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

use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\DocumentResolver as DocumentResolverService;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Model\Document\Page;
use Pimcore\Templating\Helper\HeadMeta;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds Meta Data entries of document to HeadMeta view helper
 */
class DocumentMetaDataListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    /**
     * @var DocumentResolverService
     */
    protected $documentResolverService;

    /**
     * @var HeadMeta
     */
    protected $headMeta;

    /**
     * @param DocumentResolverService $documentResolverService
     * @param HeadMeta $headMeta
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
        $request = $event->getRequest();

        // just add meta data on master request
        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $document = $this->documentResolverService->getDocument($request);

        //check if document is set and if route is a document route for exactly that document
        if ($document && $request->get('_route') == 'document_' . $document->getId()) {
            if ($document instanceof Page && is_array($document->getMetaData())) {
                foreach ($document->getMetaData() as $meta) {
                    $this->headMeta->addRaw($meta);
                }
            }
        }
    }
}
