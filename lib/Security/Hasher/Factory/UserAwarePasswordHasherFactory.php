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

namespace Pimcore\Security\Hasher\Factory;

use Pimcore\Security\Exception\ConfigurationException;
use Pimcore\Security\Hasher\UserAwarePasswordHasherInterface;
use RuntimeException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherAwareInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @internal
 *
 * Password Hasher factory keeping a dedicated hasher instance per user object. This is needed as Pimcore Users and user
 * objects containing Password field definitions handle their encoding logic by themself. The user aware hasher
 * delegates encoding and verification to the user object.
 *
 * Example DI configuration for a factory building PasswordFieldHasher instances which get 'password' as argument:
 *
 *      website_demo.security.password_hasher_factory:
 *          class: Pimcore\Security\Hasher\Factory\UserAwarePasswordHasherFactory
 *          arguments:
 *              - Pimcore\Security\Hasher\PasswordFieldHasher
 *              - ['password']
 */
class UserAwarePasswordHasherFactory extends AbstractHasherFactory
{
    /**
     * @var PasswordHasherInterface[]
     */
    private array $hashers = [];

    public function getPasswordHasher(string|PasswordAuthenticatedUserInterface|PasswordHasherAwareInterface $user): PasswordHasherInterface
    {
        if (!$user instanceof UserInterface) {
            throw new RuntimeException(sprintf(
                'Need an instance of UserInterface to build a password hasher, "%s" given',
                is_object($user) ? get_class($user) : gettype($user)
            ));
        }

        $userIdentifier = $user->getUserIdentifier();

        if (isset($this->hashers[$userIdentifier])) {
            return $this->hashers[$userIdentifier];
        }

        $reflector = $this->getReflector();
        if (!$reflector->implementsInterface(UserAwarePasswordHasherInterface::class)) {
            throw new ConfigurationException('A password hasher built by the UserAwarePasswordHasherFactory must implement UserAwarePasswordHasherInterface');
        }

        $hasher = $this->buildPasswordHasher($reflector);

        if ($hasher instanceof UserAwarePasswordHasherInterface) {
            $hasher->setUser($user);
        }

        $this->hashers[$userIdentifier] = $hasher;

        return $hasher;
    }
}
