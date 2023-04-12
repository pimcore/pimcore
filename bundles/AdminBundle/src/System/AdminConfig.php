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

namespace Pimcore\Bundle\AdminBundle\System;

use Pimcore\Bundle\AdminBundle\Helper\SystemConfig;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Config\LocationAwareConfigRepository;

/**
 * @internal
 */
final class AdminConfig
{
    private const CONFIG_ID = 'admin_system_settings';

    private const DATA_KEY = 'branding';

    private const SCOPE = 'pimcore_admin_system_settings';

    private static ?LocationAwareConfigRepository $locationAwareConfigRepository = null;

    private static ?SystemConfig $systemConfigService = null;

    public static function getRepository(): LocationAwareConfigRepository
    {
        if (!self::$locationAwareConfigRepository) {
            $containerConfig = \Pimcore::getContainer()->getParameter('pimcore_admin.config');
            $config[self::CONFIG_ID][self::DATA_KEY] = $containerConfig[self::DATA_KEY];
            $storageConfig = $containerConfig['config_location'][self::CONFIG_ID];

            self::$locationAwareConfigRepository = new LocationAwareConfigRepository(
                $config,
                self::SCOPE,
                $storageConfig
            );
        }

        return self::$locationAwareConfigRepository;
    }

    public static function get(): array
    {
        $repository = self::getRepository();
        $service = self::getSystemConfigService();

        return $service::get($repository, self::CONFIG_ID);
    }

    public function save(array $values): void
    {
        $repository = self::getRepository();

        $data[self::DATA_KEY] = [
            'login_screen_invert_colors' => $values['branding.login_screen_invert_colors'],
            'color_login_screen' => $values['branding.color_login_screen'],
            'color_admin_interface' => $values['branding.color_admin_interface'],
            'color_admin_interface_background' => $values['branding.color_admin_interface_background'],
            'login_screen_custom_image' => str_replace('%', '%%', $values['branding.login_screen_custom_image']),
        ];

        $repository->saveConfig(self::CONFIG_ID, $data, function ($key, $data) {
            return [
                'pimcore_admin' => $data,
            ];
        });
    }

    /**
     *
     * @internal
     */
    public function getAdminSystemSettingsConfig(): array
    {
        if (RuntimeCache::isRegistered('pimcore_admin_system_settings_config')) {
            $config = RuntimeCache::get('pimcore_admin_system_settings_config');
        } else {
            $config = $this->get();
            $this->setAdminSystemSettingsConfig($config);
        }

        return $config;
    }

    /**
     *
     * @internal
     */
    public function setAdminSystemSettingsConfig(array $config): void
    {
        RuntimeCache::set('pimcore_admin_system_settings_config', $config);
    }

    private static function getSystemConfigService(): SystemConfig
    {
        if (!self::$systemConfigService) {
            self::$systemConfigService = new SystemConfig();
        }

        return self::$systemConfigService;
    }
}
