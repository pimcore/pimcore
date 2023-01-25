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
        $this->addSql("INSERT INTO `users_permission_definitions` (`key`, `category`) VALUES ('objectbricks', 'Data Objects')");

        $this->addSql('UPDATE `users` SET `permissions`=CONCAT(`permissions`, \',objectbricks\') WHERE `permissions` REGEXP \'(?:^|,)classes(?:$|,)\'');

        $this->addSql("INSERT INTO `users_permission_definitions` (`key`, `category`) VALUES ('fieldcollections', 'Data Objects')");

        $this->addSql('UPDATE `users` SET `permissions`=CONCAT(`permissions`, \',fieldcollections\') WHERE `permissions` REGEXP \'(?:^|,)classes(?:$|,)\'');

        $this->addSql("INSERT INTO `users_permission_definitions` (`key`, `category`) VALUES ('quantityValue', 'Data Objects')");

        $this->addSql('UPDATE `users` SET `permissions`=CONCAT(`permissions`, \',quantityValue\') WHERE `permissions` REGEXP \'(?:^|,)classes(?:$|,)\'');

        $this->addSql("INSERT INTO `users_permission_definitions` (`key`, `category`) VALUES ('classificationstore', 'Data Objects')");

        $this->addSql('UPDATE `users` SET `permissions`=CONCAT(`permissions`, \',classificationstore\') WHERE `permissions` REGEXP \'(?:^|,)classes(?:$|,)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE `users` SET `permissions`=REGEXP_REPLACE(`permissions`, \'(?:^|,)objectbricks(?:^|,)\', \'\') WHERE `permissions` REGEXP \'(?:^|,)objectbricks(?:$|,)\'');
        $this->addSql('UPDATE `users` SET `permissions`=REGEXP_REPLACE(`permissions`, \'(?:^|,)fieldcollections(?:^|,)\', \'\') WHERE `permissions` REGEXP \'(?:^|,)fieldcollections(?:$|,)\'');
        $this->addSql('UPDATE `users` SET `permissions`=REGEXP_REPLACE(`permissions`, \'(?:^|,)quantityValue(?:^|,)\', \'\') WHERE `permissions` REGEXP \'(?:^|,)quantityValue(?:$|,)\'');
        $this->addSql('UPDATE `users` SET `permissions`=REGEXP_REPLACE(`permissions`, \'(?:^|,)classificationstore(?:^|,)\', \'\') WHERE `permissions` REGEXP \'(?:^|,)classificationstore(?:$|,)\'');

        $this->addSql("DELETE FROM `users_permission_definitions` WHERE `key` = 'objectbricks'");
        $this->addSql("DELETE FROM `users_permission_definitions` WHERE `key` = 'fieldcollections'");
        $this->addSql("DELETE FROM `users_permission_definitions` WHERE `key` = 'quantityValue'");
        $this->addSql("DELETE FROM `users_permission_definitions` WHERE `key` = 'classificationstore'");
    }
}
