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
use Pimcore;

final class Version20230320110429 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'rename default dir for symfony-config files';
    }

    public function up(Schema $schema): void
    {
        $this->renameConfigFolder(['image-thumbnails', 'video-thumbnails'], '-', '_');
    }

    public function down(Schema $schema): void
    {
        $this->renameConfigFolder(['image_thumbnails', 'video_thumbnails'], '_', '-');
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
}
