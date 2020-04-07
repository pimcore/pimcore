<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20200407145422 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->getTable('notes');
        if ($table->hasIndex('cid')) {
            $table->dropIndex('cid');
        }

        if ($table->hasIndex('ctype')) {
            $table->dropIndex('ctype');
        }

        if (!$table->hasIndex('cid_ctype')) {
            $table->addIndex(['cid', 'ctype'], 'cid_ctype');
        }

        if (!$table->hasIndex('user')) {
            $table->addIndex(['user'], 'user');
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $table = $schema->getTable('notes');
        if (!$table->hasIndex('cid')) {
            $table->addIndex(['cid'], 'cid');
        }

        if (!$table->hasIndex('ctype')) {
            $table->addIndex(['ctype'], 'ctype');
        }

        if ($table->hasIndex('cid_ctype')) {
            $table->dropIndex('cid_ctype');
        }

        if ($table->hasIndex('user')) {
            $table->dropIndex('user');
        }
    }
}
