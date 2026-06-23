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

namespace Pimcore\Workflow\Notification;

trait NotificationTrait
{
    public function getNotifyUsers(): array
    {
        return $this->options['notifyUsers'] ?? [];
    }

    public function getNotifyRoles(): array
    {
        return $this->options['notifyRoles'] ?? [];
    }

    public function getNotificationSettings(): array
    {
        return $this->options['notificationSettings'] ?? [];
    }
}
