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

final class Version20220830105212 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT IGNORE INTO users (`parentId`, `name`, `admin`, `active`) VALUES(0, 'system', 1, 1);");
        $this->addSql("UPDATE users set id = 0 where name = 'system' AND `type` = 'user'");
    }

    public function down(Schema $schema): void
    {
        //no need to delete system user entry
    }
}
