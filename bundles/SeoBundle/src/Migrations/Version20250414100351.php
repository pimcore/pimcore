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

namespace Pimcore\Bundle\SeoBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250414100351 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add `targetType` column to `redirects` table.';
    }

    public function up(Schema $schema): void
    {
        // the bundle installer creates the table; skip if it does not exist yet
        if (!$schema->hasTable('redirects')) {
            return;
        }

        $tableSchema = $schema->getTable('redirects');
        if ($tableSchema->hasColumn('targetType')) {
            return;
        }

        $this->addSql(<<<SQL
            ALTER TABLE `redirects`
            ADD COLUMN `targetType` ENUM('document','asset','object') DEFAULT NULL AFTER `target`;
        SQL);

        $this->addSql(<<<SQL
            UPDATE `redirects`
            SET `targetType` = 'document'
            WHERE `target` REGEXP '^[1-9][0-9]*$';
        SQL);
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('redirects')) {
            return;
        }

        $tableSchema = $schema->getTable('redirects');
        if ($tableSchema->hasColumn('targetType')) {
            $this->addSql(<<<SQL
                ALTER TABLE `redirects` DROP COLUMN `targetType`;
            SQL);
        }
    }
}
