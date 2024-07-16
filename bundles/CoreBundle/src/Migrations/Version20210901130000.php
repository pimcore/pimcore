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

final class Version20210901130000 extends AbstractMigration
{
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
     * @throws Exception
     */
    private function regenerateClasses(): void
    {
        $listing = new Listing();
        foreach ($listing->getClasses() as $class) {
            $this->write(sprintf('Saving php files for class: %s', $class->getName()));
            $class->generateClassFiles(false);
        }
    }
}
