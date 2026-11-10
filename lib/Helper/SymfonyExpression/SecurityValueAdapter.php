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

namespace Pimcore\Helper\SymfonyExpression;

use Closure;
use Pimcore\Model\User;
use Pimcore\Security\User\User as SecurityUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Read-only getters of the security token and user that workflow expressions get as `token`
 * and `user`. Credentials, settings and anything that changes the user are left out.
 *
 * @internal
 */
final class SecurityValueAdapter implements ReadOnlyValueAdapterInterface
{
    public function supports(object $value): bool
    {
        return $value instanceof TokenInterface || $value instanceof UserInterface || $value instanceof User;
    }

    /**
     * @return array<string, Closure(array<mixed>): mixed>
     */
    public function getGetters(object $value): array
    {
        if ($value instanceof TokenInterface) {
            return [
                'getuser' => static fn (): ?UserInterface => $value->getUser(),
                'getuseridentifier' => static fn (): string => $value->getUserIdentifier(),
                'getrolenames' => static fn (): array => $value->getRoleNames(),
            ];
        }

        if ($value instanceof User) {
            return [
                'getid' => static fn (): ?int => $value->getId(),
                'getname' => static fn (): ?string => $value->getName(),
                'getusername' => static fn (): ?string => $value->getUsername(),
                'getfirstname' => static fn (): ?string => $value->getFirstname(),
                'getlastname' => static fn (): ?string => $value->getLastname(),
                'getfullname' => static fn (): string => $value->getFullName(),
                'getemail' => static fn (): ?string => $value->getEmail(),
                'getlanguage' => static fn (): string => $value->getLanguage(),
                'isadmin' => static fn (): bool => $value->isAdmin(),
                'getadmin' => static fn (): bool => $value->getAdmin(),
                'isactive' => static fn (): bool => $value->isActive(),
                'getroles' => static fn (): array => $value->getRoles(),
                'isallowed' => static fn (array $arguments): bool => $value->isAllowed(...$arguments),
                'getpermission' => static fn (array $arguments): bool => $value->getPermission(...$arguments),
            ];
        }

        $getters = [
            'getuseridentifier' => static fn (): string => $value->getUserIdentifier(),
            'getroles' => static fn (): array => $value->getRoles(),
        ];

        if ($value instanceof SecurityUser) {
            $getters['getid'] = static fn (): int => $value->getId();
            $getters['getuser'] = static fn (): User => $value->getUser();
        }

        return $getters;
    }
}
