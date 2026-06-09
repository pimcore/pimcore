<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221116115427 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add "object bricks" permission';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO `users_permission_definitions` (`key`, `category`) VALUES ('objectbricks', 'Data Objects')");

        $this->addSql("INSERT INTO `users_permission_definitions` (`key`, `category`) VALUES ('fieldcollections', 'Data Objects')");

        $this->addSql("INSERT INTO `users_permission_definitions` (`key`, `category`) VALUES ('quantityValueUnits', 'Data Objects')");

        $this->addSql("INSERT INTO `users_permission_definitions` (`key`, `category`) VALUES ('classificationstore', 'Data Objects')");

        $this->addSql('UPDATE `users` SET `permissions`=CONCAT(`permissions`, \',objectbricks,fieldcollections,quantityValueUnits,classificationstore\') WHERE `permissions` REGEXP \'(?:^|,)classes(?:$|,)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE `users` SET `permissions`=REGEXP_REPLACE(`permissions`, \'(?:^|,)objectbricks(?:$|,)\', \'\') WHERE `permissions` REGEXP \'(?:^|,)objectbricks(?:$|,)\'');
        $this->addSql('UPDATE `users` SET `permissions`=REGEXP_REPLACE(`permissions`, \'(?:^|,)fieldcollections(?:$|,)\', \'\') WHERE `permissions` REGEXP \'(?:^|,)fieldcollections(?:$|,)\'');
        $this->addSql('UPDATE `users` SET `permissions`=REGEXP_REPLACE(`permissions`, \'(?:^|,)quantityValueUnits(?:$|,)\', \'\') WHERE `permissions` REGEXP \'(?:^|,)quantityValueUnits(?:$|,)\'');
        $this->addSql('UPDATE `users` SET `permissions`=REGEXP_REPLACE(`permissions`, \'(?:^|,)classificationstore(?:$|,)\', \'\') WHERE `permissions` REGEXP \'(?:^|,)classificationstore(?:$|,)\'');

        $this->addSql("DELETE FROM `users_permission_definitions` WHERE `key` = 'objectbricks'");
        $this->addSql("DELETE FROM `users_permission_definitions` WHERE `key` = 'fieldcollections'");
        $this->addSql("DELETE FROM `users_permission_definitions` WHERE `key` = 'quantityValueUnits'");
        $this->addSql("DELETE FROM `users_permission_definitions` WHERE `key` = 'classificationstore'");
    }
}
