<?php

declare(strict_types=1);

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

use Pimcore\Bundle\AdminBundle\Security\User\Exception\InvalidUserException;
use Pimcore\Tool\Authentication;
use Symfony\Component\Security\Core\User\UserChecker as BaseUserChecker;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * We're calling the valid user check in pre and post auth as it is cheap and
 * we're also dealing with pre authenticated tokens.
 */
class UserChecker extends BaseUserChecker
{
    /**
     * @inheritDoc
     */
    public function checkPreAuth(UserInterface $user)
    {
        $this->checkValidUser($user);

        parent::checkPreAuth($user);
    }

    /**
     * @inheritDoc
     */
    public function checkPostAuth(UserInterface $user)
    {
        $this->checkValidUser($user);

        parent::checkPostAuth($user);
    }

    private function checkValidUser(UserInterface $user)
    {
        if (!($user instanceof User && Authentication::isValidUser($user->getUser()))) {
            $ex = new InvalidUserException('User is no valid Pimcore admin user');
            $ex->setUser($user);

            throw $ex;
        }
    }
}
