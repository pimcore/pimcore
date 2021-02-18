<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210218142556 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM users_permission_definitions WHERE `key` = 'piwik_settings'");
        $this->addSql("DELETE FROM users_permission_definitions WHERE `key` = 'piwik_reports'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql("INSERT IGNORE INTO users_permission_definitions (`key`) VALUES('piwik_settings');");
        $this->addSql("INSERT IGNORE INTO users_permission_definitions (`key`) VALUES('piwik_reports');");
    }
}
