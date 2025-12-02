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

final class Version20221005090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add locked column to notes table';
    }

    public function up(Schema $schema): void
    {
        if ($schema->getTable('notes')->hasColumn('locked') === false) {
            $this->addSql('ALTER TABLE `notes` ADD COLUMN `locked` tinyint(1) unsigned DEFAULT \'1\';');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('notes')->hasColumn('locked')) {
            $this->addSql('ALTER TABLE `notes` DROP COLUMN `locked`;');
        }
    }
}
