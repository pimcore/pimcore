<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20200518205733 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $table = $schema->getTable('tags');
        $table->addColumn('translations', 'text', ['notNull' => false, 'default' => 'null']);
        $table->addColumn('creationDate', 'integer', ['unsigned' => true, 'length' => 20, 'notNull' => false, 'default' => 'null']);
        $table->addColumn('modificatinDate', 'integer', ['unsigned' => true, 'length' => 20, 'notNull' => false, 'default' => 'null']);
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function down(Schema $schema)
    {
        $table = $schema->getTable('tags');
        $table->dropColumn('translations');
        $table->dropColumn('creationDate');
        $table->dropColumn('modificationDate');
    }
}
