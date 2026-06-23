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
final class Version20210330132354 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `users_workspaces_asset` CHANGE `cpath` `cpath` varchar(765) COLLATE 'utf8_bin' NULL AFTER `cid`;");
        $this->addSql("ALTER TABLE `users_workspaces_document` CHANGE `cpath` `cpath` varchar(765) COLLATE 'utf8_bin' NULL AFTER `cid`;");
        $this->addSql("ALTER TABLE `users_workspaces_object` CHANGE `cpath` `cpath` varchar(765) COLLATE 'utf8_bin' NULL AFTER `cid`;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `users_workspaces_asset` CHANGE `cpath` `cpath` varchar(765) COLLATE 'utf8_general_ci' NULL AFTER `cid`;");
        $this->addSql("ALTER TABLE `users_workspaces_document` CHANGE `cpath` `cpath` varchar(765) COLLATE 'utf8_general_ci' NULL AFTER `cid`;");
        $this->addSql("ALTER TABLE `users_workspaces_object` CHANGE `cpath` `cpath` varchar(765) COLLATE 'utf8_general_ci' NULL AFTER `cid`;");
    }
}
