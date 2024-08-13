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

final class Version20240813085200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Extending notifications with payload and isStudio columns';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('notifications')) {
            $notificationTable = $schema->getTable('notifications');
            if (!$notificationTable->hasColumn('payload')) {
                $this->addSql('ALTER TABLE `notifications` ADD `payload` LONGTEXT DEFAULT NULL');
            }

            if (!$notificationTable->hasColumn('isStudio')) {
                $this->addSql('ALTER TABLE `notifications` ADD `isStudio` TINYINT(1) DEFAULT 0');
            }
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('notifications')) {
            $notificationTable = $schema->getTable('notifications');
            if ($notificationTable->hasColumn('payload')) {
                $this->addSql('ALTER TABLE `notifications` DROP COLUMN `payload`');
            }

            if ($notificationTable->hasColumn('isStudio')) {
                $this->addSql('ALTER TABLE `notifications` DROP COLUMN `isStudio`');
            }
        }
    }
}
