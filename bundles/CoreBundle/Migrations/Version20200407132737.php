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

class Version20200407132737 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->getTable('uuids');

        if ($table->hasPrimaryKey()) {
            $table->dropPrimaryKey();
        }

        $table->setPrimaryKey(['uuid', 'itemId', 'type']);

        if ($table->hasIndex('itemId_type_uuid')) {
            $table->dropIndex('itemId_type_uuid');
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $table = $schema->getTable('uuids');

        $table->dropPrimaryKey();
        $table->setPrimaryKey(['itemId', 'type', 'uuid']);
    }
}
