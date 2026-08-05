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

final class Version20220214110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add `storageType` column to `version` database table';
    }

    public function up(Schema $schema): void
    {
        if ($schema->getTable('versions')->hasColumn('storageType') === false) {
            $this->addSql('ALTER TABLE `versions` ADD COLUMN `storageType` varchar(5) NOT NULL;');
            $this->addSql("update `versions` set storageType = 'fs'");
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('versions')->hasColumn('storageType')) {
            $this->addSql('ALTER TABLE `versions` DROP COLUMN `storageType`;');
        }
    }
}
