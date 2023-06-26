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
use Pimcore\Model\DataObject\Exception\DefinitionWriteException;

final class Version20230606112233 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rebuild object-bricks';
    }

    /**
     * @throws DefinitionWriteException
     */
    public function up(Schema $schema): void
    {
        try {
            $list = new DataObject\Objectbrick\Definition\Listing();
            foreach ($list->load() as $brickDefinition) {
                $this->write(sprintf('Saving object brick: %s', $brickDefinition->getKey()));
                $brickDefinition->save();
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
