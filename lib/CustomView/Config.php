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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\CustomView;

use Pimcore\Config\LocationAwareConfigRepository;

/**
 * @internal
 */
final class Config
{
    private const CONFIG_ID = 'custom_views';

    /**
     * @deprecated Will be removed in Pimcore 11
     */
    private const LEGACY_FILE = 'customviews.php';

    private static ?LocationAwareConfigRepository $locationAwareConfigRepository = null;

    private static function getRepository()
    {
        if (!self::$locationAwareConfigRepository) {
            $containerConfig = \Pimcore::getContainer()->getParameter('pimcore.config');
            $config = $containerConfig[self::CONFIG_ID]['definitions'];

            // @deprecated legacy will be removed in Pimcore 11
            $loadLegacyConfigCallback = function ($legacyRepo, &$dataSource) {
                $file = \Pimcore\Config::locateConfigFile(self::LEGACY_FILE);
                if (is_file($file)) {
                    $content = include($file);
                    if (is_array($content)) {
                        $dataSource = LocationAwareConfigRepository::LOCATION_LEGACY;

                        return $content['views'];
                    }
                }

                return null;
            };

            self::$locationAwareConfigRepository = new LocationAwareConfigRepository(
                $config,
                'pimcore_custom_views',
                $_SERVER['PIMCORE_CONFIG_STORAGE_DIR_CUSTOM_VIEWS'] ?? PIMCORE_CONFIGURATION_DIRECTORY . '/custom-views',
                'PIMCORE_WRITE_TARGET_CUSTOM_VIEWS',
                null,
                self::LEGACY_FILE,
                $loadLegacyConfigCallback
            );
        }

        return self::$locationAwareConfigRepository;
    }

    /**
     * @param mixed $data
     *
     * @return array
     */
    protected static function flipArray(mixed $data): array
    {
        if (empty($data['classes'])) {
            return [];
        } else {
            $tempClasses = explode(',', $data['classes']);

            return array_fill_keys($tempClasses, null);
        }
    }

    /**
     * @return array
     *
     * @internal
     *
     */
    public static function get()
    {
        $config = [];
        $repository = self::getRepository();
        $keys = $repository->fetchAllKeys();
        foreach ($keys as $key) {
            list($data, $dataSource) = $repository->loadConfigByKey(($key));
            if ($dataSource == LocationAwareConfigRepository::LOCATION_LEGACY) {
                foreach ($data as $configKey) {
                    $configId = $configKey['id'];
                    if (!isset($config[$configId])) {
                        $configKey['writeable'] = $repository->isWriteable($key, $dataSource);
                        if (!is_array($configKey['classes'] ?? [])) {
                            $configKey['classes'] = self::flipArray($configKey);
                        }

                        if (!empty($configKey['hidden'])) {
                            continue;
                        }

                        $config[$configId] = $configKey;
                    }
                }
            } else {
                $data['writeable'] = $repository->isWriteable($key, $dataSource);
                $data['id'] = $data['id'] ?? $key;
                if (!is_array($data['classes'] ?? [])) {
                    $data['classes'] = self::flipArray($data);
                }

                $config[$data['id']] = $data;
            }
        }
        //$config = new \Pimcore\Config\Config($config, true);

        return $config;
    }

    /**
     * @param array $data
     * @param array|null $deletedRecords
     *
     * @throws \Exception
     */
    public static function save(array $data, ?array $deletedRecords)
    {
        $repository = self::getRepository();

        foreach ($data as $key => $value) {
            list($configKey, $dataSource) = $repository->loadConfigByKey($key);
            if ($repository->isWriteable($key, $dataSource) === true) {
                unset($value['writeable']);
                $repository->saveConfig($key, $value, function ($key, $data) {
                    return [
                        'pimcore' => [
                            'custom_views' => [
                                'definitions' => [
                                    $key => $data,
                                ],
                            ],
                        ],
                    ];
                });
            }
        }

        if ($deletedRecords) {
            foreach ($deletedRecords as $key) {
                list($configKey, $dataSource) = $repository->loadConfigByKey(($key));
                if (!empty($configKey)) {
                    $repository->deleteData($key, $dataSource);
                }
            }
        }
    }

    /**
     * @return bool
     */
    public static function isWriteable(): bool
    {
        return self::getRepository()->isWriteable();
    }
}
