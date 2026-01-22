<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Security\User;

use Pimcore\Security\User\Exception\InvalidUserException;
use Pimcore\Tool\Authentication;
use Symfony\Component\Security\Core\User\InMemoryUserChecker;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * We're calling the valid user check in pre and post auth as it is cheap and
 * we're also dealing with pre authenticated tokens.
 */
class UserChecker extends InMemoryUserChecker
{
    public function checkPreAuth(UserInterface $user): void
    {
        $this->checkValidUser($user);

        parent::checkPreAuth($user);
    }

    public function checkPostAuth(UserInterface $user): void
    {
        $this->checkValidUser($user);

        /** @var User $user */
        $pimcoreUser = $user->getUser();

        // this is to reduce potential many last login update queries within a small time frame
        if ($pimcoreUser->getLastLogin() <= time() - 60) {
            $pimcoreUser->setLastLoginDate(); //set user current login date
        }

        parent::checkPostAuth($user);
    }

    private function checkValidUser(UserInterface $user): void
    {
        if (!($user instanceof User && Authentication::isValidUser($user->getUser()))) {
            $ex = new InvalidUserException('User is no valid Pimcore admin user');
            $ex->setUser($user);

            throw $ex;
        }
    }
}
