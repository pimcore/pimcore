<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240606165618 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the Date Time Locale column to the Users table.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('users')->hasColumn('datetimeLocale')) {
            $this->addSql('ALTER TABLE `users` ADD COLUMN `datetimeLocale` varchar(10) AFTER `language`;');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('users')->hasColumn('datetimeLocale')) {
            $this->addSql('ALTER TABLE `users` DROP COLUMN `datetimeLocale`;');
        }
    }
}
