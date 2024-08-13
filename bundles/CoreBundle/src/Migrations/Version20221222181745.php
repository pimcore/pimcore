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

final class Version20221222181745 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add CONSTRAINT of classificationstore_groups in object_classificationstore_groups';
    }

    public function up(Schema $schema): void
    {
        $tableList = $this->connection->fetchAllAssociative("show tables like 'object_classificationstore_groups_%'");
        foreach ($tableList as $tableGroups) {
            $theTableGroups = current($tableGroups);
            $tableArray = explode('_', $theTableGroups);
            $tableNumber = end($tableArray);
            if (!is_numeric($tableNumber)) {
                continue;
            }

            $this->addSql("DELETE `object_classificationstore_groups_{$tableNumber}`
            FROM `object_classificationstore_groups_{$tableNumber}`
            LEFT JOIN `classificationstore_groups` ON object_classificationstore_groups_{$tableNumber}.groupId = classificationstore_groups.id
            WHERE classificationstore_groups.id IS NULL;");

            $this->addSql("DELETE `object_classificationstore_data_{$tableNumber}`
            FROM `object_classificationstore_data_{$tableNumber}`
            LEFT JOIN `$theTableGroups` ON object_classificationstore_data_{$tableNumber}.id = $theTableGroups.id AND
            object_classificationstore_data_{$tableNumber}.fieldname = $theTableGroups.fieldname AND
            object_classificationstore_data_{$tableNumber}.groupId = $theTableGroups.groupId
            WHERE $theTableGroups.id IS NULL AND  $theTableGroups.fieldname IS NULL AND $theTableGroups.groupId IS NULL;");

            $this->addSql("ALTER TABLE `$theTableGroups` MODIFY COLUMN groupId INT(11) UNSIGNED NOT NULL;");
            $this->addSql("ALTER TABLE `$theTableGroups`
            ADD CONSTRAINT `fk_object_classificationstore_groups_{$tableNumber}__groupId` FOREIGN KEY (`groupId`)
            REFERENCES `classificationstore_groups` (`id`)
            ON DELETE CASCADE;");

            $theTableData = "object_classificationstore_data_$tableNumber";
            $this->addSql("ALTER TABLE `$theTableData` MODIFY COLUMN groupId INT(11) UNSIGNED NOT NULL;");
            $this->addSql("CREATE INDEX `groupKeys` ON `$theTableData` (`id`, `fieldname`, `groupId`);");
            $this->addSql("ALTER TABLE `$theTableData`
            ADD CONSTRAINT `fk_object_classificationstore_data_{$tableNumber}__id__fieldname__groupId` FOREIGN KEY (`id`, `fieldname`, `groupId`)
            REFERENCES `$theTableGroups` (`id`, `fieldname`, `groupId`)
            ON DELETE CASCADE;");
        }
    }

    public function down(Schema $schema): void
    {
        $tableList = $this->connection->fetchAllAssociative("show tables like 'object_classificationstore_groups_%'");
        foreach ($tableList as $theTableGroups) {
            $theTableGroups = current($theTableGroups);
            $tableArray = explode('_', $theTableGroups);
            $tableNumber = end($tableArray);
            if (!is_numeric($tableNumber)) {
                continue;
            }

            $this->addSql("ALTER TABLE `$theTableGroups` DROP FOREIGN KEY `fk_object_classificationstore_groups_{$tableNumber}__groupId`;");

            $theTableData = "object_classificationstore_data_$tableNumber";
            $this->addSql("ALTER TABLE `$theTableData` DROP FOREIGN KEY `fk_object_classificationstore_data_{$tableNumber}__id__fieldname__groupId`;");
            $this->addSql("ALTER TABLE `$theTableData` DROP INDEX `groupKeys`;");

            $this->addSql("ALTER TABLE `$theTableData` MODIFY COLUMN groupId BIGINT(20) NOT NULL;");
            $this->addSql("ALTER TABLE `$theTableGroups` MODIFY COLUMN groupId BIGINT(20) NOT NULL;");
        }
    }
}
