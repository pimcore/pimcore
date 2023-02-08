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

namespace Pimcore\Web2Print;

use Pimcore\Config\LocationAwareConfigRepository;
use Pimcore\Model\Exception\ConfigWriteException;

/**
 * @internal
 */
final class Config
{
    /**
     * @deprecated Will be removed in Pimcore 11
     */
    private const STORAGE_DIR = 'PIMCORE_CONFIG_STORAGE_DIR_WEB_TO_PRINT';

    /**
     * @deprecated Will be removed in Pimcore 11
     */
    private const WRITE_TARGET = 'PIMCORE_WRITE_TARGET_WEB_TO_PRINT';

    private const CONFIG_ID = 'web_to_print';

    /**
     * @var LocationAwareConfigRepository|null
     */
    private static ?LocationAwareConfigRepository $locationAwareConfigRepository = null;

    /**
     * @deprecated Will be removed in Pimcore 11
     */
    private const LEGACY_FILE = 'web2print.php';

    /**
     * @return LocationAwareConfigRepository
     */
    private static function getRepository()
    {
        if (!self::$locationAwareConfigRepository) {
            $config = [];
            $containerConfig = \Pimcore::getContainer()->getParameter('pimcore.config');
            if ($containerConfig['documents']['web_to_print']['generalTool']) {
                $config = [
                    self::CONFIG_ID => $containerConfig['documents']['web_to_print'],
                ];
            }

            // @deprecated legacy will be removed in Pimcore 11
            $loadLegacyConfigCallback = function ($legacyRepo, &$dataSource) {
                $file = \Pimcore\Config::locateConfigFile(self::LEGACY_FILE);
                if (is_file($file)) {
                    $content = include($file);
                    if (is_array($content)) {
                        $dataSource = LocationAwareConfigRepository::LOCATION_LEGACY;

                        return $content;
                    }
                }

                return null;
            };

            $storageDirectory = null;
            if(array_key_exists('directory', $containerConfig['storage'][self::CONFIG_ID])) {
                $storageDirectory = $containerConfig['storage'][self::CONFIG_ID]['directory'];
            } elseif (array_key_exists(self::STORAGE_DIR, $_SERVER)) {
                $storageDirectory = $_SERVER[self::STORAGE_DIR];
                trigger_deprecation('pimcore/pimcore', '10.6',
                    sprintf('Setting storage directory (%s) in the .env file is deprecated, instead use the symfony config. It will be removed in Pimcore 11.',  self::STORAGE_DIR));
            } else {
                $storageDirectory = PIMCORE_CONFIGURATION_DIRECTORY . '/' . self::CONFIG_ID;
            }

            $writeTarget = null;
            if(array_key_exists('target', $containerConfig['storage'][self::CONFIG_ID])) {
                $writeTarget = $containerConfig['storage'][self::CONFIG_ID]['target'];
            } elseif (array_key_exists(self::WRITE_TARGET, $_SERVER)) {
                $writeTarget = $_SERVER[self::WRITE_TARGET];
                trigger_deprecation('pimcore/pimcore', '10.6',
                    sprintf('Setting write targets (%s) in the .env file is deprecated, instead use the symfony config. It will be removed in Pimcore 11.',  self::WRITE_TARGET));
            }

            self::$locationAwareConfigRepository = new LocationAwareConfigRepository(
                $config,
                'pimcore_web_to_print',
                $storageDirectory,
                $writeTarget,
                null,
                self::LEGACY_FILE,
                $loadLegacyConfigCallback,
                $writeTarget
            );
        }

        return self::$locationAwareConfigRepository;
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public static function isWriteable(): bool
    {
        return self::getRepository()->isWriteable();
    }

    /**
     * @return \Pimcore\Config\Config
     */
    public static function get(): \Pimcore\Config\Config
    {
        $repository = self::getRepository();

        list($config) = $repository->loadConfigByKey(self::CONFIG_ID);

        return new \Pimcore\Config\Config($config ?? []);
    }

    /**
     * @param array $data
     *
     * @throws \Exception
     */
    public static function save(array $data)
    {
        $repository = self::getRepository();

        unset($data['pdf_creation_php_memory_limit']);
        unset($data['default_controller_print_page']);
        unset($data['default_controller_print_container']);

        if (!$repository->isWriteable()) {
            throw new ConfigWriteException();
        }

        $repository->saveConfig(self::CONFIG_ID, $data, function ($key, $data) {
            return [
                'pimcore' => [
                    'documents' => [
                        'web_to_print' => $data,
                    ],
                ],
            ];
        });
    }
}
