<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller;

use Pimcore\Bundle\PimcoreAdminBundle\Security\User\User;
use Pimcore\Logger;
use Pimcore\Tool\Session;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

abstract class AdminController extends Controller implements AdminControllerInterface
{
    /**
     * Get user from user proxy object which is registered on security component
     *
     * @return \Pimcore\Model\User
     */
    protected function getUser()
    {
        $user = parent::getUser();
        if ($user && $user instanceof User) {
            return $user->getUser();
        }
    }

    /**
     * Check user permission
     *
     * @param $permission
     *
     * @throws AccessDeniedHttpException
     */
    protected function checkPermission($permission)
    {
        if (!$this->getUser() || !$this->getUser()->isAllowed($permission)) {
            $message = "Attempt to access " . $permission . ", but has no permission to do so.";
            Logger::err($message);

            throw new AccessDeniedHttpException($message);
        }
    }

    /**
     * Check CSRF token
     *
     * @param Request $request
     *
     * @throws AccessDeniedHttpException
     *      if CSRF token does not match
     */
    protected function protectCsrf(Request $request)
    {
        // TODO use isCsrfTokenValid() and the native CSRF token storage?

        $csrfToken = Session::useSession(function (AttributeBagInterface $adminSession) {
            return $adminSession->get('csrfToken');
        });

        if (!$csrfToken || $csrfToken !== $request->headers->get('x_pimcore_csrf_token')) {
            throw new AccessDeniedHttpException('Detected CSRF Attack! Do not do evil things with pimcore ... ;-)');
        }
    }
}
