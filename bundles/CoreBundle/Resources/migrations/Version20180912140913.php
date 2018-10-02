<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Objectbrick\Definition;

/**
 * Class Version20180912140913
 *
 * @package Pimcore\Bundle\CoreBundle\Migrations
 */
class Version20180912140913 extends AbstractPimcoreMigration
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
        $list = new ClassDefinition\Listing();
        $list = $list->load();

        foreach ($list as $class) {
            $class->save(false);
        }

        $list = new Definition\Listing();
        $list = $list->load();
        foreach ($list as $brickDefinition) {
            $brickDefinition->save();
        }

        $list = new \Pimcore\Model\DataObject\Fieldcollection\Definition\Listing();
        $list = $list->load();
        foreach ($list as $fc) {
            $fc->save();
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->writeMessage('Please execute bin/console pimcore:deployment:classes-rebuild afterwards.');
    }
}
