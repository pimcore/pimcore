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
 * @internal
 */
class Version20211103055110 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $db = \Pimcore\Db::get();

        $classes = $db->fetchFirstColumn('SELECT id FROM classes');

        foreach ($classes as $class) {
            $objectDatastoreTableRelation = 'object_relations_' . $class;

            if ($schema->hasTable($objectDatastoreTableRelation)) {
                $this->addSql(
                    "UPDATE $objectDatastoreTableRelation SET `type` = 'object' WHERE `type` = NULL OR `type` =''"
                );
                $this->addSql(
                    "ALTER TABLE $objectDatastoreTableRelation CHANGE COLUMN
                        `type` `type` ENUM('object', 'asset', 'document') NOT NULL;"
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $db = \Pimcore\Db::get();

        $classes = $db->fetchAssociative('SELECT id FROM classes');

        foreach ($classes as $class) {
            $objectDatastoreTableRelation = 'object_relations_' . $class;

            if ($schema->hasTable($objectDatastoreTableRelation)) {
                $this->addSql(
                    "ALTER TABLE $objectDatastoreTableRelation CHANGE COLUMN
                        `type` `type` VARCHAR(50) NOT NULL DEFAULT '';"
                );
            }
        }
    }
}
