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
final class Version20210531125102 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Added LocalizedErrorDocuments to sites table.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('sites')->hasColumn('localizedErrorDocuments')) {
            $this->addSql('ALTER TABLE `sites` ADD COLUMN `localizedErrorDocuments` text;');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('sites')->hasColumn('localizedErrorDocuments')) {
            $this->addSql('ALTER TABLE `sites` DROP COLUMN `localizedErrorDocuments`;');
        }
    }
}
