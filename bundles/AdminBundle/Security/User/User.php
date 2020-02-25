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

namespace Pimcore\Bundle\AdminBundle\Security\User;

use Pimcore\Model\User as PimcoreUser;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface as GoogleTwoFactorInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Proxy user to pimcore model and expose roles as ROLE_* array. If we can safely change the roles on the user model
 * this proxy can be removed and the UserInterface can directly be implemented on the model.
 */
class User implements UserInterface, EquatableInterface, GoogleTwoFactorInterface
{
    /**
     * @var PimcoreUser
     */
    protected $user;

    /**
     * @param PimcoreUser $user
     */
    public function __construct(PimcoreUser $user)
    {
        $this->user = $user;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->user->getId();
    }

    /**
     * @inheritDoc
     */
    public function getUsername()
    {
        return $this->user->getName();
    }

    /**
     * @return PimcoreUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @inheritDoc
     */
    public function getRoles()
    {
        $roles = [];

        if ($this->user->isAdmin()) {
            $roles[] = 'ROLE_PIMCORE_ADMIN';
        } else {
            $roles[] = 'ROLE_PIMCORE_USER';
        }

        foreach ($this->user->getRoles() as $roleId) {
            /** @var PimcoreUser\Role $role */
            if ($role = PimcoreUser\Role::getById($roleId)) {
                $roles[] = 'ROLE_' . strtoupper($role->getName());
            }
        }

        return $roles;
    }

    /**
     * @inheritDoc
     */
    public function getPassword()
    {
        return $this->user->getPassword();
    }

    /**
     * @inheritDoc
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
        // TODO: anything to do here?
    }

    /**
     * @inheritDoc
     */
    public function isEqualTo(UserInterface $user)
    {
        return $user instanceof self && $user->getId() === $this->getId();
    }

    /**
     * Return true if the user should do two-factor authentication.
     *
     * @return bool
     */
    public function isGoogleAuthenticatorEnabled(): bool
    {
        if ($this->user->getTwoFactorAuthentication('enabled')) {
            return true;
        }

        return false;
    }

    /**
     * Return the user name.
     *
     * @return string
     */
    public function getGoogleAuthenticatorUsername(): string
    {
        return $this->user->getName();
    }

    /**
     * Return the Google Authenticator secret
     * When an empty string or null is returned, the Google authentication is disabled.
     *
     * @return string
     */
    public function getGoogleAuthenticatorSecret(): string
    {
        if ($this->isGoogleAuthenticatorEnabled()) {
            $secret = $this->user->getTwoFactorAuthentication('secret');
            if (!$secret) {
                // we return a dummy token
                $twoFactorService = \Pimcore::getContainer()->get('scheb_two_factor.security.google_authenticator');

                return $twoFactorService->generateSecret();
            }

            return $secret;
        }

        return '';
    }
}
