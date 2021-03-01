<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use Pimcore\Model\DataObject;

class Version20210217111615 extends AbstractPimcoreMigration
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
        $list = new DataObject\ClassDefinition\Listing();
        $list = $list->load();
        foreach ($list as $class) {
            $this->writeMessage(sprintf('Saving php files for class: %s', $class->getName()));
            $class->generateClassFiles(true);
        }

        $list = new DataObject\Objectbrick\Definition\Listing();
        $list = $list->load();
        foreach ($list as $brickDefinition) {
            $this->writeMessage(sprintf('Saving php files for object brick: %s', $brickDefinition->getKey()));
            $brickDefinition->generateClassFiles(true);
        }

        $list = new DataObject\Fieldcollection\Definition\Listing();
        $list = $list->load();
        foreach ($list as $fc) {
            $this->writeMessage(sprintf('Saving php files for field collection: %s', $fc->getKey()));
            $fc->generateClassFiles(true);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->write(sprintf('Please restore your class definition files in %s and run bin/console pimcore:deployment:classes-rebuild manually.', PIMCORE_CLASS_DIRECTORY));
    }
}
