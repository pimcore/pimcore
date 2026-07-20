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

use Pimcore\Event\AssetEvents;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\UserRoleEvents;
use Pimcore\Model\Element\PermissionCache;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Invalidates the request-scoped element permission cache whenever the underlying workspace data can
 * change within the same request/message:
 *
 * - user/role add/update/delete, because element workspaces are persisted together with their
 *   user/role, so this also covers users_workspaces_* writes;
 * - element add/update/delete, because moving or renaming an element rewrites the workspace cpaths
 *   of the element and its children (see Dao::updateWorkspaces()/updateChildPaths()), which would
 *   otherwise leave an allow/deny cached before the move stale for the rest of the request.
 *
 * The per-request/per-message boundary is handled separately by the kernel.reset tag on
 * {@see PermissionCache}.
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
            UserRoleEvents::POST_ADD => 'onPermissionDataChange',
            UserRoleEvents::POST_UPDATE => 'onPermissionDataChange',
            UserRoleEvents::POST_DELETE => 'onPermissionDataChange',
            AssetEvents::POST_ADD => 'onPermissionDataChange',
            AssetEvents::POST_UPDATE => 'onPermissionDataChange',
            AssetEvents::POST_DELETE => 'onPermissionDataChange',
            DocumentEvents::POST_ADD => 'onPermissionDataChange',
            DocumentEvents::POST_UPDATE => 'onPermissionDataChange',
            DocumentEvents::POST_DELETE => 'onPermissionDataChange',
            DataObjectEvents::POST_ADD => 'onPermissionDataChange',
            DataObjectEvents::POST_UPDATE => 'onPermissionDataChange',
            DataObjectEvents::POST_DELETE => 'onPermissionDataChange',
        ];
    }

    public function onPermissionDataChange(): void
    {
        $this->permissionCache->reset();
    }
}
