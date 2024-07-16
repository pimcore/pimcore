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
