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
use Pimcore\Security\Encoder\AbstractUserAwarePasswordEncoder;
use Pimcore\Tool\Authentication;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * As pimcore needs the user information when encoding the password, every user gets his own encoder instance with a
 * user object. If user is no pimcore user, fall back to default implementation.
 *
 * @method User getUser()
 */
class AdminPasswordEncoder extends AbstractUserAwarePasswordEncoder
{
    /**
     * @inheritDoc
     */
    public function encodePassword($raw, $salt)
    {
        if ($this->isPasswordTooLong($raw)) {
            throw new BadCredentialsException(sprintf('Password exceeds a maximum of %d characters', static::MAX_PASSWORD_LENGTH));
        }

        return Authentication::getPasswordHash($this->getUser()->getUsername(), $raw);
    }

    /**
     * @inheritDoc
     */
    public function isPasswordValid($encoded, $raw, $salt)
    {
        if ($this->isPasswordTooLong($raw)) {
            return false;
        }

        return Authentication::verifyPassword($this->getUser()->getUser(), $raw);
    }
}
