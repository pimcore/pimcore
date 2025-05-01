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

namespace Pimcore\Event\Model;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Model\User\AbstractUser;
use Symfony\Contracts\EventDispatcher\Event;

class UserRoleEvent extends Event
{
    use ArgumentsAwareTrait;

    protected AbstractUser $userRole;

    /**
     * DocumentEvent constructor.
     *
     */
    public function __construct(AbstractUser $userRole, array $arguments = [])
    {
        $this->userRole = $userRole;
        $this->arguments = $arguments;
    }

    public function getUserRole(): AbstractUser
    {
        return $this->userRole;
    }

    public function setUserRole(AbstractUser $userRole): void
    {
        $this->userRole = $userRole;
    }
}
