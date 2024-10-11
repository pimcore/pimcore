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
use Pimcore\Model\DataObject;

final class Version20240323121349 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rebuild classes, objects-bricks and field collections to add foreign key for quantity value units';
    }

    public function up(Schema $schema): void
    {
        $classDefinitions = new DataObject\ClassDefinition\Listing();
        foreach ($classDefinitions->load() as $classDefinition) {
            $this->write(sprintf('Saving class: %s', $classDefinition->getName()));
            $classDefinition->save();
        }

        $objectBricks = new DataObject\Objectbrick\Definition\Listing();
        foreach ($objectBricks->load() as $brickDefinition) {
            $this->write(sprintf('Saving object brick: %s', $brickDefinition->getKey()));
            $brickDefinition->save();
        }

        $fieldCollections = new DataObject\Fieldcollection\Definition\Listing();
        foreach ($fieldCollections->load() as $fieldCollection) {
            $this->write(sprintf('Saving field collection: %s', $fieldCollection->getKey()));
            $fieldCollection->save();
        }
    }

    public function down(Schema $schema): void
    {
        $this->write(
            sprintf(
                'Please restore your class definition files in %s and run
                    bin/console pimcore:deployment:classes-rebuild manually.',
                PIMCORE_CLASS_DEFINITION_DIRECTORY
            )
        );
    }
}
