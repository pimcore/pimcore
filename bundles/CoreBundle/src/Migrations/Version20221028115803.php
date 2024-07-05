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
     * @throws Exception
     */
    private function regenerateClassesWithFieldCollections(): void
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
