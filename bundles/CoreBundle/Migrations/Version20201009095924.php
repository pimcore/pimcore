<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20201009095924 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        if ($schema->getTable('users')->hasColumn('apiKey')) {
            $this->addSql('ALTER TABLE `users` DROP COLUMN `apiKey`;');
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `users` ADD COLUMN `apiKey` varchar(255) DEFAULT NULL;');
    }
}
