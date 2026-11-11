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

namespace Pimcore\Model\Element;

use Pimcore\Model\User;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Request-scoped memoization of element permission lookups. It caches only the raw DAO result of
 * AbstractElement::isAllowed()/getUserPermissions() so that repeated permission checks on the same
 * element within a single request do not re-query the users_workspaces_* tables.
 *
 * The cache is cleared automatically at the request and messenger-message boundary via the
 * kernel.reset tag (ResetInterface), and on any permission-affecting mutation (user/role or element
 * add/update/move/delete) by {@see \Pimcore\Bundle\CoreBundle\EventListener\PermissionCacheInvalidationListener}.
 * Workflow-deny handling and the ELEMENT_PERMISSION_IS_ALLOWED event are intentionally kept outside
 * this cache by the caller, because listeners may decide dynamically on every call.
 *
 * The key is scoped by {@see PermissionCacheScope} because the single- and batch-permission DAO
 * paths are not interchangeable, and includes the user's role set so a role change that is applied
 * in-memory before it is persisted cannot serve a stale result. Elements without an id (not yet
 * saved) are not cached, because they share no stable identity.
 *
 * @internal
 */
final class PermissionCache implements ResetInterface
{
    /**
     * @var array<string, bool>
     */
    private array $cache = [];

    public function get(User $user, ElementInterface $element, string $type, PermissionCacheScope $scope): ?bool
    {
        if (null === $element->getId()) {
            return null;
        }

        return $this->cache[$this->buildKey($user, $element, $type, $scope)] ?? null;
    }

    public function set(User $user, ElementInterface $element, string $type, PermissionCacheScope $scope, bool $allowed): void
    {
        if (null === $element->getId()) {
            return;
        }

        $this->cache[$this->buildKey($user, $element, $type, $scope)] = $allowed;
    }

    public function reset(): void
    {
        $this->cache = [];
    }

    private function buildKey(User $user, ElementInterface $element, string $type, PermissionCacheScope $scope): string
    {
        $roles = $user->getRoles();
        sort($roles);

        return implode('-', [
            $scope->value,
            $user->getId(),
            implode(',', $roles),
            Service::getElementType($element),
            $element->getId(),
            $type,
        ]);
    }
}
