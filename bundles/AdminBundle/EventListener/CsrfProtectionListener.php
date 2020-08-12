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

namespace Pimcore\Bundle\AdminBundle\EventListener;

use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Templating\PhpEngine;
use Pimcore\Tool\Session;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class CsrfProtectionListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;
    use LoggerAwareTrait;

    protected $excludedRoutes = [];

    protected $csrfToken = null;

    /**
     * @var PhpEngine
     */
    protected $phpTemplatingEngine;

    /**
     * @param array $excludedRoutes
     * @param PhpEngine $phpTemplatingEngine
     */
    public function __construct($excludedRoutes, PhpEngine $phpTemplatingEngine)
    {
        $this->excludedRoutes = $excludedRoutes;
        $this->phpTemplatingEngine = $phpTemplatingEngine;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['handleRequest', 11],
        ];
    }

    /**
     * @param GetResponseEvent $event
     */
    public function handleRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_ADMIN)) {
            return;
        }

        $this->phpTemplatingEngine->addGlobal('csrfToken', $this->getCsrfToken());

        if ($request->getMethod() == Request::METHOD_GET) {
            return;
        }

        $exludedRoutes = [
            // WebDAV
            'pimcore_admin_webdav',

            // external applications
            'pimcore_admin_external_opcache_index',
            'pimcore_admin_external_linfo_index', 'pimcore_admin_external_linfo_layout',
            'pimcore_admin_external_adminer_adminer', 'pimcore_admin_external_adminer_proxy',
            'pimcore_admin_external_adminer_proxy_1', 'pimcore_admin_external_adminer_proxy_2',
        ];

        $route = $request->attributes->get('_route');
        if (in_array($route, $exludedRoutes) || in_array($route, $this->excludedRoutes)) {
            return;
        }

        $this->checkCsrfToken($request);
    }

    /**
     * @param Request $request
     */
    public function checkCsrfToken(Request $request)
    {
        $csrfToken = $this->getCsrfToken();
        $requestCsrfToken = $request->headers->get('x_pimcore_csrf_token');
        if (!$requestCsrfToken) {
            $requestCsrfToken = $request->get('csrfToken');
        }

        if (!$csrfToken || $csrfToken !== $requestCsrfToken) {
            $this->logger->error('Detected CSRF attack on {request}', [
                'request' => $request->getPathInfo(),
            ]);

            throw new AccessDeniedHttpException('Detected CSRF Attack! Do not do evil things with pimcore ... ;-)');
        }
    }

    /**
     * @return string
     */
    public function getCsrfToken()
    {
        if (!$this->csrfToken) {
            $this->csrfToken = Session::getReadOnly()->get('csrfToken');
            if (!$this->csrfToken) {
                $this->csrfToken = Session::useSession(function (AttributeBagInterface $adminSession) {
                    if (!$adminSession->has('csrfToken') && !$adminSession->get('csrfToken')) {
                        $adminSession->set('csrfToken', sha1(generateRandomSymfonySecret()));
                    }

                    return $adminSession->get('csrfToken');
                });
            }
        }

        return $this->csrfToken;
    }

    public function regenerateCsrfToken()
    {
        $this->csrfToken = Session::useSession(function (AttributeBagInterface $adminSession) {
            $token = sha1(generateRandomSymfonySecret());
            $adminSession->set('csrfToken', $token);

            return $token;
        });

        $this->phpTemplatingEngine->addGlobal('csrfToken', $this->csrfToken);
    }
}
