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

namespace Pimcore\Event\Traits;

use Pimcore;

/**
 * @internal
 */
trait RecursionBlockingEventDispatchHelperTrait
{
    private array $activeDispatchingEvents = [];

    /**
     * Dispatches an event, avoids recursion by checking if the active dispatch event is the same
     *
     *
     */
    protected function dispatchEvent(object $event, ?string $eventName = null): void
    {
        $eventName ??= get_class($event);
        if (!isset($this->activeDispatchingEvents[$eventName])) {
            $this->activeDispatchingEvents[$eventName] = true;
            Pimcore::getEventDispatcher()->dispatch($event, $eventName);
            unset($this->activeDispatchingEvents[$eventName]);
        }
    }
}
