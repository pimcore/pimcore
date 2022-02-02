<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\DataObject\ClassDefinition\Listing;

final class Version20220201132131 extends AbstractMigration
{
    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Updates class definition files';
    }

    public function up(Schema $schema): void
    {
        $this->regenerateClasses();
    }

    public function down(Schema $schema): void
    {
        $this->regenerateClasses();
    }

    /**
     * @throws \Exception
     */
    private function regenerateClasses()
    {
        $listing = new Listing();
        foreach ($listing->getClasses() as $class) {
            $this->write(sprintf('Saving php files for class: %s', $class->getName()));
            $class->generateClassFiles(false);
        }
    }
}
