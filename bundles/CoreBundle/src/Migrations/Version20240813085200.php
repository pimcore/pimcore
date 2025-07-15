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
use Pimcore\Model\Notification\Dao;

final class Version20240813085200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Extending notifications with payload and isStudio columns';
    }

    public function up(Schema $schema): void
    {
        $tableName = Dao::DB_TABLE_NAME;
        if ($schema->hasTable($tableName)) {
            $notificationTable = $schema->getTable($tableName);
            if (!$notificationTable->hasColumn('payload')) {
                $notificationTable->addColumn(
                    'payload',
                    'text',
                    [
                        'default' => null,
                        'columnDefinition' => 'LONGTEXT',
                    ]
                );
            }
            // TODO: New migration will be needed with removal of Classic-UI
            if (!$notificationTable->hasColumn('isStudio')) {
                $notificationTable->addColumn(
                    'isStudio',
                    'integer',
                    [
                        'columnDefinition' => 'TINYINT DEFAULT 0',
                    ]
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $tableName = Dao::DB_TABLE_NAME;
        if ($schema->hasTable($tableName)) {
            $notificationTable = $schema->getTable($tableName);
            if ($notificationTable->hasColumn('payload')) {
                $notificationTable->dropColumn('payload');
            }

            if ($notificationTable->hasColumn('isStudio')) {
                $notificationTable->dropColumn('isStudio');
            }
        }
    }
}
