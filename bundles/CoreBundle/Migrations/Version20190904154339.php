<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190904154339 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE `redirects` ADD COLUMN `userOwner` int(11) unsigned NULL;');
        $this->addSql('ALTER TABLE `redirects` ADD COLUMN `userModification` int(11) unsigned NULL AFTER `userOwner`;');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE `redirects` DROP COLUMN `userOwner`;');
        $this->addSql('ALTER TABLE `redirects` DROP COLUMN `userModification`;');
    }
}
