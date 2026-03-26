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

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210412112812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $notesTable = $schema->getTable('notes_data');
        if ($notesTable->hasIndex('id')) {
            $this->addSql('ALTER TABLE `notes_data` DROP INDEX `id`;');
        }

        if (!$notesTable->getPrimaryKey()) {
            $this->addSql('ALTER TABLE `notes_data` ADD PRIMARY KEY (`id`, `name`);');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `notes_data` DROP INDEX `PRIMARY`;');
        $this->addSql('ALTER TABLE `notes_data` ADD KEY (`id`);');
    }
}
