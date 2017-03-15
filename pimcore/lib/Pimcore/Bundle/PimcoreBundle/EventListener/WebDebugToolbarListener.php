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

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Pimcore\Http\RequestHelper;
use Symfony\Bundle\WebProfilerBundle\EventListener\WebDebugToolbarListener as BaseWebDebugToolbarListener;
use Symfony\Component\HttpFoundation\Request;
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
     * @param RequestHelper $requestHelper
     */
    public function setRequestHelper(RequestHelper $requestHelper)
    {
        $this->requestHelper = $requestHelper;
    }

    /**
     * @inheritDoc
     */
    protected function injectToolbar(Response $response, Request $request, array $nonces)
    {
        // only show toolbar for frontend-admin requests if requested
        $request = $this->requestHelper->getCurrentRequest();
        if ($this->requestHelper->isFrontendRequestByAdmin($request)) {
            if (!$request->get('pimcore_enable_wdt')) {
                return;
            }
        }

        parent::injectToolbar($response, $request, $nonces);
    }
}
