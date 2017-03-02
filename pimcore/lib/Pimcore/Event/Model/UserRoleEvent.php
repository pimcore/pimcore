<?php

namespace Pimcore\Event\Model;

use Pimcore\Model\User\AbstractUser;
use Symfony\Component\EventDispatcher\Event;

class UserRoleEvent extends Event {

    /**
     * @var AbstractUser
     */
    protected $userRole;

    /**
     * DocumentEvent constructor.
     * @param AbstractUser $userRole
     */
    function __construct(AbstractUser $userRole)
    {
        $this->userRole = $userRole;
    }

    /**
     * @return AbstractUser
     */
    public function getUserRole()
    {
        return $this->userRole;
    }

    /**
     * @param AbstractUser $userRole
     */
    public function setUserRole($userRole)
    {
        $this->userRole = $userRole;
    }
}
