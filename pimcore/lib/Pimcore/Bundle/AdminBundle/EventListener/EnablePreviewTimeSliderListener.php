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

    public function __construct(OutputTimestampResolver $outputTimestampResolver, RequestHelper $requestHelper) {
        $this->outputTimestampResolver = $outputTimestampResolver;
        $this->requestHelper = $requestHelper;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse'
        ];
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        // only inject analytics code on non-admin requests
        if (!$this->requestHelper->isFrontendRequestByAdmin($request)) {
            return;
        }

        $response = $event->getResponse();
        if (!$this->isHtmlResponse($response)) {
            return;
        }

        $code = "
            <script>
                var windowName = window.name;
                var documentId = windowName.replace('document_preview_iframe_', '');
                var documentTab = top.pimcore.globalmanager.get('document_' + documentId);
                if(documentTab && documentTab.preview) {
                    documentTab.preview.showTimeSlider();
                }
            </script>
        ";

        $this->injectBeforeHeadEnd($response, $code);
    }
}
