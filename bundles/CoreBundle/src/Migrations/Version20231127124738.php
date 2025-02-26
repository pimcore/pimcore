<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Exception;
use Pimcore;
use Pimcore\DataObject\ClassBuilder\PHPClassDumperInterface;
use Pimcore\DataObject\ClassBuilder\PHPFieldCollectionClassDumperInterface;
use Pimcore\DataObject\ClassBuilder\PHPObjectBrickClassDumperInterface;
use Pimcore\DataObject\ClassBuilder\PHPObjectBrickContainerClassDumperInterface;
use Pimcore\Model\DataObject;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

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
