<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller;

use Pimcore\Bundle\PimcoreAdminBundle\Security\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

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
}
