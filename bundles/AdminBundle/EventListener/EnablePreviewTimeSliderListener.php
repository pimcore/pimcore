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

namespace Pimcore\Bundle\AdminBundle\EventListener;

use Pimcore\Bundle\CoreBundle\EventListener\Traits\ResponseInjectionTrait;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\Request\Resolver\OutputTimestampResolver;
use Pimcore\Http\RequestHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class EnablePreviewTimeSliderListener implements EventSubscriberInterface
{
    use ResponseInjectionTrait;

    /**
     * @var OutputTimestampResolver
     */
    protected $outputTimestampResolver;

    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * @var EditmodeResolver
     */
    protected $editmodeResolver;

    /**
     * @var DocumentResolver
     */
    protected $documentResolver;

    public function __construct(OutputTimestampResolver $outputTimestampResolver, RequestHelper $requestHelper, EditmodeResolver $editmodeResolver, DocumentResolver $documentResolver)
    {
        $this->outputTimestampResolver = $outputTimestampResolver;
        $this->requestHelper = $requestHelper;
        $this->editmodeResolver = $editmodeResolver;
        $this->documentResolver = $documentResolver;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$this->outputTimestampResolver->timestampWasQueried()) {
            return;
        }

        $request = $event->getRequest();

        if ($this->editmodeResolver->isEditmode($request)) {
            return;
        }

        if (!$this->requestHelper->isFrontendRequestByAdmin($request)) {
            return;
        }

        $response = $event->getResponse();
        if (!$this->isHtmlResponse($response)) {
            return;
        }

        $documentId = 0;
        $document = $this->documentResolver->getDocument($request);
        if ($document) {
            $documentId = $document->getId();
        }

        $code = '
            <script>
                var documentId = ' . $documentId . ";
                var documentTab = top.pimcore.globalmanager.get('document_' + documentId);
                if(documentTab && documentTab.preview) {
                    documentTab.preview.showTimeSlider();
                }
            </script>
        ";

        $this->injectBeforeHeadEnd($response, $code);
    }
}
