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

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\Tool\SettingsStore;

final class Version20220829132224 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add migration for LocationAwareConfigRepository.';
    }

    private function loadLegacyConfigs(string $fileName): array
    {
        $file = \Pimcore\Config::locateConfigFile($fileName);
        $configs = [];

        if (file_exists($file)) {
            $configs = @include $file;
        }

        return $configs;
    }

    private function migrateToSettingsStore(string $id, string $scope, array $configs, bool $overwriteExistingConfig = false): void
    {
        if (count($configs) > 0) {
            $existingConfig = SettingsStore::get($id, $scope);
            if (!$existingConfig || $overwriteExistingConfig) {
                SettingsStore::set($id, json_encode($configs), SettingsStore::TYPE_STRING, $scope);
            }
        }
    }

    private function migrateCommonConfigurations(string $fileName, string $scope): void
    {
        $configs = $this->loadLegacyConfigs($fileName);
        foreach ($configs as $key => $config) {
            $id = $config['id'] ?? $config['name'];
            $this->migrateToSettingsStore((string)$id, $scope, $config);
        }
    }

    private function migrateCustomViewsPerspectives(string $fileName, string $scope): void
    {
        $configs = $this->loadLegacyConfigs($fileName);
        if (count($configs) > 0) {
            $configs = $configs['views'] ?? $configs;
            foreach ($configs as $key => $config) {
                $id = (string)($config['id'] ?? $key);
                $this->migrateToSettingsStore($id, $scope, $config);
            }
        }
    }

    private function migrateWeb2PrintSettings(): void
    {
        $configs = $this->loadLegacyConfigs('web2print.php');
        if (count($configs) > 0 && class_exists('Pimcore\Bundle\WebToPrintBundle\Config')) {
            $web2PrintConfigs = \Pimcore\Bundle\WebToPrintBundle\Config::getWeb2PrintConfig();
            foreach ($configs as $key => $config) {
                if (!isset($web2PrintConfigs[$key])) {
                    $web2PrintConfigs[$key] = $config;
                }
            }
            $this->migrateToSettingsStore('web_to_print', 'pimcore_web_to_print', $web2PrintConfigs, true);
        }
    }

    public function up(Schema $schema): void
    {
        $this->migrateCommonConfigurations('predefined-properties.php', 'pimcore_predefined_properties');
        $this->migrateCommonConfigurations('predefined-asset-metadata.php', 'pimcore_predefined_asset_metadata');
        $this->migrateCommonConfigurations('image-thumbnails.php', 'pimcore_image_thumbnails');
        $this->migrateCommonConfigurations('video-thumbnails.php', 'pimcore_video_thumbnails');
        $this->migrateCommonConfigurations('staticroutes.php', 'pimcore_staticroutes');
        $this->migrateCommonConfigurations('custom-reports.php', 'pimcore_custom_reports');
        $this->migrateCommonConfigurations('document-types.php', 'pimcore_document_types');

        $this->migrateCustomViewsPerspectives('perspectives.php', 'pimcore_perspectives');
        $this->migrateCustomViewsPerspectives('customviews.php', 'pimcore_custom_views');

        $this->migrateWeb2PrintSettings();
    }

    public function down(Schema $schema): void
    {
    }
}
