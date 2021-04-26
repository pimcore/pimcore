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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\DataObject;

/**
 * @internal
 */
final class Version20210107103923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $list = new DataObject\ClassDefinition\Listing();
        $list = $list->load();

        foreach ($list as $class) {
            $this->write(sprintf('Saving class: %s', $class->getName()));
            $class->save();
        }

        $list = new DataObject\Objectbrick\Definition\Listing();
        $list = $list->load();
        foreach ($list as $brickDefinition) {
            $this->write(sprintf('Saving object brick: %s', $brickDefinition->getKey()));
            $brickDefinition->save();
        }

        $list = new DataObject\Fieldcollection\Definition\Listing();
        $list = $list->load();
        foreach ($list as $fc) {
            $this->write(sprintf('Saving field collection: %s', $fc->getKey()));
            $fc->save();
        }
    }

    public function down(Schema $schema): void
    {
        $this->write(sprintf('Please restore your class definition files in %s and run bin/console pimcore:deployment:classes-rebuild manually.', PIMCORE_CLASS_DIRECTORY));
    }
}
