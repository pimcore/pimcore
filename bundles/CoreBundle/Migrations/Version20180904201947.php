<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20180904201947 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->writeMessage('Changing Database schema for new workflows. Please see upgrade notes for migration of data!');

        $table = $schema->getTable('element_workflow_state');
        $table->addColumn('place', 'string', ['length' => 255]);
        $table->addColumn('workflow', 'string', ['length' => 100]);
        $table->dropPrimaryKey();
        $table->setPrimaryKey(['cid', 'ctype', 'workflowId', 'workflow']);

        $this->addSql("INSERT IGNORE INTO users_permission_definitions (`key`) VALUES('workflow_details');");
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
