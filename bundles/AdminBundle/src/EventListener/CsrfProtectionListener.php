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

namespace Pimcore\Bundle\AdminBundle\EventListener;

use Pimcore\Bundle\AdminBundle\Security\CsrfProtectionHandler;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

/**
 * @internal
 */
class CsrfProtectionListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    protected Environment $twig;

    /**
     * @var CsrfProtectionHandler $handler
     */
    protected CsrfProtectionHandler $csrfProtectionHandler;

    public function __construct(CsrfProtectionHandler $csrfProtectionHandler)
    {
        $this->csrfProtectionHandler = $csrfProtectionHandler;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['handleRequest', 11],
        ];
    }

    public function handleRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_ADMIN)) {
            return;
        }

        $this->csrfProtectionHandler->generateCsrfToken();

        if ($request->isMethodCacheable()) {
            return;
        }

        $exludedRoutes = [
            // WebDAV
            'pimcore_admin_webdav',

            // external applications
            'pimcore_bundle_systeminfo_opcache_index',
        ];

        $route = $request->attributes->get('_route');
        if (in_array($route, $exludedRoutes) || in_array($route, $this->csrfProtectionHandler->getExcludedRoutes())) {
            return;
        }

        $this->csrfProtectionHandler->checkCsrfToken($request);
    }
}
