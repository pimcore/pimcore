<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Security\Encoder;

use Pimcore\Bundle\PimcoreAdminBundle\Security\User\User;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

/**
 * As pimcore needs the user information when encoding the password, every user gets his own encoder instance with a user
 * object. If user is no pimcore user, fall back to default implementation.
 */
class EncoderFactory implements EncoderFactoryInterface
{
    /**
     * @var EncoderFactoryInterface
     */
    protected $decoratedFactory;

    /**
     * @var PasswordEncoder
     */
    protected $userEncoders = [];

    /**
     * @inheritDoc
     */
    public function getEncoder($user)
    {
        if ($user instanceof User) {
            if (!isset($this->userEncoders[$user->getId()])) {
                $this->userEncoders[$user->getId()] = new PasswordEncoder($user);
            }

            return $this->userEncoders[$user->getId()];
        }

        return $this->decoratedFactory->getEncoder($user);
    }
}
