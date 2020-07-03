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

use Pimcore\Bundle\AdminBundle\Security\User\User as UserProxy;
use Pimcore\Model\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Resolves the current pimcore user from the token storage.
 */
class TokenStorageUserResolver
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return User|null
     */
    public function getUser()
    {
        if ($proxy = $this->getUserProxy()) {
            return $proxy->getUser();
        }

        return null;
    }

    /**
     * Taken and adapted from framework base controller.
     *
     * The proxy is the wrapping Pimcore\Bundle\AdminBundle\Security\User\User object implementing UserInterface.
     *
     * @return UserProxy|null
     */
    public function getUserProxy()
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return null;
        }

        if ($user instanceof UserProxy) {
            return $user;
        }

        return null;
    }
}
