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
        $this->addSql(<<<SQL
            ALTER TABLE `redirects`
            ADD COLUMN `targetType` VARCHAR(255) DEFAULT NULL AFTER `target`;
        SQL);

        $this->addSql(<<<SQL
            UPDATE `redirects`
            SET `targetType` = 'document'
            WHERE CAST(`target` AS UNSIGNED) > 0;
        SQL);

        $now = time();
        $this->addSql(<<<SQL
            INSERT INTO `translations_admin` (
                `key`,
                `type`,
                `language`,
                `text`,
                `creationDate`,
                `modificationDate`,
                `userOwner`,
                `userModification`
            )
            VALUES (
                'target_type',
                'simple',
                'en',
                'Target Type',
                $now,
                $now,
                0,
                0
            )
            ON DUPLICATE KEY UPDATE
                `text` = VALUES(`text`),
                `modificationDate` = VALUES(`modificationDate`),
                `userModification` = VALUES(`userModification`);
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE `redirects`
            DROP COLUMN `targetType`;
        SQL);

        $this->addSql(<<<SQL
            DELETE FROM `translations_admin`
            WHERE `key` = 'target_type';
        SQL);
    }
}
