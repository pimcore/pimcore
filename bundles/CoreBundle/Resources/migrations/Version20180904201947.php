<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Db;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20180904201947 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->getTable('element_workflow_state');
        $table->addColumn('place', 'string', ['length' => 255]);
        $table->addColumn('workflow', 'string', ['length' => 100]);
        $table->dropPrimaryKey();
        $table->setPrimaryKey(['cid', 'ctype', 'workflowId', 'workflow']);


        $db = Db::get();
        try {
            $db->query("INSERT INTO users_permission_definitions VALUES('workflow_details');");
        } catch (\Exception $e) {
        }

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $table = $schema->getTable('element_workflow_state');
        $table->dropPrimaryKey();
        $table->dropColumn('place');
        $table->dropColumn('workflow');
        $table->setPrimaryKey(['cid', 'ctype', 'workflowId']);
    }
}
