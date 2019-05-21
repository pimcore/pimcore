<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190520151448 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE `documents_email` DROP COLUMN `legacy`;');
        $this->addSql('ALTER TABLE `documents_newsletter` DROP COLUMN `legacy`;');
        $this->addSql('ALTER TABLE `documents_page` DROP COLUMN `legacy`;');
        $this->addSql('ALTER TABLE `documents_snippet` DROP COLUMN `legacy`;');
        $this->addSql('ALTER TABLE `documents_printpage` DROP COLUMN `legacy`;');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->writeMessage('<error>This migration cannot be undone! Please restore the schema and data of the following tables manually: documents_email, documents_newsletter, documents_page, documents_snippet, documents_printpage </error>');
    }
}
