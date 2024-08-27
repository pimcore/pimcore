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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class Pimcore
{
    private static bool $adminMode = false;

    private static bool $shutdownEnabled = true;

    private static ?KernelInterface $kernel = null;

    public static function inDebugMode(): bool
    {
        return self::getKernel()->isDebug();
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
     */
    public static function getEventDispatcher(): EventDispatcherInterface
    {
        return self::getContainer()->get('event_dispatcher');
    }

    /**
     * @internal
     */
    public static function getKernel(): ?KernelInterface
    {
        return self::$kernel;
    }

    /**
     * @internal
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
     */
    public static function getContainer(): ?ContainerInterface
    {
        return static::getKernel()->getContainer();
    }

    /**
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
     * Forces a garbage collection.
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
}
