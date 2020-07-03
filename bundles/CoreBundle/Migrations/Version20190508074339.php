<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20190508074339 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE `versions`
            DROP INDEX `ctype`,
            ADD INDEX `ctype_cid` (`ctype`, `cid`);');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE `versions`
            DROP INDEX `ctype_cid`,
            ADD INDEX `ctype` (`ctype`);');
    }
}
