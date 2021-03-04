<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20210303165115 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        if (!$schema->hasTable('settings_store')) {
            $this->addSql("CREATE TABLE `settings_store` (
                  `id` varchar(190) NOT NULL DEFAULT '',
                  `scope` varchar(190) DEFAULT NULL,
                  `data` longtext,
                  `type` enum('bool','int','float','string') NOT NULL DEFAULT 'string',
                  PRIMARY KEY (`id`),
                  KEY `scope` (`scope`)
                ) DEFAULT CHARSET=utf8mb4;"
            );
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('DROP TABLE IF EXISTS settings_store;');
    }
}
