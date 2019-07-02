<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use Pimcore\Model\DataObject\ClassDefinition;

/**
 * Class Version20190419083749
 *
 * @package Pimcore\Bundle\CoreBundle\Migrations
 */
class Version20190419083749 extends AbstractPimcoreMigration
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
            $resaveClass = false;
            foreach($class->getFieldDefinitions() as $fieldDefinition) {
                if($fieldDefinition instanceof ClassDefinition\Data\ReverseManyToManyObjectRelation) {
                    $resaveClass = true;
                }
            }

            if($resaveClass) {
                $class->save(false);
            }
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
