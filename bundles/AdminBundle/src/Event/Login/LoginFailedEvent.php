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

namespace Pimcore\Bundle\AdminBundle\Event\Login;

use Pimcore\Model\User;
use Symfony\Contracts\EventDispatcher\Event;

class LoginFailedEvent extends Event
{
    protected array $credentials;

    protected ?User $user = null;

    public function __construct(array $credentials)
    {
        $this->credentials = $credentials;
    }

    public function getCredentials(): array
    {
        return $this->credentials;
    }

    public function getCredential(string $name, mixed $default = null): mixed
    {
        if (isset($this->credentials[$name])) {
            return $this->credentials[$name];
        }

        return $default;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @return $this
     */
    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function hasUser(): bool
    {
        return null !== $this->user;
    }
}
