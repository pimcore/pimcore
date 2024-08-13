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

use Pimcore\Http\RequestHelper;
use Pimcore\Model\User as UserModel;
use Pimcore\Tool\Authentication;

/**
 * Loads user either from token storage (when inside admin firewall) or directly from session and keeps it in cache. This
 * is mainly needed from event listeners outside the admin firewall to access the user object without needing to open the
 * session multiple times.
 */
class UserLoader
{
    protected ?UserModel $user = null;

    protected TokenStorageUserResolver $userResolver;

    protected RequestHelper $requestHelper;

    public function __construct(TokenStorageUserResolver $userResolver, RequestHelper $requestHelper)
    {
        $this->userResolver = $userResolver;
        $this->requestHelper = $requestHelper;
    }

    public function getUser(): ?UserModel
    {
        if (null === $this->user) {
            $user = $this->loadUser();

            if ($user) {
                $this->user = $user;
            }
        }

        return $this->user;
    }

    public function setUser(UserModel $user): void
    {
        $this->user = $user;
    }

    protected function loadUser(): ?UserModel
    {
        // authenticated admin user inside admin firewall and set on token storage
        if ($user = $this->userResolver->getUser()) {
            return $user;
        }

        // try to directly authenticate
        if ($this->requestHelper->isFrontendRequestByAdmin()) {
            return Authentication::authenticateSession($this->requestHelper->getCurrentRequest());
        }

        return null;
    }
}
