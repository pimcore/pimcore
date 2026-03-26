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

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230221073317 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add selectoptions user permission';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT IGNORE INTO users_permission_definitions (`key`) VALUES('selectoptions');");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM users_permission_definitions WHERE `key` = 'selectoptions'");
    }
}
