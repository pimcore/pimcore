<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Exception;
use Pimcore;
use Pimcore\Bundle\CoreBundle\DependencyInjection\ContainerAwareInterface;
use Pimcore\Bundle\CoreBundle\DependencyInjection\ContainerAwareTrait;
use Pimcore\DataObject\ClassBuilder\PHPClassDumperInterface;
use Pimcore\DataObject\ClassBuilder\PHPFieldCollectionClassDumperInterface;
use Pimcore\DataObject\ClassBuilder\PHPObjectBrickClassDumperInterface;
use Pimcore\DataObject\ClassBuilder\PHPObjectBrickContainerClassDumperInterface;
use Pimcore\Model\DataObject;

final class Version20231127124738 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function getDescription(): string
    {
        return 'Regenerate class/objectbrick/fieldcollection php files';
    }

    public function up(Schema $schema): void
    {
        $this->regenerate();
    }

    public function down(Schema $schema): void
    {
        $this->regenerate();
    }

    /**
     * @throws Exception
     */
    private function regenerate(): void
    {
        $classDumper = Pimcore::getContainer()->get(PHPClassDumperInterface::class);
        $brickClassDumper = Pimcore::getContainer()->get(PHPObjectBrickClassDumperInterface::class);
        $brickContainerClassDumper = Pimcore::getContainer()->get(PHPObjectBrickContainerClassDumperInterface::class);
        $collectionClassDumper = Pimcore::getContainer()->get(PHPFieldCollectionClassDumperInterface::class);

        $listing = new DataObject\ClassDefinition\Listing();
        foreach ($listing->getClasses() as $class) {
            $this->write(sprintf('Saving php files for class: %s', $class->getName()));
            $classDumper->dumpPHPClasses($class);
        }

        $list = new DataObject\Objectbrick\Definition\Listing();
        $list = $list->load();
        foreach ($list as $brickDefinition) {
            $this->write(sprintf('Saving php files for objectbrick: %s', $brickDefinition->getKey()));
            $brickClassDumper->dumpPHPClasses($brickDefinition);
            $brickContainerClassDumper->dumpContainerClasses($brickDefinition);
        }

        $list = new DataObject\Fieldcollection\Definition\Listing();
        $list = $list->load();
        foreach ($list as $fcDefinition) {
            $this->write(sprintf('Saving php files for fieldcollection: %s', $fcDefinition->getKey()));
            $collectionClassDumper->dumpPHPClass($fcDefinition);
        }
    }
}
