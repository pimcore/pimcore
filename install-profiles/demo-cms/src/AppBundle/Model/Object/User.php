<?php

namespace AppBundle\Model\Object;

use Pimcore\Model\Object\User as BaseUser;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Our custom user class implementing Symfony's UserInterface.
 */
class User extends BaseUser implements UserInterface
{
    /**
     * @inheritDoc
     */
    public function getSalt()
    {
        // user has no salt as we use password_hash
        // which handles the salt by itself
        return null;
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials()
    {
        // noop
    }
}
