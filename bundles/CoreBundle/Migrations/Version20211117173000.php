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

final class Version20211117173000 extends AbstractMigration
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
        $list = new DataObject\ClassDefinition\Listing();
        foreach ($list->getClasses() as $class) {
            $this->write(sprintf('Saving class: %s', $class->getName()));
            try {
                $class->save();
            } catch (DataObject\ClassDefinition\Exception\WriteException $e) {
                $this->write($e->getMessage());
            }
        }

        $list = new DataObject\Objectbrick\Definition\Listing();
        foreach ($list->load() as $brickDefinition) {
            $this->write(sprintf('Saving object brick: %s', $brickDefinition->getKey()));
            try {
                $brickDefinition->save();
            } catch (DataObject\ClassDefinition\Exception\WriteException $e) {
                $this->write($e->getMessage());
            }
        }

        $list = new DataObject\Fieldcollection\Definition\Listing();
        foreach ($list->load() as $fc) {
            $this->write(sprintf('Saving field collection: %s', $fc->getKey()));
            try {
                $fc->save();
            } catch (DataObject\ClassDefinition\Exception\WriteException $e) {
                $this->write($e->getMessage());
            }
        }

        $list = new DataObject\ClassDefinition\CustomLayout\Listing();
        foreach ($list->getLayoutDefinitions() as $layout) {
            $this->write(sprintf('Saving custom layout: %s', $layout->getName()));
            try {
                $layout->save();
            } catch (DataObject\ClassDefinition\Exception\WriteException $e) {
                $this->write($e->getMessage());
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->write(sprintf('Please restore your class definition files in %s and run bin/console pimcore:deployment:classes-rebuild manually.', PIMCORE_CLASS_DEFINITION_DIRECTORY));
    }
}
