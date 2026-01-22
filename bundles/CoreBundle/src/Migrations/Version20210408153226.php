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

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20210408153226 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $versionsTable = $schema->getTable('versions');

        if (!$versionsTable->hasColumn('autoSave')) {
            $this->addSql('ALTER TABLE `versions` ADD `autoSave` TINYINT(4) NOT NULL DEFAULT 0');
        }

        if (!$versionsTable->hasIndex('autoSave')) {
            $this->addSql('ALTER TABLE `versions` ADD INDEX `autoSave` (`autoSave`)');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `versions` DROP COLUMN `autoSave`');
    }
}
