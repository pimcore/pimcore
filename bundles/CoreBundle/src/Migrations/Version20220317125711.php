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

final class Version20220317125711 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('assets_image_thumbnail_cache')) {
            $this->addSql('CREATE TABLE `assets_image_thumbnail_cache` (
                `cid` int(11) unsigned NOT NULL,
                `name` varchar(190) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
                `filename` varchar(190) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                `modificationDate` INT(11) UNSIGNED DEFAULT NULL,
                PRIMARY KEY (`cid`, `name`, `filename`),
                CONSTRAINT `FK_assets_image_thumbnail_cache_assets` FOREIGN KEY (`cid`) REFERENCES `assets` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
            ) DEFAULT CHARSET=utf8mb4;');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('assets_image_thumbnail_cache')) {
            $this->addSql('DROP TABLE IF EXISTS `assets_image_thumbnail_cache`;');
        }
    }
}
