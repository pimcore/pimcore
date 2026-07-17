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

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Pimcore\Event\UserRoleEvents;
use Pimcore\Model\Element\PermissionCache;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Invalidates the request-scoped element permission cache when a user or role is added, updated or
 * deleted. Because element workspaces are persisted together with their user/role, this also covers
 * users_workspaces_* writes. The per-request/per-message boundary is handled separately by the
 * kernel.reset tag on {@see PermissionCache}.
 *
 * @internal
 */
final class PermissionCacheInvalidationListener implements EventSubscriberInterface
{
    public function __construct(private readonly PermissionCache $permissionCache)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserRoleEvents::POST_ADD => 'onUserRoleChange',
            UserRoleEvents::POST_UPDATE => 'onUserRoleChange',
            UserRoleEvents::POST_DELETE => 'onUserRoleChange',
        ];
    }

    public function onUserRoleChange(): void
    {
        $this->permissionCache->reset();
    }
}
