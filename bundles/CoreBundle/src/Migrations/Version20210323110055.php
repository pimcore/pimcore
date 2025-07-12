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
use Pimcore\Config;
use Pimcore\Model\Tool\SettingsStore;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210323110055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $file = Config::locateConfigFile('robots.php');

        if (!file_exists($file)) {
            return;
        }

        $config = Config::getConfigInstance($file);

        foreach ($config as $siteId => $robotsContent) {
            SettingsStore::set('robots.txt-' . $siteId, $robotsContent, SettingsStore::TYPE_STRING, 'robots.txt');
        }
    }

    public function down(Schema $schema): void
    {
        $robotsSettingsIds = SettingsStore::getIdsByScope('robots.txt');
        foreach ($robotsSettingsIds as $id) {
            SettingsStore::delete($id, 'robots.txt');
        }
    }
}
