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

namespace Pimcore\Model\User;

use Exception;
use Pimcore\Model\User;
use Pimcore\Model\User\Exception\DuplicateUserNameException;
use Pimcore\Model\User\Exception\InvalidServiceUserException;

/**
 * A machine identity for integrations, agents and pipelines, stored in `users` with the type
 * `serviceuser`.
 *
 * A service user is authorized like any user: permissions, roles, workspaces and class and document
 * type restrictions apply unchanged. It never logs in interactively: it has no password, is never an
 * administrator and has no two-factor authentication. User::getByName() and \Pimcore\Model\User\Listing
 * return users only; use ServiceUser::getByName() and \Pimcore\Model\User\ServiceUser\Listing for
 * service users. User::getById() returns a ServiceUser for a service user id. Names are unique across
 * users and service users, and the type of an existing user or service user never changes.
 *
 * @method Dao getDao()
 */
class ServiceUser extends User
{
    private const TWO_FACTOR_DEFAULTS = [
        'required' => false,
        'enabled' => false,
        'secret' => '',
        'type' => '',
    ];

    protected string $type = 'serviceuser';

    /**
     * A service user never has a password.
     */
    public function getPassword(): ?string
    {
        return null;
    }

    /**
     * @return $this
     *
     * @throws InvalidServiceUserException when a password is given
     */
    public function setPassword(?string $password): static
    {
        if ($password !== null) {
            throw new InvalidServiceUserException('A service user cannot have a password.');
        }

        $this->password = null;

        return $this;
    }

    /**
     * A service user is never an administrator.
     */
    public function getAdmin(): bool
    {
        return false;
    }

    /**
     * @return $this
     *
     * @throws InvalidServiceUserException when administrator rights are requested
     */
    public function setAdmin(bool $admin): static
    {
        if ($admin) {
            throw new InvalidServiceUserException('A service user cannot be an administrator.');
        }

        $this->admin = false;

        return $this;
    }

    /**
     * A service user has no two-factor authentication; this always returns the disabled defaults.
     */
    public function getTwoFactorAuthentication(?string $key = null): mixed
    {
        if ($key) {
            return self::TWO_FACTOR_DEFAULTS[$key] ?? null;
        }

        return self::TWO_FACTOR_DEFAULTS;
    }

    /**
     * Accepts only data that leaves two-factor authentication disabled and without a secret.
     *
     * @param array<string, mixed>|string $key
     *
     * @throws InvalidServiceUserException when the data enables two-factor authentication or holds a secret
     */
    public function setTwoFactorAuthentication(array|string $key, mixed $value = null): void
    {
        parent::setTwoFactorAuthentication($key, $value);

        if ($this->hasTwoFactorData()) {
            $this->twoFactorAuthentication = null;

            throw new InvalidServiceUserException('A service user cannot use two-factor authentication.');
        }
    }

    /**
     * @return $this
     *
     * @throws InvalidServiceUserException if the type was changed or the service user has a password,
     *     administrator rights or two-factor data
     * @throws DuplicateUserNameException if a user already has the name
     * @throws Exception
     */
    public function save(array $parameters = []): static
    {
        if ($this->getType() !== 'serviceuser') {
            throw new InvalidServiceUserException('The type of a service user cannot be changed.');
        }

        if ($this->password !== null || $this->admin || $this->hasTwoFactorData()) {
            throw new InvalidServiceUserException(
                'A service user cannot have a password, administrator rights or two-factor authentication.'
            );
        }

        return parent::save($parameters);
    }

    private function hasTwoFactorData(): bool
    {
        $data = $this->twoFactorAuthentication;
        if (!is_array($data)) {
            return false;
        }

        return ($data['enabled'] ?? false) === true
            || ($data['required'] ?? false) === true
            || (string) ($data['secret'] ?? '') !== ''
            || (string) ($data['type'] ?? '') !== '';
    }
}
