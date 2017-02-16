<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Security\Encoder;

use Pimcore\Bundle\PimcoreAdminBundle\Security\User\User;
use Pimcore\Tool\Authentication;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class PasswordEncoder implements PasswordEncoderInterface
{
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
     * @inheritDoc
     */
    public function encodePassword($raw, $salt)
    {
        return Authentication::getPasswordHash($this->user->getUsername(), $raw);
    }

    /**
     * @inheritDoc
     */
    public function isPasswordValid($encoded, $raw, $salt)
    {
        return Authentication::verifyPassword($this->user->getUser(), $raw);
    }
}
