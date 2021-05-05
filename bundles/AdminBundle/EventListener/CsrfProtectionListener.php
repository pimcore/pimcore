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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\AdminBundle\EventListener;

use Pimcore\Bundle\AdminBundle\Security\CsrfProtectionHandler;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

/**
 * @internal
 */
class CsrfProtectionListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @var CsrfProtectionHandler $handler
     */
    protected $csrfProtectionHandler;

    /**
     * @param CsrfProtectionHandler $csrfProtectionHandler
     */
    public function __construct(CsrfProtectionHandler $csrfProtectionHandler)
    {
        $this->csrfProtectionHandler = $csrfProtectionHandler;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['handleRequest', 11],
        ];
    }

    /**
     * @param RequestEvent $event
     */
    public function handleRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_ADMIN)) {
            return;
        }

        $this->csrfProtectionHandler->generateCsrfToken();

        if ($request->getMethod() == Request::METHOD_GET) {
            return;
        }

        $exludedRoutes = [
            // WebDAV
            'pimcore_admin_webdav',

            // external applications
            'pimcore_admin_external_opcache_index',
            'pimcore_admin_external_adminer_adminer', 'pimcore_admin_external_adminer_proxy',
            'pimcore_admin_external_adminer_proxy_1', 'pimcore_admin_external_adminer_proxy_2',
        ];

        $route = $request->attributes->get('_route');
        if (in_array($route, $exludedRoutes) || in_array($route, $this->csrfProtectionHandler->getExcludedRoutes())) {
            return;
        }

        $this->csrfProtectionHandler->checkCsrfToken($request);
    }
}
