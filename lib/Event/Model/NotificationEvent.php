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

namespace Pimcore\Event\Model;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Model\Notification;
use Symfony\Contracts\EventDispatcher\Event;

class NotificationEvent extends Event
{
    use ArgumentsAwareTrait;

    protected Notification $notification;

    /**
     * DataObjectEvent constructor.
     *
     */
    public function __construct(Notification $notification, array $arguments = [])
    {
        $this->notification = $notification;
        $this->arguments = $arguments;
    }

    public function getNotification(): Notification
    {
        return $this->notification;
    }

    public function setNotification(Notification $notification): void
    {
        $this->notification = $notification;
    }
}
