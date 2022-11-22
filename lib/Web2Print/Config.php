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

namespace Pimcore\Web2Print;

use Pimcore\Config\LocationAwareConfigRepository;
use Pimcore\Model\Exception\ConfigWriteException;

/**
 * @internal
 */
final class Config
{
    private const CONFIG_ID = 'web_to_print';

    private static ?LocationAwareConfigRepository $locationAwareConfigRepository = null;

    private static function getRepository(): LocationAwareConfigRepository
    {
        if (!self::$locationAwareConfigRepository) {
            $config = [];
            $containerConfig = \Pimcore::getContainer()->getParameter('pimcore.config');
            if ($containerConfig['documents']['web_to_print']['generalTool']) {
                $config = [
                    self::CONFIG_ID => $containerConfig['documents']['web_to_print'],
                ];
            }

            self::$locationAwareConfigRepository = new LocationAwareConfigRepository(
                $config,
                'pimcore_web_to_print',
                $_SERVER['PIMCORE_CONFIG_STORAGE_DIR_WEB_TO_PRINT'] ?? PIMCORE_CONFIGURATION_DIRECTORY . '/web-to-print',
                'PIMCORE_WRITE_TARGET_WEB_TO_PRINT'
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

    public static function get(): array
    {
        $repository = self::getRepository();

        $config = $repository->loadConfigByKey(self::CONFIG_ID);

        return $config[0] ?? [];
    }

    /**
     * @param array $data
     *
     * @throws \Exception
     */
    public static function save(array $data): void
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
