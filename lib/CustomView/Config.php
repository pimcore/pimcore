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

namespace Pimcore\CustomView;

use Pimcore\Config\LocationAwareConfigRepository;

/**
 * @internal
 */
final class Config
{
    /**
     * @deprecated Will be removed in Pimcore 11
     */
    private const WRITE_TARGET = 'PIMCORE_WRITE_TARGET_CUSTOM_VIEWS';

    private const CONFIG_ID = 'custom_views';

    private static ?LocationAwareConfigRepository $locationAwareConfigRepository = null;

    protected ?string $writeTarget = null;

    protected ?array $options = null;

    private static function getRepository(): LocationAwareConfigRepository
    {
        if (!self::$locationAwareConfigRepository) {
            $containerConfig = \Pimcore::getContainer()->getParameter('pimcore.config');
            $config = $containerConfig[self::CONFIG_ID]['definitions'];

            $storageDirectory = LocationAwareConfigRepository::getStorageDirectoryFromSymfonyConfig($containerConfig, self::CONFIG_ID, 'PIMCORE_CONFIG_STORAGE_DIR_WEB_TO_PRINT');
            $writeTarget = LocationAwareConfigRepository::getWriteTargetFromSymfonyConfig($containerConfig, self::CONFIG_ID, 'PIMCORE_WRITE_TARGET_WEB_TO_PRINT');

            self::$locationAwareConfigRepository = new LocationAwareConfigRepository(
                $config,
                'pimcore_custom_views',
                $storageDirectory,
                self::WRITE_TARGET
            );

            self::$locationAwareConfigRepository->setWriteTarget($writeTarget);
            self::$locationAwareConfigRepository->setOptions($containerConfig['storage'][self::CONFIG_ID]['options']);
        }

        return self::$locationAwareConfigRepository;
    }

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
    public static function get(): array
    {
        $config = [];
        $repository = self::getRepository();
        $keys = $repository->fetchAllKeys();
        foreach ($keys as $key) {
            list($data, $dataSource) = $repository->loadConfigByKey(($key));
            $data['writeable'] = $repository->isWriteable($key, $dataSource);
            $data['id'] = $data['id'] ?? $key;
            if (!is_array($data['classes'] ?? [])) {
                $data['classes'] = self::flipArray($data);
            }

            $config[$data['id']] = $data;
        }

        return $config;
    }

    /**
     * @param array $data
     * @param array|null $deletedRecords
     *
     * @throws \Exception
     */
    public static function save(array $data, ?array $deletedRecords): void
    {
        $repository = self::getRepository();

        foreach ($data as $key => $value) {
            $key = (string) $key;
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

    public static function isWriteable(): bool
    {
        return self::getRepository()->isWriteable();
    }
}
