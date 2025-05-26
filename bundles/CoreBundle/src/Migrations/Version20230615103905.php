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

final class Version20230615103905 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add definitionModificationDate to classes table for optimizing rebuild command performance.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('classes')->hasColumn('definitionModificationDate')) {
            $this->addSql(
                'ALTER TABLE `classes` ADD COLUMN `definitionModificationDate` INT(11) UNSIGNED NULL DEFAULT NULL;'
            );
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('classes')->hasColumn('definitionModificationDate')) {
            $this->addSql(
                'ALTER TABLE `classes` DROP COLUMN `definitionModificationDate`;'
            );
        }
    }
}
