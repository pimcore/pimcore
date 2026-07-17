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
 * kernel.reset tag (ResetInterface). Workflow-deny handling and the ELEMENT_PERMISSION_IS_ALLOWED
 * event are intentionally kept outside this cache by the caller, because listeners may decide
 * dynamically on every call.
 *
 * @internal
 */
final class PermissionCache implements ResetInterface
{
    /**
     * @var array<string, bool>
     */
    private array $cache = [];

    public function get(User $user, ElementInterface $element, string $type): ?bool
    {
        return $this->cache[$this->buildKey($user, $element, $type)] ?? null;
    }

    public function set(User $user, ElementInterface $element, string $type, bool $allowed): void
    {
        $this->cache[$this->buildKey($user, $element, $type)] = $allowed;
    }

    public function reset(): void
    {
        $this->cache = [];
    }

    private function buildKey(User $user, ElementInterface $element, string $type): string
    {
        return implode('-', [
            $user->getId(),
            Service::getElementType($element),
            $element->getId(),
            $type,
        ]);
    }
}
