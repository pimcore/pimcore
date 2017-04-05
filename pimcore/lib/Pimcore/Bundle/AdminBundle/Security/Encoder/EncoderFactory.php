<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\AdminBundle\Security\Encoder;

use Pimcore\Bundle\AdminBundle\Security\User\User;
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
     * @param EncoderFactoryInterface $decoratedFactory
     */
    public function __construct(EncoderFactoryInterface $decoratedFactory)
    {
        $this->decoratedFactory = $decoratedFactory;
    }

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
