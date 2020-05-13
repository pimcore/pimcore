<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190729085052 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE users_permission_definitions ADD `category` varchar(50) NOT NULL DEFAULT '';");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // no downgrade because of loss of category data
    }
}
