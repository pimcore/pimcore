<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\User;
use Pimcore\Model\User\Listing;


final class Version20221116115427 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add "object bricks" permission';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO `users_permission_definitions` (`key`, `category`) VALUES ('objectbricks', '')");

        $this->addSql('UPDATE `users` SET `permissions`=CONCAT(`permissions`, \',objectbricks\') WHERE `permissions` REGEXP \'(?:^|,)classes(?:$|,)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE `users` SET `permissions`=REGEXP_REPLACE(`permissions`, \'(?:^|,)objectbricks(?:^|,)\', \'\') WHERE `permissions` REGEXP \'(?:^|,)objectbricks(?:$|,)\'');

        $this->addSql("DELETE FROM `users_permission_definitions` WHERE `key` = 'objectbricks'");
    }
}
