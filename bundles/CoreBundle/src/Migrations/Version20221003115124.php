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

final class Version20221003115124 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename o_id columns to id.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE objects CHANGE o_id id int(11) unsigned auto_increment NOT NULL;');
        $this->addSql('ALTER TABLE objects CHANGE o_parentId parentId int(11) unsigned DEFAULT NULL NULL;');
        $this->addSql("ALTER TABLE objects CHANGE o_type `type` enum('object','folder','variant') DEFAULT NULL NULL;");
        $this->addSql("ALTER TABLE objects CHANGE o_key `key` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT '' NULL;");
        $this->addSql('ALTER TABLE objects CHANGE o_path `path` varchar(765) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL NULL;');
        $this->addSql('ALTER TABLE objects CHANGE o_index `index` int(11) unsigned DEFAULT 0 NULL;');
        $this->addSql('ALTER TABLE objects CHANGE o_published published tinyint(1) unsigned DEFAULT 1 NULL;');
        $this->addSql('ALTER TABLE objects CHANGE o_creationDate creationDate int(11) unsigned DEFAULT NULL NULL;');
        $this->addSql('ALTER TABLE objects CHANGE o_modificationDate modificationDate int(11) unsigned DEFAULT NULL NULL;');
        $this->addSql('ALTER TABLE objects CHANGE o_userOwner userOwner int(11) unsigned DEFAULT NULL NULL;');
        $this->addSql('ALTER TABLE objects CHANGE o_userModification userModification int(11) unsigned DEFAULT NULL NULL;');
        $this->addSql('ALTER TABLE objects CHANGE o_classId classId varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL NULL;');
        $this->addSql('ALTER TABLE objects CHANGE o_className className varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL NULL;');
        $this->addSql("ALTER TABLE objects CHANGE o_childrenSortBy childrenSortBy enum('key','index') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL NULL;");
        $this->addSql("ALTER TABLE objects CHANGE o_childrenSortOrder childrenSortOrder enum('ASC','DESC') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL NULL;");
        $this->addSql('ALTER TABLE objects CHANGE o_versionCount versionCount int(10) unsigned DEFAULT 0 NOT NULL;');

        $tableListObject = $this->connection->fetchAllAssociative("SHOW TABLES LIKE 'object_brick_%'");
        $tableListObject = array_merge($tableListObject, $this->connection->fetchAllAssociative("SHOW TABLES LIKE 'object_metadata_%'"));
        $tableListObject = array_merge($tableListObject, $this->connection->fetchAllAssociative("SHOW TABLES LIKE 'object_collection_%'"));
        foreach ($tableListObject as $table) {
            $tableName = current($table);
            $columnExists = $this->connection->fetchAllAssociative("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$tableName' AND COLUMN_NAME = 'o_id'");
            if ($columnExists) {
                $this->addSql("ALTER TABLE $tableName CHANGE o_id id int(10) unsigned DEFAULT 0 NOT NULL;");
            }
        }

        $tableListClassificationstore = $this->connection->fetchAllAssociative("SHOW TABLES LIKE 'object_classificationstore_%'");
        foreach ($tableListClassificationstore as $table) {
            $tableName = current($table);
            $columnExists = $this->connection->fetchAllAssociative("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$tableName' AND COLUMN_NAME = 'o_id'");
            if ($columnExists) {
                $this->addSql("ALTER TABLE $tableName CHANGE o_id id int(11) unsigned NOT NULL;");
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE objects CHANGE id o_id int(11) unsigned auto_increment NOT NULL;');
        $this->addSql('ALTER TABLE objects CHANGE parentId o_parentId int(11) unsigned DEFAULT NULL NULL;');
        $this->addSql("ALTER TABLE objects CHANGE `type` `o_type` enum('object','folder','variant') DEFAULT NULL NULL;");
        $this->addSql("ALTER TABLE objects CHANGE `key` `o_key` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT '' NULL;");
        $this->addSql('ALTER TABLE objects CHANGE `path` `o_path` varchar(765) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL NULL;');
        $this->addSql('ALTER TABLE objects CHANGE `index` `o_index` int(11) unsigned DEFAULT 0 NULL;');
        $this->addSql('ALTER TABLE objects CHANGE published o_published tinyint(1) unsigned DEFAULT 1 NULL;');
        $this->addSql('ALTER TABLE objects CHANGE creationDate o_creationDate int(11) unsigned DEFAULT NULL NULL;');
        $this->addSql('ALTER TABLE objects CHANGE modificationDate o_modificationDate int(11) unsigned DEFAULT NULL NULL;');
        $this->addSql('ALTER TABLE objects CHANGE userOwner o_userOwner int(11) unsigned DEFAULT NULL NULL;');
        $this->addSql('ALTER TABLE objects CHANGE userModification o_userModification int(11) unsigned DEFAULT NULL NULL;');
        $this->addSql('ALTER TABLE objects CHANGE classId o_classId varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL NULL;');
        $this->addSql('ALTER TABLE objects CHANGE className o_className varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL NULL;');
        $this->addSql("ALTER TABLE objects CHANGE childrenSortBy o_childrenSortBy enum('key','index') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL NULL;");
        $this->addSql("ALTER TABLE objects CHANGE childrenSortOrder o_childrenSortOrder enum('ASC','DESC') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL NULL;");
        $this->addSql('ALTER TABLE objects CHANGE versionCount o_versionCount int(10) unsigned DEFAULT 0 NOT NULL;');

        $tableListObject = $this->connection->fetchAllAssociative("SHOW TABLES LIKE 'object_brick_%'");
        $tableListObject = array_merge($tableListObject, $this->connection->fetchAllAssociative("SHOW TABLES LIKE 'object_metadata_%'"));
        $tableListObject = array_merge($tableListObject, $this->connection->fetchAllAssociative("SHOW TABLES LIKE 'object_collection_%'"));
        foreach ($tableListObject as $table) {
            $tableName = current($table);
            $columnExists = $this->connection->fetchAllAssociative("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$tableName' AND COLUMN_NAME = 'id'");
            if ($columnExists) {
                $this->addSql("ALTER TABLE $tableName CHANGE id o_id int(10) unsigned DEFAULT 0 NOT NULL;");
            }
        }

        $tableListClassificationstore = $this->connection->fetchAllAssociative("SHOW TABLES LIKE 'object_classificationstore_%'");
        foreach ($tableListClassificationstore as $table) {
            $tableName = current($table);
            $columnExists = $this->connection->fetchAllAssociative("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$tableName' AND COLUMN_NAME = 'id'");
            if ($columnExists) {
                $this->addSql("ALTER TABLE $tableName CHANGE id o_id int(11) unsigned NOT NULL;");
            }
        }
    }
}
