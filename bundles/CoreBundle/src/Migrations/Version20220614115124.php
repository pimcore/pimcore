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

final class Version20220614115124 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add filesize, width and height to assets_image_thumbnail_cache';
    }

    public function up(Schema $schema): void
    {
        // Delete old Version Name
        $this->addSql('DELETE FROM `migration_versions` WHERE `migration_versions`.`version` = \'Pimcore\\\\Bundle\\\\CoreBundle\\\\Migrations\\\\Version22020614115124\'');

        if (!$schema->getTable('assets_image_thumbnail_cache')->hasColumn('filesize')) {
            $this->addSql(
                'ALTER TABLE `assets_image_thumbnail_cache`
                ADD COLUMN `filesize` INT UNSIGNED
            ;'
            );
        }
        if (!$schema->getTable('assets_image_thumbnail_cache')->hasColumn('width')) {
            $this->addSql(
                'ALTER TABLE `assets_image_thumbnail_cache`
                ADD COLUMN `width` SMALLINT UNSIGNED
            ;'
            );
        }
        if (!$schema->getTable('assets_image_thumbnail_cache')->hasColumn('height')) {
            $this->addSql(
                'ALTER TABLE `assets_image_thumbnail_cache`
                ADD COLUMN `height` SMALLINT UNSIGNED
            ;'
            );
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->getTable('assets_image_thumbnail_cache')->hasColumn('filesize')) {
            $this->addSql(
                'ALTER TABLE `assets_image_thumbnail_cache`
                DROP COLUMN `filesize`
            ;'
            );
        }
        if (!$schema->getTable('assets_image_thumbnail_cache')->hasColumn('width')) {
            $this->addSql(
                'ALTER TABLE `assets_image_thumbnail_cache`
                DROP COLUMN `width`
            ;'
            );
        }
        if (!$schema->getTable('assets_image_thumbnail_cache')->hasColumn('height')) {
            $this->addSql(
                'ALTER TABLE `assets_image_thumbnail_cache`
                DROP COLUMN `height`
            ;'
            );
        }
    }
}
