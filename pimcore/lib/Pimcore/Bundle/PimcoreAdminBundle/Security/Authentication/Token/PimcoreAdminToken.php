<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Security\Authentication\Token;

use Pimcore\Bundle\PimcoreAdminBundle\Security\User\User;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class PimcoreAdminToken extends AbstractToken
{
    /**
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->setUser($user);
        parent::__construct($user->getRoles());
    }

    /**
     * @inheritDoc
     */
    public function getCredentials()
    {
        return '';
    }
}
