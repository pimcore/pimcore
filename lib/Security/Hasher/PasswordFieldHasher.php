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

namespace Pimcore\Security\Hasher;

use Pimcore\Model\DataObject\ClassDefinition\Data\Password;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\RuntimeException;

/**
 * @internal
 *
 * @method Concrete getUser()
 */
class PasswordFieldHasher extends AbstractUserAwarePasswordHasher
{
    use CheckPasswordLengthTrait;

    protected string $fieldName;

    /**
     * If true, the user password hash will be updated if necessary.
     *
     */
    protected bool $updateHash = true;

    public function __construct(string $fieldName = 'password')
    {
        $this->fieldName = $fieldName;
    }

    public function getUpdateHash(): bool
    {
        return $this->updateHash;
    }

    public function setUpdateHash(bool $updateHash): void
    {
        $this->updateHash = $updateHash;
    }

    public function hashPassword(string $raw, ?string $salt): string
    {
        if ($this->isPasswordTooLong($raw)) {
            throw new BadCredentialsException(sprintf('Password exceeds a maximum of %d characters', static::MAX_PASSWORD_LENGTH));
        }

        return $this->getFieldDefinition()->calculateHash($raw);
    }

    public function isPasswordValid(string $encoded, string $raw): bool
    {
        if ($this->isPasswordTooLong($raw)) {
            return false;
        }

        return $this->getFieldDefinition()->verifyPassword($raw, $this->getUser(), $this->updateHash);
    }

    /**
     *
     * @throws RuntimeException
     */
    protected function getFieldDefinition(): Password
    {
        $field = $this->getUser()->getClass()->getFieldDefinition($this->fieldName);

        if (!$field instanceof Password) {
            throw new RuntimeException(sprintf(
                'Field %s for user type %s is expected to be of type %s, %s given',
                $this->fieldName,
                get_class($this->user),
                Password::class,
                get_debug_type($field)
            ));
        }

        return $field;
    }

    public function verify(string $hashedPassword, string $plainPassword, ?string $salt = null): bool
    {
        return $this->getFieldDefinition()->verifyPassword($plainPassword, $this->getUser(), $this->updateHash);
    }
}
