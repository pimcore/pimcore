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

namespace Pimcore\Security\Encoder;

use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\RuntimeException;
use Symfony\Component\Security\Core\User\UserInterface;

interface UserAwarePasswordEncoderInterface extends PasswordEncoderInterface
{
    /**
     * Set the user
     *
     * @param UserInterface $user
     *
     * @throws RuntimeException
     *      if the user is already set to prevent overwriting the scoped user object
     */
    public function setUser(UserInterface $user);

    /**
     * Get the user object
     *
     * @return UserInterface
     *
     * @throws RuntimeException
     *      if no user was set
     */
    public function getUser();
}
