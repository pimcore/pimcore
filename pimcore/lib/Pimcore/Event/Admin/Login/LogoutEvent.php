<?php

namespace Pimcore\Event\Admin\Login;

use Pimcore\Event\Traits\RequestAwareTrait;
use Pimcore\Event\Traits\ResponseAwareTrait;
use Pimcore\Model\User;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class LogoutEvent extends Event
{
    use RequestAwareTrait;
    use ResponseAwareTrait;

    /**
     * @var User
     */
    protected $user;

    /**
     * @param Request $request
     * @param User $user
     */
    public function __construct(Request $request, User $user)
    {
        $this->request = $request;
        $this->user    = $user;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}
