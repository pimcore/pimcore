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

final class Version20230517115427 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add "object bricks" permission and remove category, when updating from new v11 installation to v11.0.1.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO `users_permission_definitions` (`key`) VALUES ('objectbricks') ON DUPLICATE KEY UPDATE category = ''");
        $this->addSql("INSERT INTO `users_permission_definitions` (`key`) VALUES ('fieldcollections') ON DUPLICATE KEY UPDATE category = ''");
        $this->addSql("INSERT INTO `users_permission_definitions` (`key`) VALUES ('quantityValueUnits') ON DUPLICATE KEY UPDATE category = ''");
        $this->addSql("INSERT INTO `users_permission_definitions` (`key`) VALUES ('classificationstore') ON DUPLICATE KEY UPDATE category = ''");
    }

    public function down(Schema $schema): void
    {

    }
}
