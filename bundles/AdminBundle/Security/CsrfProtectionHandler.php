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

namespace Pimcore\Bundle\AdminBundle\Security;

use Pimcore\Tool\Session;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Twig\Environment;

/**
 * @internal
 */
class CsrfProtectionHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected $excludedRoutes = [];

    protected $csrfToken = null;

    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @param array $excludedRoutes
     * @param Environment $twig
     */
    public function __construct($excludedRoutes, Environment $twig)
    {
        $this->excludedRoutes = $excludedRoutes;
        $this->twig = $twig;
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

        $this->twig->addGlobal('csrfToken', $this->csrfToken);
    }

    public function generateCsrfToken()
    {
        $this->twig->addGlobal('csrfToken', $this->getCsrfToken());
    }

    /**
     * @return array
     */
    public function getExcludedRoutes(): array
    {
        return $this->excludedRoutes;
    }
}
