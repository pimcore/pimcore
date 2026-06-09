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

namespace Pimcore\Bundle\SeoBundle;

use Exception;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\Tool\SettingsStore;

final class Config
{
    /**
     * @return array<string, string>
     *
     * @internal
     */
    public static function getRobotsConfig(): array
    {
        $config = [];
        if (RuntimeCache::isRegistered('pimcore_bundle_seo_config_robots')) {
            $config = RuntimeCache::get('pimcore_bundle_seo_config_robots');
        } else {
            try {
                $settingsStoreScope = 'robots.txt';
                $robotsSettingsIds = SettingsStore::getIdsByScope($settingsStoreScope);
                foreach ($robotsSettingsIds as $id) {
                    $robots = SettingsStore::get($id, $settingsStoreScope);
                    $siteId = preg_replace('/^robots\.txt\-/', '', $robots->getId());
                    $config[$siteId] = $robots->getData();
                }
            } catch (Exception $e) {
            }

            self::setRobotsConfig($config);
        }

        return $config;
    }

    /**
     * @param array<string, string> $config
     *
     * @internal
     */
    public static function setRobotsConfig(array $config): void
    {
        RuntimeCache::set('pimcore_bundle_seo_config_robots', $config);
    }
}
