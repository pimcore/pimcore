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

use Pimcore\Config;
use Pimcore\Model\DataObject\ClassDefinition\Data\Password;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\RuntimeException;
use function count;
use function get_class;
use function strlen;

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
        $settings = Config::getSystemConfiguration();
        $passwordStandard = $settings['password.standard'];

        if (
            $passwordStandard == 'pimcore' &&
            $this->isPasswordTooLong($raw)
        ) {
            throw new BadCredentialsException(
                sprintf('Password exceeds a maximum of %d characters', static::MAX_PASSWORD_LENGTH)
            );
        } elseif (
            $passwordStandard == 'bsi_standard_less' &&
            !$this->isLongLessComplexPassword($raw)
        ) {
            throw new BadCredentialsException(
                'Passwords must be at least 8 to 12 characters long
                and must consist of 4 different character types'
            );
        } elseif (
            $passwordStandard == 'bsi_standard_complex' &&
            !$this->isComplexPassword($raw)
        ) {
            throw new BadCredentialsException(
                'Passwords must be at least 25 characters long and consist of 2 character types'
            );
        }

        return $this->getFieldDefinition()->calculateHash($raw);
    }

    public function isPasswordValid(string $encoded, string $raw): bool
    {
        $settings = Config::getSystemConfiguration();
        $passwordStandard = $settings['password.standard'];

        if (
            ($passwordStandard == 'pimcore' &&
            $this->isPasswordTooLong($raw)) ||
            ($passwordStandard == 'bsi_standard_less' &&
            !$this->isLongLessComplexPassword($raw)) ||
            ($passwordStandard == 'bsi_standard_complex' &&
            !$this->isComplexPassword($raw))
        ) {
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

    private function isComplexPassword(string $password): bool
    {
        if (strlen($password) < 8 || strlen($password) > 12) {
            return false;
        }

        $uppercase = preg_match('/[A-Z]/', $password);
        $lowercase = preg_match('/[a-z]/', $password);
        $numbers = preg_match('/d/', $password);
        $specialCharacters = preg_match('/[^\w]/', $password);

        return $uppercase && $lowercase && $numbers && $specialCharacters;
    }

    private function isLongLessComplexPassword(string $password): bool
    {
        if (strlen($password) < 25) {
            return false;
        }

        $uppercase = preg_match('/[A-Z]/', $password);
        $lowercase = preg_match('/[a-z]/', $password);
        $numbers = preg_match('/d/', $password);
        $specialCharacters = preg_match('/[^\w]/', $password);

        $typesCount = count(array_filter([$uppercase, $lowercase, $numbers, $specialCharacters]));

        return $typesCount >= 2;
    }
}
