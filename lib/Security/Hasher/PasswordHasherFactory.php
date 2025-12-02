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

namespace Pimcore\Security\Hasher;

use Pimcore\Security\Hasher\Factory\UserAwarePasswordHasherFactory;
use RuntimeException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherAwareInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @internal
 *
 * Password hashing and verification for Pimcore objects and admin users is implemented on the user object itself.
 * Therefore the password hasher needs the user object when encoding or verifying a password. This factory decorates the core
 * factory and allows to delegate building the password hasher to a type specific factory which then is able to create a
 * dedicated password hasher for a user object.
 *
 * If the given user is not configured to be handled by one of the password hasher factories, the normal framework password hasher
 * logic applies.
 */
class PasswordHasherFactory implements PasswordHasherFactoryInterface
{
    /**
     * @param PasswordHasherFactoryInterface[] $passwordHasherFactories
     */
    public function __construct(protected PasswordHasherFactoryInterface $frameworkFactory, protected array $passwordHasherFactories = [])
    {
    }

    public function getPasswordHasher(string|PasswordAuthenticatedUserInterface|PasswordHasherAwareInterface $user): PasswordHasherInterface
    {
        if ($hasher = $this->getPasswordHasherFromFactory($user)) {
            return $hasher;
        }

        // fall back to default implementation
        return $this->frameworkFactory->getPasswordHasher($user);
    }

    /**
     * Returns the password hasher factory to use for the given account.
     */
    private function getPasswordHasherFromFactory(string|PasswordAuthenticatedUserInterface|PasswordHasherAwareInterface $user): ?PasswordHasherInterface
    {
        $factoryKey = null;

        if ($user instanceof PasswordHasherFactoryAwareInterface) {
            $factoryName = $user->getHasherFactoryName();
            if (!array_key_exists($factoryName, $this->passwordHasherFactories)) {
                throw new RuntimeException(sprintf('The hasher factory "%s" was not configured.', $factoryName));
            }

            $factoryKey = $factoryName;
        } else {
            foreach ($this->passwordHasherFactories as $class => $factory) {
                /** @var class-string $class */
                if (is_a($user, $class, true)) {
                    $factoryKey = $class;

                    break;
                }
            }
        }

        if (null !== $factoryKey) {
            $factory = $this->passwordHasherFactories[$factoryKey];

            if ($factory instanceof UserAwarePasswordHasherFactory) {
                return $factory->getPasswordHasher($user);
            }
        }

        return null;
    }
}
