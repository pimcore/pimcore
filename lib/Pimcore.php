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
use Pimcore\Cache;
use Pimcore\File;
use Pimcore\Model;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class Pimcore
{
    /**
     * @var bool|null
     */
    public static $adminMode;

    /**
     * @var bool|null
     */
    protected static $debugMode;

    /**
     * @var bool|null
     */
    protected static $devMode;

    /**
     * @var bool
     */
    private static $inShutdown = false;

    /**
     * @var bool
     */
    private static $shutdownEnabled = true;

    /**
     * @var KernelInterface
     */
    private static $kernel;

    /**
     * @var \Composer\Autoload\ClassLoader
     */
    private static $autoloader;

    /**
     * @return bool
     */
    public static function inDebugMode(): bool
    {
        return (bool) self::$debugMode;
    }

    /**
     * @internal
     *
     * @return bool|null
     */
    public static function getDebugMode(): ?bool
    {
        return self::$debugMode;
    }

    /**
     * @internal
     *
     * @param bool $debugMode
     */
    public static function setDebugMode(bool $debugMode): void
    {
        self::$debugMode = $debugMode;
    }

    /**
     * @return bool
     */
    public static function inDevMode(): bool
    {
        return (bool) self::$devMode;
    }

    /**
     * @internal
     *
     * @return bool|null
     */
    public static function getDevMode(): ?bool
    {
        return self::$devMode;
    }

    /**
     * @internal
     *
     * @param bool $devMode
     */
    public static function setDevMode(bool $devMode): void
    {
        self::$devMode = $devMode;
    }

    /**
     * switches pimcore into the admin mode - there you can access also unpublished elements, ....
     *
     * @static
     */
    public static function setAdminMode()
    {
        self::$adminMode = true;
    }

    /**
     * switches back to the non admin mode, where unpublished elements are invisible
     *
     * @static
     */
    public static function unsetAdminMode()
    {
        self::$adminMode = false;
    }

    /**
     * check if the process is currently in admin mode or not
     *
     * @static
     *
     * @return bool
     */
    public static function inAdmin()
    {
        if (self::$adminMode !== null) {
            return self::$adminMode;
        }

        return false;
    }

    /**
     * @return bool
     */
    public static function isInstalled()
    {
        try {
            \Pimcore\Db::get()->query('SELECT VERSION()');

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    public static function getEventDispatcher()
    {
        return self::getContainer()->get('event_dispatcher');
    }

    /**
     * @return KernelInterface
     */
    public static function getKernel()
    {
        return static::$kernel;
    }

    /**
     * @return bool
     */
    public static function hasKernel()
    {
        if (static::$kernel) {
            return true;
        }

        return false;
    }

    /**
     * @param KernelInterface $kernel
     */
    public static function setKernel(KernelInterface $kernel)
    {
        static::$kernel = $kernel;
    }

    /**
     * Accessing the container this way is discouraged as dependencies should be wired through the container instead of
     * needing to access the container directly. This exists mainly for compatibility with legacy code.
     *
     * @internal
     *
     * @return ContainerInterface
     */
    public static function getContainer()
    {
        return static::getKernel()->getContainer();
    }

    /**
     * @return bool
     */
    public static function hasContainer()
    {
        if (static::hasKernel()) {
            $container = static::getContainer();
            if ($container) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getAutoloader(): \Composer\Autoload\ClassLoader
    {
        return self::$autoloader;
    }

    /**
     * @param \Composer\Autoload\ClassLoader $autoloader
     */
    public static function setAutoloader(\Composer\Autoload\ClassLoader $autoloader)
    {
        self::$autoloader = $autoloader;
    }

    /**
     * Forces a garbage collection.
     *
     * @static
     *
     * @param array $keepItems
     */
    public static function collectGarbage($keepItems = [])
    {
        $longRunningHelper = self::getContainer()->get(\Pimcore\Helper\LongRunningHelper::class);
        $longRunningHelper->cleanUp([
            'pimcoreRuntimeCache' => [
                'keepItems' => $keepItems,
            ],
        ]);
    }

    /**
     * this method is called with register_shutdown_function() and writes all data queued into the cache
     *
     * @static
     */
    public static function shutdown()
    {
        // set inShutdown to true so that the output-buffer knows that he is allowed to send the headers
        self::$inShutdown = true;

        if (self::getContainer() === null) {
            return;
        }

        if (self::$shutdownEnabled && self::isInstalled()) {
            // write and clean up cache
            Cache::shutdown();

            // release all open locks from this process
            Model\Tool\Lock::releaseAll();
        }
    }

    /**
     * @internal
     */
    public static function disableShutdown()
    {
        self::$shutdownEnabled = false;
    }

    /**
     * @internal
     */
    public static function enableShutdown()
    {
        self::$shutdownEnabled = true;
    }

    public static function disableMinifyJs(): bool
    {
        if (self::inDevMode()) {
            return true;
        }

        // magic parameter for debugging ExtJS stuff
        if (array_key_exists('unminified_js', $_REQUEST) && self::inDebugMode()) {
            return true;
        }

        return false;
    }

    public static function initLogger()
    {
        // special request log -> if parameter pimcore_log is set
        if (array_key_exists('pimcore_log', $_REQUEST) && self::inDebugMode()) {
            $requestLogName = date('Y-m-d_H-i-s');
            if (!empty($_REQUEST['pimcore_log'])) {
                // slashed are not allowed, replace them with hyphens
                $requestLogName = str_replace('/', '-', $_REQUEST['pimcore_log']);
            }

            $requestLogFile = resolvePath(PIMCORE_LOG_DIRECTORY . '/request-' . $requestLogName . '.log');
            if (strpos($requestLogFile, PIMCORE_LOG_DIRECTORY) !== 0) {
                throw new \Exception('Not allowed');
            }

            if (!file_exists($requestLogFile)) {
                File::put($requestLogFile, '');
            }

            $requestDebugHandler = new \Monolog\Handler\StreamHandler($requestLogFile);

            foreach (self::getContainer()->getServiceIds() as $id) {
                if (strpos($id, 'monolog.logger.') === 0) {
                    $logger = self::getContainer()->get($id);
                    if ($logger->getName() != 'event') {
                        // replace all handlers
                        $logger->setHandlers([$requestDebugHandler]);
                    }
                }
            }
        }
    }
}
