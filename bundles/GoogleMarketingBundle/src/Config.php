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

namespace Pimcore\Bundle\GoogleMarketingBundle;

use Exception;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Config\ReportConfigWriter;
use Pimcore\Model\Tool\SettingsStore;

final class Config
{
    /**
     * @return array<string, mixed>
     *
     * @throws Exception
     *
     * @internal
     */
    public static function getReportConfig(): array
    {
        $config = [];
        if (RuntimeCache::isRegistered('pimcore_config_report')) {
            $config = RuntimeCache::get('pimcore_config_report');
        } else {
            try {
                $configJson = SettingsStore::get(
                    ReportConfigWriter::REPORT_SETTING_ID, ReportConfigWriter::REPORT_SETTING_SCOPE
                );

                if ($configJson) {
                    $config = json_decode($configJson->getData(), true);
                }
            } catch (Exception $e) {
                // nothing to do
            }
        }

        self::setReportConfig($config);

        return $config;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @internal
     */
    public static function setReportConfig(array $config): void
    {
        RuntimeCache::set('pimcore_config_report', $config);
    }
}
