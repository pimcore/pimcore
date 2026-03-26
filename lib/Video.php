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
     *
     *
     * @throws Exception
     */
    public static function getInstance(?string $adapter = null): ?Video\AdapterInterface
    {
        try {
            if ($adapter) {
                $adapterClass = '\\Pimcore\\Video\\Adapter\\' . $adapter;
                if (Tool::classExists($adapterClass)) {
                    return new $adapterClass();
                } else {
                    throw new Exception('Video-transcode adapter `' . $adapter . '´ does not exist.');
                }
            } else {
                return self::getDefaultAdapter();
            }
        } catch (Exception $e) {
            Logger::crit('Unable to load video adapter: ' . $e->getMessage());

            throw $e;
        }
    }

    public static function isAvailable(): bool
    {
        return self::getDefaultAdapter() !== null;
    }

    private static function getDefaultAdapter(): ?Video\AdapterInterface
    {
        if (self::$defaultAdapterInstance !== null) {
            return self::$defaultAdapterInstance;
        }

        $adapters = ['Ffmpeg'];

        foreach ($adapters as $adapter) {
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
