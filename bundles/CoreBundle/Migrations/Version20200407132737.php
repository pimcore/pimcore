<?php

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

        $table->dropPrimaryKey();
        $table->setPrimaryKey(['uuid', 'itemId', 'type']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $table = $schema->getTable('uuids');

        $table->dropPrimaryKey();
        $table->setPrimaryKey( ['itemId', 'type', 'uuid']);
    }
}
