<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Event;

final class SystemEvents
{
    /**
     * This event is fired on shutdown (register_shutdown_function)
     *
     * @Event()
     *
     * @var string
     */
    const SHUTDOWN = 'pimcore.system.shutdown';

    /**
     * 	See Console / CLI | allow to register console commands (e.g. through plugins)
     *
     * @Event("Pimcore\Event\System\ConsoleEvent")
     *
     * @var string
     */
    const CONSOLE_INIT = 'pimcore.system.console.init';

    /**
     * This event is fired on maintenance mode activation
     *
     * @Event()
     *
     * @var string
     */
    const MAINTENANCE_MODE_ACTIVATE = 'pimcore.system.maintenance_mode.activate';

    /**
     * This event is fired on maintenance mode deactivation
     *
     * @Event()
     *
     * @var string
     */
    const MAINTENANCE_MODE_DEACTIVATE = 'pimcore.system.maintenance_mode.deactivate';

    /**
     * This event is fired when maintenance mode is scheduled for the next login
     *
     * @Event()
     *
     * @var string
     */
    const MAINTENANCE_MODE_SCHEDULE_LOGIN = 'pimcore.system.maintenance_mode.schedule_login';

    /**
     * This event is fired when maintenance mode is unscheduled
     *
     * @Event()
     *
     * @var string
     */
    const MAINTENANCE_MODE_UNSCHEDULE_LOGIN = 'pimcore.system.maintenance_mode.unschedule_login';

    /**
     * This event is fired on Full-Page Cache clear
     *
     * @Event()
     *
     * @var string
     */
    const CACHE_CLEAR_FULLPAGE_CACHE = 'pimcore.system.cache.clearFullpageCache';

    /**
     * This event is fired on Cache clear
     *
     * @Event()
     *
     * @var string
     */
    const CACHE_CLEAR = 'pimcore.system.cache.clear';

    /**
     * This event is fired on Temporary Files clear
     *
     * @Event()
     *
     * @var string
     */
    const CACHE_CLEAR_TEMPORARY_FILES = 'pimcore.system.cache.clearTemporaryFiles';

    /**
     * This event is fired before Pimcore adjusts element keys to generic rules
     *
     * @Event("\Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    const SERVICE_PRE_GET_VALID_KEY = 'pimcore.system.service.preGetValidKey';

    /**
     * This event is fired before element service returns deep copy instance
     *
     * Arguments:
     *  - copier | deep copy instance
     *  - element | source element for deep copy
     *  - context | context info array i.e. 'source' => calling method, 'conversion' => 'marshal'/'unmarshal', 'defaultFilter' => true/false
     *
     * @Event("\Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    const SERVICE_PRE_GET_DEEP_COPY = 'pimcore.system.service.preGetDeepCopy';
}
