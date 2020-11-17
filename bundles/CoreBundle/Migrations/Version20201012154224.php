<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20201012154224 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        if($schema->getTable('glossary')->hasColumn('acronym')) {
            $this->addSql('ALTER TABLE glossary DROP COLUMN acronym');
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE glossary ADD COLUMN `acronym` varchar(255) DEFAULT NULL');
    }
}
