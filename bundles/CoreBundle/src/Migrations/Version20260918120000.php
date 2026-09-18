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
 * The asset storage operation queue is opt-in and its table is created by the operator, as
 * documented in doc/02_Assets/05_Asset_Storage_Operation_Queue.md. Installs that never enabled
 * the feature therefore have no table at all, and this migration has nothing to do for them.
 */
final class Version20260918120000 extends AbstractMigration
{
    private const TABLE = 'asset_storage_operation_queue';

    private const COLUMN = 'copy_options';

    public function getDescription(): string
    {
        return 'Add copy_options column to the asset storage operation queue table';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable(self::TABLE)) {
            $this->write(sprintf(
                'Table "%s" does not exist - the asset storage operation queue is not in use, skipping.',
                self::TABLE
            ));

            return;
        }

        if ($schema->getTable(self::TABLE)->hasColumn(self::COLUMN)) {
            return;
        }

        $this->addSql(sprintf(
            'ALTER TABLE `%s` ADD COLUMN `%s` JSON DEFAULT NULL AFTER `created_at`;',
            self::TABLE,
            self::COLUMN
        ));
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable(self::TABLE) || !$schema->getTable(self::TABLE)->hasColumn(self::COLUMN)) {
            return;
        }

        $this->addSql(sprintf('ALTER TABLE `%s` DROP COLUMN `%s`;', self::TABLE, self::COLUMN));
    }
}
