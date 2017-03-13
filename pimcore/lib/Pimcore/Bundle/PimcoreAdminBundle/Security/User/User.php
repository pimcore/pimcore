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

namespace Pimcore\Bundle\PimcoreAdminBundle\Security\User;

use Pimcore\Model\User as PimcoreUser;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Proxy user to pimcore model and expose roles as ROLE_* array. If we can safely change the roles on the user model
 * this proxy can be removed and the UserInterface can directly be implemented on the model.
 */
class User implements UserInterface, EquatableInterface
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
            $role = PimcoreUser\Role::getById($roleId);

            $roles[] = 'ROLE_' . strtoupper($role->getName());
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
        return $user instanceof User && $user->getId() === $this->getId();
    }
}
