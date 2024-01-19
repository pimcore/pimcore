<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230913173812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds passwordRecoveryToken column to users table';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('users')->hasColumn('passwordRecoveryToken')) {
            $this->addSql(
                'ALTER TABLE `users` ADD COLUMN `passwordRecoveryToken` varchar(255) DEFAULT NULL AFTER `password`;'
            );
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('users')->hasColumn('passwordRecoveryToken')) {
            $this->addSql('ALTER TABLE `users` DROP COLUMN `passwordRecoveryToken`;');
        }
    }
}
