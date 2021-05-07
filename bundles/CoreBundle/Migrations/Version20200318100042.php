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
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20200318100042 extends AbstractPimcoreMigration
{
    public function doesSqlMigrations(): bool
    {
        return false;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        try {
            if (!$schema->getTable('objects')->hasIndex('o_classId')) {
                $this->addSql('ALTER TABLE `objects`
                        ADD INDEX `o_classId` (`o_classId`);');
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
            if ($schema->getTable('objects')->hasIndex('o_classId')) {
                $this->addSql('ALTER TABLE `objects`
                    DROP INDEX `o_classId`
                    ');
            }
        } catch (\Exception $e) {
            $this->writeMessage('An error occurred while performing migrations: ' . $e->getMessage());
        }
    }
}
