<?php

declare(strict_types=1);

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

use Pimcore\Analytics\Google\Tracker;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\EnabledTrait;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PreviewRequestTrait;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\ResponseInjectionTrait;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\StaticPageContextAwareTrait;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Tool;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * @internal
 */
class GoogleAnalyticsCodeListener
{
    use EnabledTrait;
    use ResponseInjectionTrait;
    use PimcoreContextAwareTrait;
    use PreviewRequestTrait;
    use StaticPageContextAwareTrait;

    public function __construct(private Tracker $tracker)
    {
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        if (!$this->isEnabled()) {
            return;
        }

        $request = $event->getRequest();
        if (!$event->isMainRequest() && !$this->matchesStaticPageContext($request)) {
            return;
        }

        // only inject analytics code on non-admin requests
        // and check for static page context for CLI generation
        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)
            && !$this->matchesStaticPageContext($request)) {
            return;
        }

        if (!Tool::useFrontendOutputFilters()) {
            return;
        }

        if ($this->isPreviewRequest($request)) {
            return;
        }

        $response = $event->getResponse();
        if (!$this->isHtmlResponse($response)) {
            return;
        }

        $code = $this->tracker->generateCode();
        if (empty($code)) {
            return;
        }

        $this->injectBeforeHeadEnd($response, $code);
    }
}
