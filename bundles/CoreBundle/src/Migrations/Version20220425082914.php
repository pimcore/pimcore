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

final class Version20220425082914 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Improve data object grid loading performance';
    }

    public function up(Schema $schema): void
    {
        if ($schema->getTable('objects')->hasIndex('type')) {
            $this->addSql('ALTER TABLE `objects` DROP INDEX `type`');
        }

        if (!$schema->getTable('objects')->hasIndex('type_path_classId')) {
            $this->addSql('ALTER TABLE `objects` ADD INDEX `type_path_classId` (o_type, o_path, o_classId)');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->getTable('objects')->hasIndex('type')) {
            $this->addSql('ALTER TABLE `objects` ADD INDEX `type` (o_type)');
        }

        if ($schema->getTable('objects')->hasIndex('type_path_classId')) {
            $this->addSql('ALTER TABLE `objects` DROP INDEX `type_path_classId`');
        }
    }
}
