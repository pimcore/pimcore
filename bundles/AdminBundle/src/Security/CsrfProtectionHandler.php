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

namespace Pimcore\Bundle\AdminBundle\Security;

use Pimcore\Tool\Session;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Twig\Environment;

/**
 * @internal
 */
class CsrfProtectionHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected array $excludedRoutes = [];

    protected ?string $csrfToken = null;

    protected Environment $twig;

    public function __construct(array $excludedRoutes, Environment $twig)
    {
        $this->excludedRoutes = $excludedRoutes;
        $this->twig = $twig;
    }

    public function checkCsrfToken(Request $request): void
    {
        $csrfToken = $this->getCsrfToken($request->getSession());
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

    public function getCsrfToken(SessionInterface $session): ?string
    {
        if (!$this->csrfToken) {
            $this->csrfToken = Session::getSessionBag($session)->get('csrfToken');
            if (!$this->csrfToken) {
                $this->regenerateCsrfToken($session, false);
            }
        }

        return $this->csrfToken;
    }

    public function regenerateCsrfToken(SessionInterface $session, bool $force = true): void
    {
        $this->csrfToken = Session::useBag($session, function (AttributeBagInterface $adminSession) use ($force) {
            if ($force || !$adminSession->get('csrfToken')) {
                $adminSession->set('csrfToken', sha1(generateRandomSymfonySecret()));
            }

            return $adminSession->get('csrfToken');
        });

        $this->twig->addGlobal('csrfToken', $this->csrfToken);
    }

    public function generateCsrfToken(SessionInterface $session): void
    {
        $this->twig->addGlobal('csrfToken', $this->getCsrfToken($session));
    }

    public function getExcludedRoutes(): array
    {
        return $this->excludedRoutes;
    }
}
