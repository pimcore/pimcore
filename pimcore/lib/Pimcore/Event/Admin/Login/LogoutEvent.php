<?php

namespace Pimcore\Event\Admin\Login;

use Pimcore\Event\AdminEvent;
use Pimcore\Event\Traits\ResponseAwareTrait;
use Pimcore\Model\User;

class LogoutEvent extends AdminEvent
{
    use ResponseAwareTrait;

    /**
     * @var User
     */
    protected $user;

    /**
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}
