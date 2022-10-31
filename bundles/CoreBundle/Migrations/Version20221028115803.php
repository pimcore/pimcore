<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\DataObject\ClassDefinition\Listing;

final class Version20221028115803 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Regenerate definition files of classes with field collections';
    }

    public function up(Schema $schema): void
    {
        $this->regenerateClassesWithFieldCollections();
    }

    public function down(Schema $schema): void
    {
        $this->regenerateClassesWithFieldCollections();
    }

    /**
     * @throws \Exception
     */
    private function regenerateClassesWithFieldCollections()
    {
        $listing = new Listing();
        foreach ($listing->getClasses() as $class) {
            $fds = $class->getFieldDefinitions();
            foreach ($fds as $fd) {
                if ($fd instanceof \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections) {
                    $this->write(sprintf('Saving php files for class: %s', $class->getName()));
                    $class->generateClassFiles(true);
                    continue 2;
                }
            }
        }
    }
}
