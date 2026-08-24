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
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Exception\DefinitionWriteException;

final class Version20240708083500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rebuild classes, object-bricks, field-collection and custom layouts';
    }

    /**
     * @throws DefinitionWriteException
     */
    public function up(Schema $schema): void
    {
        // Delete old Version Name
        $this->addSql('DELETE FROM `migration_versions` WHERE `migration_versions`.`version` = \'Pimcore\\\\Bundle\\\\CoreBundle\\\\Migrations\\\\Version20210107103923\'');
        $this->addSql('DELETE FROM `migration_versions` WHERE `migration_versions`.`version` = \'Pimcore\\\\Bundle\\\\CoreBundle\\\\Migrations\\\\Version20210706090823\'');
        $this->addSql('DELETE FROM `migration_versions` WHERE `migration_versions`.`version` = \'Pimcore\\\\Bundle\\\\CoreBundle\\\\Migrations\\\\Version20211117173000\'');
        $this->addSql('DELETE FROM `migration_versions` WHERE `migration_versions`.`version` = \'Pimcore\\\\Bundle\\\\CoreBundle\\\\Migrations\\\\Version20230412105530\'');
        $this->addSql('DELETE FROM `migration_versions` WHERE `migration_versions`.`version` = \'Pimcore\\\\Bundle\\\\CoreBundle\\\\Migrations\\\\Version20230508121105\'');
        $this->addSql('DELETE FROM `migration_versions` WHERE `migration_versions`.`version` = \'Pimcore\\\\Bundle\\\\CoreBundle\\\\Migrations\\\\Version20230516161000\'');
        $this->addSql('DELETE FROM `migration_versions` WHERE `migration_versions`.`version` = \'Pimcore\\\\Bundle\\\\CoreBundle\\\\Migrations\\\\Version20230606112233\'');

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

            $list = new DataObject\ClassDefinition\CustomLayout\Listing();
            foreach ($list->getLayoutDefinitions() as $layout) {
                $this->write(sprintf('Saving custom layout: %s', $layout->getName()));
                $layout->save();
            }
        } catch (DataObject\Exception\DefinitionWriteException $e) {
            $this->write(
                'Could not write class definition file.' . "\n" .
                'Possible causes:' . "\n" .
                '- If using symfony-config write target: Configs are read-only in production mode (non-debug). ' .
                'Temporarily enable debug mode or switch to an alternate config-storage to run this migration.' . "\n" .
                '- Missing write permissions: Set PIMCORE_CLASS_DEFINITION_WRITABLE env variable.' . "\n" .
                sprintf(
                    'If definitions are already migrated, skip this migration: ' . "\n" .
                    '  "php bin/console doctrine:migrations:version --add %s"',
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
