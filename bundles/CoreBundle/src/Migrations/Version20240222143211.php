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
use Pimcore\Model\Dao\AbstractDao;

/**
 * Modifies foreign key constraints for specified tables, changing their names from ending in '_o_id' to '_id' and adding ON DELETE CASCADE.
 * The down method reverses the naming back to '_o_id' while keeping ON DELETE CASCADE.
 */
final class Version20240222143211 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Dynamically rename _o_id foreign keys to _id.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET foreign_key_checks = 0');

        foreach ($schema->getTables() as $table) {
            if ($table->hasColumn('id')) {
                $tableName = $table->getName();

                if (
                    str_starts_with($tableName, 'object_brick_') ||
                    str_starts_with($tableName, 'object_classificationstore_') ||
                    str_starts_with($tableName, 'object_collection_') ||
                    str_starts_with($tableName, 'object_metadata_')
                ) {
                    $foreignKeyWithoutOPrefix = AbstractDao::getForeignKeyName($tableName, 'id');
                    $foreignKeyWithOPrefix = AbstractDao::getForeignKeyName($tableName, 'o_id');

                    if ($table->hasForeignKey($foreignKeyWithOPrefix)) {
                        $this->addSql("ALTER TABLE {$tableName} DROP FOREIGN KEY {$foreignKeyWithOPrefix}");
                        $this->addSql("ALTER TABLE {$tableName} ADD CONSTRAINT {$foreignKeyWithoutOPrefix} FOREIGN KEY (id) REFERENCES objects(id) ON DELETE CASCADE");
                    }
                }
            }
        }

        $this->addSql('SET foreign_key_checks = 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('SET foreign_key_checks = 0');

        foreach ($schema->getTables() as $table) {
            if ($table->hasColumn('id')) {
                $tableName = $table->getName();

                if (
                    str_starts_with($tableName, 'object_brick_') ||
                    str_starts_with($tableName, 'object_classificationstore_') ||
                    str_starts_with($tableName, 'object_collection_') ||
                    str_starts_with($tableName, 'object_metadata_')
                ) {
                    $foreignKeyWithoutOPrefix = AbstractDao::getForeignKeyName($tableName, 'id');
                    $foreignKeyWithOPrefix = AbstractDao::getForeignKeyName($tableName, 'o_id');

                    if ($table->hasForeignKey($foreignKeyWithoutOPrefix)) {
                        $this->addSql("ALTER TABLE {$tableName} DROP FOREIGN KEY {$foreignKeyWithoutOPrefix}");
                        $this->addSql("ALTER TABLE {$tableName} ADD CONSTRAINT {$foreignKeyWithOPrefix} FOREIGN KEY (id) REFERENCES objects(id) ON DELETE CASCADE");
                    }
                }
            }
        }

        $this->addSql('SET foreign_key_checks = 1');
    }
}
