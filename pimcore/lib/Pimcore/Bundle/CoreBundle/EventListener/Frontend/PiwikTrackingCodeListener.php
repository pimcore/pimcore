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

namespace Pimcore\Bundle\CoreBundle\EventListener\Frontend;

use Pimcore\Analytics\Tracking\Piwik\Tracker;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\ResponseInjectionTrait;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Tool;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class PiwikTrackingCodeListener implements EventSubscriberInterface
{
    use ResponseInjectionTrait;
    use PimcoreContextAwareTrait;

    /**
     * @var Tracker
     */
    private $tracker;

    /**
     * @var bool
     */
    private $enabled = true;

    public function __construct(Tracker $tracker)
    {
        $this->tracker = $tracker;
    }


    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -110]
        ];
    }

    public function enable()
    {
        $this->enabled = true;
    }

    public function disable()
    {
        $this->enabled = false;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$this->enabled) {
            return;
        }

        $request = $event->getRequest();
        if (!$event->isMasterRequest()) {
            return;
        }

        // only inject analytics code on non-admin requests
        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        // it's standard industry practice to exclude tracking if the request includes
        // the header 'X-Purpose:preview'
        if ($request->server->get('HTTP_X_PURPOSE') === 'preview') {
            return;
        }

        // output filters are disabled
        if (!Tool::useFrontendOutputFilters($event->getRequest())) {
            return;
        }

        $response = $event->getResponse();
        if (!$this->isHtmlResponse($response)) {
            return;
        }

        $code = $this->tracker->getCode();
        if (empty($code)) {
            return;
        }

        $this->injectBeforeHeadEnd($response, $code);
    }
}
