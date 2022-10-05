<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221005122653 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add fulltext index to search_backend_data table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE FULLTEXT INDEX search_backend_data_fulltext_index ON search_backend_data(`data`, `properties`, `fullpath`);");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE search_backend_data DROP INDEX search_backend_data_fulltext_index;");

    }
}
