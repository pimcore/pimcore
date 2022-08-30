<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220830105212 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT IGNORE INTO users (`parentId`, `name`, `admin`, `active`) VALUES(0, 'system', 'admin', 'active');");
        $this->addSql("UPDATE users set id = 0 where name = 'system'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM users WHERE `id` = 0");
    }
}
