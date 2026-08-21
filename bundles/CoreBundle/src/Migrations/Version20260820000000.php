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

final class Version20260820000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add asset_storage_operation_queue table (deferred physical storage operations for asset moves/deletes)';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('asset_storage_operation_queue')) {
            return;
        }

        $this->addSql(
            'CREATE TABLE `asset_storage_operation_queue` (
                `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
                `storage` VARCHAR(50) NOT NULL,
                `operation` ENUM(\'move\',\'delete\') NOT NULL,
                `source_prefix` VARCHAR(765) NOT NULL,
                `target_prefix` VARCHAR(765) DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`)
            ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;'
        );
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('asset_storage_operation_queue')) {
            return;
        }

        $this->addSql('DROP TABLE `asset_storage_operation_queue`;');
    }
}
