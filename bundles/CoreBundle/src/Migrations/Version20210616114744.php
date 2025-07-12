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

final class Version20210616114744 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add columns staticGeneratorEnabled & staticGeneratorLifetime to documents_page table';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('documents_page')->hasColumn('staticGeneratorEnabled')) {
            $this->addSql('ALTER TABLE `documents_page` ADD COLUMN `staticGeneratorEnabled` tinyint(1) unsigned DEFAULT NULL');
        }

        if (!$schema->getTable('documents_page')->hasColumn('staticGeneratorLifetime')) {
            $this->addSql('ALTER TABLE `documents_page` ADD COLUMN `staticGeneratorLifetime` int(11) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('documents_page')->hasColumn('staticGeneratorEnabled')) {
            $this->addSql('ALTER TABLE `documents_page` DROP COLUMN `staticGeneratorEnabled`');
        }

        if ($schema->getTable('documents_page')->hasColumn('staticGeneratorLifetime')) {
            $this->addSql('ALTER TABLE `documents_page` DROP COLUMN `staticGeneratorLifetime`');
        }
    }
}
