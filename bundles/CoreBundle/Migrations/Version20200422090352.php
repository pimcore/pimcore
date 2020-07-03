<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20200422090352 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $elements = ['asset', 'document', 'object'];
        foreach ($elements as $element) {
            $table = $schema->getTable('users_workspaces_'.$element);

            if (!$table->hasIndex('cpath_userId')) {
                $table->addUniqueIndex(['cpath', 'userId'], 'cpath_userId');
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $elements = ['asset', 'document', 'object'];
        foreach ($elements as $element) {
            $table = $schema->getTable('users_workspaces_'.$element);

            if ($table->hasIndex('cpath_userId')) {
                $table->dropIndex('cpath_userId');
            }
        }
    }
}
