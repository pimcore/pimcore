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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20210408153226 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $versionsTable = $schema->getTable('versions');

        if(!$versionsTable->hasColumn('autoSave')) {
            $this->addSql('ALTER TABLE `versions` ADD `autoSave` TINYINT(4) NOT NULL DEFAULT 0');
        }

        if (!$versionsTable->hasIndex('autoSave')) {
            $this->addSql('ALTER TABLE `versions` ADD INDEX `autoSave` (`autoSave`)');
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `versions` DROP COLUMN `autoSave`');
    }
}
