<?php

declare(strict_types=1);

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
        $tableSchema = $schema->getTable('redirects');
        if ($tableSchema->hasColumn('targetType')) {
            $this->addSql(<<<SQL
                ALTER TABLE `redirects` DROP COLUMN `targetType`;
            SQL);
        }
    }
}
