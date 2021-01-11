<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20201222101114 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        if ($schema->hasTable('translations_website')) {
            $this->addSql('RENAME TABLE translations_website TO translations_messages;');

            //add alias view for BC reasons
            $this->addSql('CREATE OR REPLACE VIEW translations_website AS SELECT * FROM translations_messages;');
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        //add remove alias view if exists
        $this->addSql('DROP VIEW IF EXISTS translations_website;');

        if ($schema->hasTable('translations_messages')) {
            $this->addSql('RENAME TABLE translations_messages TO translations_website;');
        }
    }
}
