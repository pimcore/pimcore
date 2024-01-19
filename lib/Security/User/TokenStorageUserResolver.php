<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Security\User;

use Pimcore\Model\User;
use Pimcore\Security\User\User as UserProxy;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Resolves the current pimcore user from the token storage.
 */
class TokenStorageUserResolver
{
    protected TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function getUser(): ?User
    {
        if ($proxy = $this->getUserProxy()) {
            return $proxy->getUser();
        }

        return null;
    }

    /**
     * Taken and adapted from framework base controller.
     *
     * The proxy is the wrapping Pimcore\Security\User\User object implementing UserInterface.
     *
     */
    public function getUserProxy(): ?\Pimcore\Security\User\User
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
