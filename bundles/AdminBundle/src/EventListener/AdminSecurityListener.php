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

namespace Pimcore\Bundle\AdminBundle\EventListener;

use Pimcore\Bundle\AdminBundle\Security\ContentSecurityPolicyHandler;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Config;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\RequestHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class AdminSecurityListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    /**
     * @param ContentSecurityPolicyHandler $contentSecurityPolicyHandler
     */
    public function __construct(
        protected RequestHelper $requestHelper,
        protected ContentSecurityPolicyHandler $contentSecurityPolicyHandler,
        protected Config $config
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        if (!$this->config['admin_csp_header']['enabled']) {
            return;
        }

        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_ADMIN)) {
            return;
        }

        if ($this->requestHelper->isFrontendRequestByAdmin($request)) {
            return;
        }

        if (!empty($this->config['admin_csp_header']['exclude_paths'])) {
            $requestUri = $request->getRequestUri();
            foreach ($this->config['admin_csp_header']['exclude_paths'] as $path) {
                if (@preg_match($path, $requestUri)) {
                    return;
                }
            }
        }

        $response = $event->getResponse();

        // set CSP header with random nonce string to the response
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicyHandler->getCspHeader());
    }
}
