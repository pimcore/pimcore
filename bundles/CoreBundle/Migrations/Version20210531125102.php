<?php

declare(strict_types=1);

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
        $this->addSql('ALTER TABLE `sites` ADD COLUMN `localizedErrorDocuments` text;');
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('sites')->hasColumn('localizedErrorDocuments')) {
            $this->addSql('ALTER TABLE `sites` DROP COLUMN `localizedErrorDocuments`;');
        }
    }
}
