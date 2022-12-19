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

use Pimcore\Cache;
use Pimcore\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class Pimcore
{
    private static bool $adminMode = false;

    private static bool $shutdownEnabled = true;

    private static ?KernelInterface $kernel = null;

    private static \Composer\Autoload\ClassLoader $autoloader;

    public static function inDebugMode(): bool
    {
        return (bool) self::getKernel()->isDebug();
    }

    public static function inDevMode(): bool
    {
        if (!isset($_SERVER['PIMCORE_DEV_MODE']) || !is_bool($_SERVER['PIMCORE_DEV_MODE'])) {
            $value = $_SERVER['PIMCORE_DEV_MODE'] ?? false;
            if (!is_bool($value)) {
                $value = filter_var($value, \FILTER_VALIDATE_BOOLEAN);
            }
            $_SERVER['PIMCORE_DEV_MODE'] = (bool) $value;
        }

        return $_SERVER['PIMCORE_DEV_MODE'];
    }

    /**
     * switches pimcore into the admin mode - there you can access also unpublished elements, ....
     *
     * @internal
     */
    public static function setAdminMode(): void
    {
        self::$adminMode = true;
    }

    /**
     * switches back to the non admin mode, where unpublished elements are invisible
     *
     * @internal
     */
    public static function unsetAdminMode(): void
    {
        self::$adminMode = false;
    }

    /**
     * check if the process is currently in admin mode or not
     *
     * @return bool
     */
    public static function inAdmin(): bool
    {
        return self::$adminMode;
    }

    public static function isInstalled(): bool
    {
        try {
            \Pimcore\Db::get()->fetchOne('SELECT id FROM assets LIMIT 1');

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @internal
     *
     * @return EventDispatcherInterface
     */
    public static function getEventDispatcher(): EventDispatcherInterface
    {
        return self::getContainer()->get('event_dispatcher');
    }

    /**
     * @internal
     *
     * @return KernelInterface|null
     */
    public static function getKernel(): ?KernelInterface
    {
        return self::$kernel;
    }

    /**
     * @internal
     *
     * @return bool
     */
    public static function hasKernel(): bool
    {
        if (self::$kernel) {
            return true;
        }

        return false;
    }

    /**
     * @internal
     *
     * @param KernelInterface $kernel
     */
    public static function setKernel(KernelInterface $kernel): void
    {
        self::$kernel = $kernel;
    }

    /**
     * Accessing the container this way is discouraged as dependencies should be wired through the container instead of
     * needing to access the container directly. This exists mainly for compatibility with legacy code.
     *
     * @internal
     *
     * @deprecated this method just exists for legacy reasons and shouldn't be used in new code
     *
     * @return ContainerInterface|null
     */
    public static function getContainer(): ?ContainerInterface
    {
        return static::getKernel()->getContainer();
    }

    /**
     * @return bool
     *
     * @internal
     */
    public static function hasContainer(): bool
    {
        if (static::hasKernel()) {
            try {
                $container = static::getContainer();
                if ($container) {
                    return true;
                }
            } catch (\LogicException) {
            }
        }

        return false;
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     *
     * @internal
     */
    public static function getAutoloader(): \Composer\Autoload\ClassLoader
    {
        return self::$autoloader;
    }

    /**
     * @param \Composer\Autoload\ClassLoader $autoloader
     *
     * @internal
     */
    public static function setAutoloader(\Composer\Autoload\ClassLoader $autoloader): void
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
    public static function collectGarbage(array $keepItems = []): void
    {
        $longRunningHelper = self::getContainer()->get(\Pimcore\Helper\LongRunningHelper::class);
        $longRunningHelper->cleanUp([
            'pimcoreRuntimeCache' => [
                'keepItems' => $keepItems,
            ],
        ]);
    }

    /**
     * Deletes temporary files which got created during the runtime of current process
     *
     * @static
     */
    public static function deleteTemporaryFiles(): void
    {
        /** @var \Pimcore\Helper\LongRunningHelper $longRunningHelper */
        $longRunningHelper = self::getContainer()->get(\Pimcore\Helper\LongRunningHelper::class);
        $longRunningHelper->deleteTemporaryFiles();
    }

    /**
     * this method is called with register_shutdown_function() and writes all data queued into the cache
     *
     * @internal
     */
    public static function shutdown(): void
    {
        try {
            self::getContainer();
        } catch (\LogicException $e) {
            return;
        }

        if (self::$shutdownEnabled && self::isInstalled()) {
            // write and clean up cache
            Cache::shutdown();
        }
    }

    /**
     * @internal
     */
    public static function disableShutdown(): void
    {
        self::$shutdownEnabled = false;
    }

    /**
     * @internal
     */
    public static function enableShutdown(): void
    {
        self::$shutdownEnabled = true;
    }

    /**
     * @internal
     *
     * @return bool
     */
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

    /**
     * @internal
     *
     * @throws Exception
     */
    public static function initLogger(): void
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

            /** @var \Symfony\Component\DependencyInjection\Container $container */
            $container = self::getContainer();
            foreach ($container->getServiceIds() as $id) {
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
