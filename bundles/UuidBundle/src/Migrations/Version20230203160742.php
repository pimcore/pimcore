<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\UuidBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230203160742 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Modify `itemId` column type in `uuids` db table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `uuids` MODIFY COLUMN `itemId` VARCHAR(50) NOT NULL;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `uuids` MODIFY COLUMN `itemId` int(11) unsigned NOT NULL;');
    }
}
