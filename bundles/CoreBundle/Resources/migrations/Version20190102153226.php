<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190102153226 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `versions`ADD COLUMN `binaryFileHash` VARCHAR(128) NULL DEFAULT NULL COLLATE 'ascii_general_ci' AFTER `versionCount`");
        $this->addSql('ALTER TABLE `versions`ADD COLUMN `binaryFileId` BIGINT(20) UNSIGNED NULL DEFAULT NULL AFTER `binaryFileHash`');
        $this->addSql('ALTER TABLE `versions` ADD INDEX `binaryFileHash` (`binaryFileHash`)');
        $this->addSql('ALTER TABLE `versions` ADD INDEX `binaryFileId` (`binaryFileId`)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE `versions` DROP COLUMN `binaryFileHash`');
        $this->addSql('ALTER TABLE `versions` DROP COLUMN `binaryFileId`');
    }
}
