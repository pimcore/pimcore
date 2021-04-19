<?php

declare(strict_types=1);

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
        $this->addSql('ALTER TABLE `notes_data` DROP INDEX `id`;');
        $this->addSql('ALTER TABLE `notes_data` ADD PRIMARY KEY (`id`, `name`);');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `notes_data` DROP INDEX `PRIMARY`;');
        $this->addSql('ALTER TABLE `notes_data` ADD KEY (`id`);');
    }
}
