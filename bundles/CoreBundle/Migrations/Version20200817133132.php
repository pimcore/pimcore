<?php

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
use Pimcore\Db;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20200817133132 extends AbstractPimcoreMigration
{
    public function doesSqlMigrations(): bool
    {
        return true;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        try {
            $db = Db::get();

            // add index to primary key in relations tables
            $relationTables = $db->fetchAll("SHOW TABLES LIKE 'object\_relations\_%'");
            foreach ($relationTables as $table) {
                $relationTable = current($table);

                if (!$schema->getTable($relationTable)->hasColumn('id')) {
                    if ($schema->getTable($relationTable)->hasPrimaryKey()) {
                        $this->addSql('ALTER TABLE `'.$relationTable.'` DROP PRIMARY KEY;');
                    }
                    $this->addSql('ALTER TABLE `'.$relationTable.'` ADD COLUMN `id` BIGINT(20) NOT NULL PRIMARY KEY AUTO_INCREMENT FIRST');
                }
            }
        } catch (\Exception $e) {
            $this->writeMessage('An error occurred while performing migrations: ' . $e->getMessage());
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        try {
            $db = Db::get();

            // add index to primary key in relations tables
            $relationTables = $db->fetchAll("SHOW TABLES LIKE 'object\_relations\_%'");
            foreach ($relationTables as $table) {
                $relationTable = current($table);

                if ($schema->getTable($relationTable)->hasColumn('id')) {
                    $this->addSql('ALTER TABLE `'.$relationTable.'` DROP COLUMN `id`');
                }
            }
        } catch (\Exception $e) {
            $this->writeMessage('An error occurred while performing migrations: ' . $e->getMessage());
        }
    }
}
