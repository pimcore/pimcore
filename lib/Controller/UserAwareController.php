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

namespace Pimcore\Controller;

use Pimcore\Logger;
use Pimcore\Model\User;
use Pimcore\Security\User\TokenStorageUserResolver;
use Pimcore\Security\User\User as UserProxy;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Contracts\Service\Attribute\Required;

abstract class UserAwareController extends Controller
{
    /**
     * @var TokenStorageUserResolver|TokenStorageUserResolver
     */
    protected $tokenResolver;

    #[Required]
    public function setTokenResolver(TokenStorageUserResolver $tokenResolver): void
    {
        $this->tokenResolver = $tokenResolver;
    }

    /**
     * @return string[]
     */
    public static function getSubscribedServices()// : array
    {
        $services = parent::getSubscribedServices();
        $services[TokenStorageUserResolver::class] = TokenStorageUserResolver::class;

        return $services;
    }

    /**
     * Get user from user proxy object which is registered on security component
     *
     * @param bool $proxyUser Return the proxy user (UserInterface) instead of the pimcore model
     *
     * @return UserProxy|User|null
     */
    protected function getPimcoreUser($proxyUser = false)
    {
        if ($proxyUser) {
            return $this->tokenResolver->getUserProxy();
        }

        return $this->tokenResolver->getUser();
    }

    /**
     * Check user permission
     *
     * @param string $permission
     *
     * @throws AccessDeniedHttpException
     */
    protected function checkPermission($permission)
    {
        if (!$this->getPimcoreUser() || !$this->getPimcoreUser()->isAllowed($permission)) {
            Logger::error(
                'User {user} attempted to access {permission}, but has no permission to do so',
                [
                    'user' => $this->getPimcoreUser()?->getName(),
                    'permission' => $permission,
                ]
            );

            throw $this->createAccessDeniedHttpException();
        }
    }

    /**
     * @param string $message
     * @param \Throwable|null $previous
     * @param int $code
     * @param array $headers
     *
     * @return AccessDeniedHttpException
     */
    protected function createAccessDeniedHttpException(string $message = 'Access Denied.', \Throwable $previous = null, int $code = 0, array $headers = []): AccessDeniedHttpException
    {
        // $headers parameter not supported by Symfony 3.4
        return new AccessDeniedHttpException($message, $previous, $code, $headers);
    }

    /**
     * @param string[] $permissions
     */
    protected function checkPermissionsHasOneOf(array $permissions)
    {
        $allowed = false;
        $permission = null;
        foreach ($permissions as $permission) {
            if ($this->getPimcoreUser()->isAllowed($permission)) {
                $allowed = true;

                break;
            }
        }

        if (!$this->getPimcoreUser() || !$allowed) {
            Logger::error(
                'User {user} attempted to access {permission}, but has no permission to do so',
                [
                    'user' => $this->getPimcoreUser()->getName(),
                    'permission' => $permission,
                ]
            );

            throw new AccessDeniedHttpException('Attempt to access ' . $permission . ', but has no permission to do so.');
        }
    }

    /**
     * Check permission against all controller actions. Can optionally exclude a list of actions.
     *
     * @param ControllerEvent $event
     * @param string $permission
     * @param array $unrestrictedActions
     */
    protected function checkActionPermission(ControllerEvent $event, string $permission, array $unrestrictedActions = [])
    {
        $actionName = null;
        $controller = $event->getController();

        if (is_array($controller) && count($controller) === 2 && is_string($controller[1])) {
            $actionName = $controller[1];
        }

        if (null === $actionName || !in_array($actionName, $unrestrictedActions)) {
            $this->checkPermission($permission);
        }
    }
}
