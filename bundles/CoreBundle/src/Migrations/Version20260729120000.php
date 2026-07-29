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
 * Modernizes the deprecated, ambiguous `utf8`/`utf8_bin`/`utf8_general_ci` charset/collation
 * names (aliases for utf8mb3, deprecated since MySQL 8.0.28) left over on databases that were
 * installed or upgraded before install.sql was updated (see internal-improvements#16).
 *
 * Columns that are part of a composite index already sized to the 3072-byte InnoDB
 * index-prefix limit (`fullpath`, `cpath_userId`, `getall`, ...) must stay 3 bytes/char, so
 * they move to the explicit, non-deprecated `utf8mb3` name instead of `utf8mb4` — widening
 * them to 4 bytes/char would overflow that limit. Columns with index headroom move to real
 * `utf8mb4`. `objects`.`key`/`path` are not touched here: Version20221003115124 already
 * moved them to utf8mb3 when renaming o_key/o_path.
 */
final class Version20260729120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Modernize deprecated utf8/utf8_bin/utf8_general_ci charset and collation usage across the core schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `assets` MODIFY `filename` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT '';");
        $this->addSql('ALTER TABLE `assets` MODIFY `path` varchar(765) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL;');

        if ($schema->hasTable('assets_image_thumbnail_cache')) {
            $this->addSql('ALTER TABLE `assets_image_thumbnail_cache` MODIFY `filename` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL;');
        }

        $this->addSql("ALTER TABLE `documents` MODIFY `key` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT '';");
        $this->addSql('ALTER TABLE `documents` MODIFY `path` varchar(765) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL;');

        $this->addSql('ALTER TABLE `lock_keys` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;');

        $this->addSql('ALTER TABLE `properties` MODIFY `cpath` varchar(765) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL;');

        $this->addSql('ALTER TABLE `tags` MODIFY `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL;');

        $this->addSql('ALTER TABLE `users_workspaces_asset` MODIFY `cpath` varchar(765) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL;');
        $this->addSql('ALTER TABLE `users_workspaces_document` MODIFY `cpath` varchar(765) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL;');
        $this->addSql('ALTER TABLE `users_workspaces_object` MODIFY `cpath` varchar(765) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL;');

        if ($schema->hasTable('search_backend_data') && $schema->getTable('search_backend_data')->hasColumn('key')) {
            $this->addSql("ALTER TABLE `search_backend_data` MODIFY `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '';");
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `assets` MODIFY `filename` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '';");
        $this->addSql('ALTER TABLE `assets` MODIFY `path` varchar(765) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL;');

        if ($schema->hasTable('assets_image_thumbnail_cache')) {
            $this->addSql('ALTER TABLE `assets_image_thumbnail_cache` MODIFY `filename` varchar(190) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL;');
        }

        $this->addSql("ALTER TABLE `documents` MODIFY `key` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '';");
        $this->addSql('ALTER TABLE `documents` MODIFY `path` varchar(765) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL;');

        $this->addSql('ALTER TABLE `lock_keys` CONVERT TO CHARACTER SET utf8;');

        $this->addSql('ALTER TABLE `properties` MODIFY `cpath` varchar(765) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL;');

        $this->addSql('ALTER TABLE `tags` MODIFY `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL;');

        $this->addSql('ALTER TABLE `users_workspaces_asset` MODIFY `cpath` varchar(765) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL;');
        $this->addSql('ALTER TABLE `users_workspaces_document` MODIFY `cpath` varchar(765) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL;');
        $this->addSql('ALTER TABLE `users_workspaces_object` MODIFY `cpath` varchar(765) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL;');

        if ($schema->hasTable('search_backend_data') && $schema->getTable('search_backend_data')->hasColumn('key')) {
            $this->addSql("ALTER TABLE `search_backend_data` MODIFY `key` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '';");
        }
    }
}
