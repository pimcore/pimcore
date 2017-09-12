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

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Pimcore\Http\RequestHelper;
use Pimcore\Http\RequestMatcherFactory;
use Symfony\Bundle\WebProfilerBundle\EventListener\WebDebugToolbarListener as BaseWebDebugToolbarListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Disables the web debug toolbar for frontend requests by admins (iframes inside admin interface)
 */
class WebDebugToolbarListener extends BaseWebDebugToolbarListener
{
    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * @var RequestMatcherFactory
     */
    protected $requestMatcherFactory;

    /**
     * @var array
     */
    protected $excludeRoutes = [];

    /**
     * @var RequestMatcherInterface[]
     */
    protected $excludeMatchers;

    /**
     * @param RequestHelper $requestHelper
     */
    public function setRequestHelper(RequestHelper $requestHelper)
    {
        $this->requestHelper = $requestHelper;
    }

    /**
     * @param RequestMatcherFactory $requestMatcherFactory
     */
    public function setRequestMatcherFactory(RequestMatcherFactory $requestMatcherFactory)
    {
        $this->requestMatcherFactory = $requestMatcherFactory;
    }

    /**
     * @param array $excludeRoutes
     */
    public function setExcludeRoutes(array $excludeRoutes)
    {
        $this->excludeRoutes = $excludeRoutes;
    }

    /**
     * @inheritDoc
     */
    protected function injectToolbar(Response $response, Request $request, array $nonces)
    {
        // only show toolbar for frontend-admin requests if requested
        $request = $this->requestHelper->getCurrentRequest();

        // parameter pimcore_enable_wdt allows us to override if toolbar is shown or not
        $enableParam = (bool)$request->get('pimcore_enable_wdt');

        if (!$enableParam) {
            // do not show toolbar on frontend-admin requests
            if ($this->requestHelper->isFrontendRequestByAdmin($request)) {
                return;
            }

            // do not show toolbar on excluded routes (pimcore.web_profiler.toolbar.excluded_routes config entry)
            foreach ($this->getExcludeMatchers() as $excludeMatcher) {
                if ($excludeMatcher->matches($request)) {
                    return;
                }
            }
        }

        parent::injectToolbar($response, $request, $nonces);
    }

    /**
     * @return RequestMatcherInterface[]
     */
    protected function getExcludeMatchers()
    {
        if (null === $this->excludeMatchers) {
            $this->excludeMatchers = $this->requestMatcherFactory->buildRequestMatchers($this->excludeRoutes);
        }

        return $this->excludeMatchers;
    }
}
