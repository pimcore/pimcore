<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20191107141816 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE locks ENGINE=InnoDB;');
        $this->addSql('ALTER TABLE cache_tags ENGINE=InnoDB;');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE locks ENGINE=MEMORY;');
        $this->addSql('ALTER TABLE cache_tags ENGINE=MEMORY;');
    }
}
