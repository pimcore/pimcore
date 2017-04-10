<?php
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

namespace Pimcore\Security\Encoder;

use Pimcore\Model\Object\ClassDefinition\Data\Password;
use Pimcore\Model\Object\Concrete;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\RuntimeException;

/**
 * @method Concrete getUser()
 */
class PasswordFieldEncoder extends AbstractUserAwarePasswordEncoder
{
    /**
     * @var string
     */
    private $fieldName = 'password';

    /**
     * If true, the user password hash will be updated if necessary.
     *
     * @var bool
     */
    private $updateHash = true;

    /**
     * @param string $fieldName
     */
    public function __construct($fieldName)
    {
        $this->fieldName = $fieldName;
    }

    /**
     * @return bool
     */
    public function getUpdateHash()
    {
        return $this->updateHash;
    }

    /**
     * @param bool $updateHash
     */
    public function setUpdateHash($updateHash)
    {
        $this->updateHash = (bool)$updateHash;
    }

    /**
     * {@inheritdoc}
     */
    public function encodePassword($raw, $salt)
    {
        if ($this->isPasswordTooLong($raw)) {
            throw new BadCredentialsException(sprintf('Password exceeds a maximum of %d characters', static::MAX_PASSWORD_LENGTH));
        }

        return $this->getFieldDefinition()->calculateHash($raw);
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordValid($encoded, $raw, $salt)
    {
        if ($this->isPasswordTooLong($raw)) {
            return false;
        }

        return $this->getFieldDefinition()->verifyPassword($raw, $this->getUser(), $this->updateHash);
    }

    /**
     * @return Password
     */
    private function getFieldDefinition()
    {
        /* @var Password $passwordField */
        $field = $this->getUser()->getClass()->getFieldDefinition($this->fieldName);

        if (!$field || !$field instanceof Password) {
            throw new RuntimeException(sprintf(
                'Field %s for user type %s is expected to be of type %s, %s given',
                $this->fieldName,
                get_class($this->user),
                Password::class,
                is_object($field) ? get_class($field) : gettype($field)
            ));
        }

        return $field;
    }
}
