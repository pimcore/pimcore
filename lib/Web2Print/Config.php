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
use Pimcore\Db\PhpArrayFileTable;
use Pimcore\File;
use Pimcore\Logger;

/**
 * @internal
 */
final class Config {

    const CONFIG_ID = 'pimcore_web2print';

    /** @var LocationAwareConfigRepository|null  */
    private static $locationAwareConfigRepository = null;

    /**
     * @deprecated Will be removed in Pimcore 11
     */
    private const LEGACY_FILE = 'web2print.php';

    /**
     * @return LocationAwareConfigRepository|null
     */
    private static function getRepository()
    {
        if (!self::$locationAwareConfigRepository) {
            $config = \Pimcore::getContainer()->getParameter('pimcore.config');
            $config = [
                self::CONFIG_ID => $config['web2print']
            ];

            /* @deprecated legacy will be removed in Pimcore 11 */
            $loadLegacyConfigCallback = function($legacyRepo) {
                $file = \Pimcore\Config::locateConfigFile(self::LEGACY_FILE);
                if (is_file($file)) {
                    $content = include($file);
                    if (is_array($content)) {
                        return $content;
                    }
                }
                return null;
            };

            self::$locationAwareConfigRepository = new LocationAwareConfigRepository(
                $config,
                'pimcore_web2print',
                PIMCORE_CONFIGURATION_DIRECTORY . '/web2print' ?? null,
                'PIMCORE_WRITE_TARGET_WEB2PRINT',
                null,
                self::LEGACY_FILE,
                $loadLegacyConfigCallback
            );
        }
        return self::$locationAwareConfigRepository;
    }

    /**
     * @return bool
     */
    public static function isWriteable(): bool {
        return self::getRepository()->isWriteable();
    }

    /**
     * @return \Pimcore\Config\Config
     */
    public static function get(): \Pimcore\Config\Config {
        $repository = self::getRepository();

        list($config, $dataSource) = $repository->loadConfigByKey(self::CONFIG_ID);
        $config = new \Pimcore\Config\Config($config);
        return $config;
    }

    /**
     * @param array $data
     * @throws \Exception
     */
    public static function save(array $data) {
        $repository = self::getRepository();
        $repository->saveConfig(self::CONFIG_ID, $data, function ($key, $data) {
            return [
                "pimcore" => [
                    "web2print" => $data
                ]
            ];
        });
    }
}
