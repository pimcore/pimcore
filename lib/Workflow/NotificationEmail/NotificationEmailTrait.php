<?php

namespace Pimcore\Workflow\NotificationEmail;

trait NotificationEmailTrait
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
