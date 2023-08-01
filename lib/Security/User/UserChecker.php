<?php

declare(strict_types=1);

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
