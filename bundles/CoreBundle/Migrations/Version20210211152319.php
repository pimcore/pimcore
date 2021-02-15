<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;

class Version20210211152319 extends AbstractMigration
{
    public function doesSqlMigrations(): bool
    {
        return false;
    }

    /**
     * @param Schema $schema
     *
     * @throws \Exception
     */
    public function up(Schema $schema)
    {
        $this->write('Saving all class definitions ...');
        $list = new ClassDefinition\Listing();
        $list = $list->load();
        foreach ($list as $class) {
            $class->save();
        }

        $this->write('Saving all object-brick definitions ...');
        $list = new DataObject\Objectbrick\Definition\Listing();
        $list = $list->load();
        foreach ($list as $brickDefinition) {
            $brickDefinition->save();
        }

        $this->write('Saving all field-collection definitions ...');
        $list = new DataObject\Fieldcollection\Definition\Listing();
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
        $this->write('Please execute bin/console pimcore:deployment:classes-rebuild afterwards.');
    }
}
