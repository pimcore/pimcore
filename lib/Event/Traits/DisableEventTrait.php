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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Event\Traits;

use Symfony\Contracts\EventDispatcher\Event;

trait DisableEventTrait
{

    /**
     * @var array
     */
    protected static $disabledEvents = [];

    /**
     * Sets the list of disabled events
     *
     * @param array $events
     * @return void
     */
    public static function disableEvents(array $events = []): void
    {
        self::$disabledEvents = $events;
    }

    /**
     * Checks if an event is not currently disabled
     *
     * @param string $eventName
     * @return bool
     */
    private function isAllowedEvent(string $eventName): bool
    {
        return !in_array($eventName, self::$disabledEvents);
    }

    /**
     * Dispatches an event, after checking if the event is not previously disabled
     *
     * @param Event $event
     * @param string $name
     * @return void
     */
    protected function dispatchEvent(Event $event, string $name): void
    {
        if ($this->isAllowedEvent($name)) {
            \Pimcore::getEventDispatcher()->dispatch($event, $name);
        }
    }
}
