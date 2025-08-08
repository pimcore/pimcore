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

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore;
use Symfony\Component\Filesystem\Filesystem;

final class Version20230525131748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move config folders with content to new folders';
    }

    public function up(Schema $schema): void
    {
        $this->renameConfigFolder(['image-thumbnails', 'video-thumbnails'], '-', '_');

        $folders = [
            'custom-reports' => 'custom_reports',
            'document-types' => 'document_types',
            'web-to-print' => 'web_to_print',
            'predefined-properties' => 'predefined_properties',
            'predefined-asset-metadata' => 'predefined_asset_metadata',
            'custom-views' => 'custom_views',
            'object-custom-layouts' => 'object_custom_layouts',
        ];

        $this->moveConfigFromFolders($folders);
    }

    public function down(Schema $schema): void
    {
        $this->renameConfigFolder(['image_thumbnails', 'video_thumbnails'], '_', '-');

        $folders = [
            'custom_reports' => 'custom-reports',
            'document_types' => 'document-types',
            'web_to_print' => 'web-to-print',
            'predefined_properties' => 'predefined-properties',
            'predefined_asset_metadata' => 'predefined-asset-metadata',
            'custom_views' => 'custom-views',
            'object_custom_layouts' => 'object-custom-layouts',
        ];

        $this->moveConfigFromFolders($folders);
    }

    private function renameConfigFolder(array $folders, string $search, string $replace): void
    {
        $configDir = Pimcore::getContainer()->getParameter('kernel.project_dir') . '/var/config/';
        foreach ($folders as $folder) {
            if (is_dir($configDir . $folder)) {
                rename($configDir . $folder, $configDir . str_replace($search, $replace, $folder));
            }
        }
    }

    private function moveConfigFromFolders(array $folders): void
    {
        $configDir = Pimcore::getContainer()->getParameter('kernel.project_dir') . '/var/config/';
        $filesystem = new Filesystem();
        foreach ($folders as $srcFolder => $targetFolder) {
            $configFolder = $configDir . $srcFolder;
            if (is_dir($configFolder)) {
                $newConfigFolder = $configDir . $targetFolder;
                if (!is_dir($newConfigFolder)) {
                    $filesystem->mkdir($newConfigFolder);
                }

                $files = array_diff(scandir($configFolder), ['.', '..']);
                foreach ($files as $file) {
                    rename($configFolder . '/' . $file, $newConfigFolder . '/' . $file);
                }

                rmdir($configFolder);
            }
        }
    }
}
