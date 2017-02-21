<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller;

use Pimcore\Bundle\PimcoreAdminBundle\Security\User\User;
use Pimcore\Logger;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

abstract class AdminController extends Controller
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
     * @throws UnauthorizedHttpException
     */
    protected function checkPermission($permission)
    {
        if (!$this->getUser() || !$this->getUser()->isAllowed($permission)) {
            $message = "Attempt to access " . $permission . ", but has no permission to do so.";
            Logger::err($message);
            throw new UnauthorizedHttpException($message);
        }
    }
}
