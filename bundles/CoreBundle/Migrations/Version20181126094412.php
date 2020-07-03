<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use Pimcore\Model\DataObject;

class Version20181126094412 extends AbstractPimcoreMigration
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
        $list = new DataObject\ClassDefinition\Listing();
        $list = $list->load();

        foreach ($list as $class) {
            $this->writeMessage(sprintf('Saving class: %s', $class->getName()));
            $class->save();
        }

        $list = new DataObject\Objectbrick\Definition\Listing();
        $list = $list->load();
        foreach ($list as $brickDefinition) {
            $this->writeMessage(sprintf('Saving object brick: %s', $brickDefinition->getKey()));
            $brickDefinition->save();
        }

        $list = new DataObject\Fieldcollection\Definition\Listing();
        $list = $list->load();
        foreach ($list as $fc) {
            $this->writeMessage(sprintf('Saving field collection: %s', $fc->getKey()));
            $fc->save();
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->writeMessage(sprintf('Please restore your class definition files in %s and run bin/console pimcore:deployment:classes-rebuild manually.', PIMCORE_CLASS_DIRECTORY));
    }
}
