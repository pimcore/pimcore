<?php

namespace Pimcore\Event;

final class SystemEvents
{
    /**
     * This event is fired on shutdown (register_shutdown_function)
     * @Event()
     * @var string
     */
    const SHUTDOWN = 'pimcore.system.shutdown';

    /**
     * Use this event to register your own maintenance jobs, this event is triggered just before the jobs are executed
     * @Event("Pimcore\Event\System\MaintenanceEvent")
     * @var string
     */
    const MAINTENANCE = 'pimcore.system.maintenance';

    /**
     * 	See Console / CLI | allow to register console commands (e.g. through plugins)
     * @Event("Pimcore\Event\System\ConsoleEvent")
     * @var string
     */
    const CONSOLE_INIT = "pimcore.system.console.init";

    /**
     * Fires when the PHP-DI Container is built, used for building Assets, Documents and Objects
     * @Event("Pimcore\Event\System\PhpDiBuilderEvent")
     * @var string
     */
    const PHP_DI_INIT = "pimcore.system.php_di.init";

    /**
     * This event is fired on maintenance mode activation
     * @Event()
     * @var string
     */
    const MAINTENANCE_MODE_ACTIVATE = "pimcore.system.maintenance_mode.activate";

    /**
     * This event is fired on maintenance mode deactivation
     * @Event()
     * @var string
     */
    const MAINTENANCE_MODE_DEACTIVATE = "pimcore.system.maintenance_mode.deactivate";

    /**
     * This event is fired on Full-Page Cache clear
     * @Event()
     * @var string
     */
    const CACHE_CLEAR_FULLPAGE_CACHE = "pimcore.system.cache.clearFullpageCache";

    /**
     * This event is fired on Cache clear
     * @Event()
     * @var string
     */
    const CACHE_CLEAR = "pimcore.system.cache.clear";

    /**
     * This event is fired on Temporary Files clear
     * @Event()
     * @var string
     */
    const CACHE_CLEAR_TEMPORARY_FILES = "pimcore.system.cache.clearTemporaryFiles";

    /**
     * This event is fired on Temporary Files clear
     * @Event("\Symfony\Component\EventDispatcher\GenericEvent")
     * @var string
     */
    const SERVICE_PRE_GET_VALID_KEY = "pimcore.system.service.preGetValidKey";
}