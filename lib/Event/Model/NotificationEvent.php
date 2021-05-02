<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Event\Model;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Model\Notification;
use Symfony\Contracts\EventDispatcher\Event;

class NotificationEvent extends Event
{
    use ArgumentsAwareTrait;

    /**
     * @var Notification
     */
    protected $notification;

    /**
     * DataObjectEvent constructor.
     *
     * @param Notification $notification
     * @param array $arguments
     */
    public function __construct(Notification $notification, array $arguments = [])
    {
        $this->notification = $notification;
        $this->arguments = $arguments;
    }

    /**
     * @return Notification
     */
    public function getNotification()
    {
        return $this->notification;
    }

    /**
     * @param Notification $notification
     */
    public function setNotification($notification)
    {
        $this->notification = $notification;
    }
}
