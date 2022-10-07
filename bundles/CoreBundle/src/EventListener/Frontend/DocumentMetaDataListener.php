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
use Pimcore\Http\Request\Resolver\DocumentResolver as DocumentResolverService;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Model\Document\Page;
use Pimcore\Twig\Extension\Templating\HeadMeta;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds Meta Data entries of document to HeadMeta view helper
 *
 * @internal
 */
class DocumentMetaDataListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    public const FORCE_INJECTION = '_pimcore_force_document_meta_data_injection';

    /**
     * @param DocumentResolverService $documentResolverService
     * @param HeadMeta $headMeta
     */
    public function __construct(protected DocumentResolverService $documentResolverService, protected HeadMeta $headMeta)
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest'],
        ];
    }

    /**
     * Finds the nearest document for the current request if the routing/document router didn't (e.g. static routes)
     *
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        // just add meta data on master request
        if (!$event->isMainRequest() && !$event->getRequest()->attributes->get(self::FORCE_INJECTION)) {
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
