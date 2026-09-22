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

namespace Pimcore;

use Exception;

/**
 * @internal
 */
class Video
{
    private static ?Video\AdapterInterface $defaultAdapterInstance = null;

    /**
     * Returns the cached default adapter, or a new named adapter instance.
     * Use for read-only operations (e.g. getDuration, getDimensions).
     *
     * @throws Exception
     */
    public static function getInstance(?string $adapter = null): ?Video\AdapterInterface
    {
        try {
            if ($adapter) {
                return self::resolveAdapterInstance($adapter);
            }

            return self::getDefaultAdapter();
        } catch (Exception $e) {
            Logger::crit('Unable to load video adapter: ' . $e->getMessage());

            throw $e;
        }
    }

    /**
     * Always returns a fresh adapter instance.
     * Use for stateful operations (e.g. thumbnail processing).
     *
     * @throws Exception
     */
    public static function newInstance(?string $adapter = null): ?Video\AdapterInterface
    {
        if ($adapter) {
            return self::resolveAdapterInstance($adapter);
        }

        $default = self::getDefaultAdapter();

        return $default !== null ? new (get_class($default))() : null;
    }

    public static function isAvailable(): bool
    {
        return self::getDefaultAdapter() !== null;
    }

    /**
     * Resolves and instantiates a named adapter class.
     *
     * @throws Exception
     */
    private static function resolveAdapterInstance(string $adapter): Video\AdapterInterface
    {
        $adapterClass = '\\Pimcore\\Video\\Adapter\\' . $adapter;
        if (!Tool::classExists($adapterClass)) {
            throw new Exception('Video-transcode adapter `' . $adapter . '´ does not exist.');
        }

        if (!is_subclass_of($adapterClass, Video\AdapterInterface::class)) {
            throw new Exception('Video-transcode adapter `' . $adapter . '´ must implement ' . Video\AdapterInterface::class . '.');
        }

        return new $adapterClass();
    }

    private static function getDefaultAdapter(): ?Video\AdapterInterface
    {
        if (self::$defaultAdapterInstance !== null) {
            return self::$defaultAdapterInstance;
        }

        foreach (['Ffmpeg'] as $adapter) {
            $adapterClass = '\\Pimcore\\Video\\Adapter\\' . $adapter;
            if (Tool::classExists($adapterClass)) {
                try {
                    $adapter = new $adapterClass();
                    if ($adapter->isAvailable()) {
                        return self::$defaultAdapterInstance = $adapter;
                    }
                } catch (Exception $e) {
                    Logger::warning((string) $e);
                }
            }
        }

        return null;
    }
}
