<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use Pimcore\Model\DataObject\ClassDefinition;

class Version20200421142950 extends AbstractPimcoreMigration
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
        try {
            $list = new ClassDefinition\Listing();
            $list = $list->load();

            foreach ($list as $class) {
                $class->generateClassFiles(false);
            }
        } catch (\Exception $e) {
            $this->writeMessage('An error occurred while generating class definitions: ' . $e->getMessage());
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
