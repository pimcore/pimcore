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

namespace Pimcore\Security\Encoder;

use Pimcore\Model\DataObject\ClassDefinition\Data\Password;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\RuntimeException;

/**
 * @internal
 *
 * @deprecated
 *
 * @method Concrete getUser()
 */
class PasswordFieldEncoder extends AbstractUserAwarePasswordEncoder
{
    protected string $fieldName = 'password';

    /**
     * If true, the user password hash will be updated if necessary.
     *
     * @var bool
     */
    protected bool $updateHash = true;

    public function __construct(string $fieldName)
    {
        $this->fieldName = $fieldName;
    }

    public function getUpdateHash(): bool
    {
        return $this->updateHash;
    }

    public function setUpdateHash(bool $updateHash)
    {
        $this->updateHash = (bool)$updateHash;
    }

    /**
     * {@inheritdoc}
     */
    public function encodePassword($raw, $salt): bool|string|null
    {
        if ($this->isPasswordTooLong($raw)) {
            throw new BadCredentialsException(sprintf('Password exceeds a maximum of %d characters', static::MAX_PASSWORD_LENGTH));
        }

        return $this->getFieldDefinition()->calculateHash($raw);
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordValid($encoded, $raw, $salt): bool
    {
        if ($this->isPasswordTooLong($raw)) {
            return false;
        }

        return $this->getFieldDefinition()->verifyPassword($raw, $this->getUser(), $this->updateHash);
    }

    /**
     * @return Password
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
}
