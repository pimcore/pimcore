<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\DataObject;

/**
 * @internal
 */
final class Version20220901100327 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Regenerate class, fieldcollection & objectbrick definitions to fix video allowed types property';
    }

    public function up(Schema $schema): void
    {
        try {
            $list = new DataObject\ClassDefinition\Listing();
            foreach ($list->getClasses() as $class) {
                $this->write(sprintf('Saving class: %s', $class->getName()));
                $class->save();
            }

            $list = new DataObject\Objectbrick\Definition\Listing();
            foreach ($list->load() as $brickDefinition) {
                $this->write(sprintf('Saving object brick: %s', $brickDefinition->getKey()));
                $brickDefinition->save();
            }

            $list = new DataObject\Fieldcollection\Definition\Listing();
            foreach ($list->load() as $fc) {
                $this->write(sprintf('Saving field collection: %s', $fc->getKey()));
                $fc->save();
            }
        } catch (DataObject\Exception\DefinitionWriteException $e) {
            $this->write(
                'Could not write class definition file. Please set PIMCORE_CLASS_DEFINITION_WRITABLE env.' . "\n" .
                sprintf(
                    'If you already have migrate the definitions you can skip this migration via "php bin/console doctrine:migrations:version --add %s"',
                    __CLASS__
                )
            );

            throw $e;
        }

    }

    public function down(Schema $schema): void
    {
        $this->write(sprintf('Please restore your class definition files in %s and run bin/console pimcore:deployment:classes-rebuild manually.', PIMCORE_CLASS_DEFINITION_DIRECTORY));
    }
}
