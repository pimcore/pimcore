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
 * names left over on databases that were installed or upgraded before install.sql was updated
 * (see internal-improvements#16).
 *
 * Most affected columns move to real `utf8mb4` (verified against a live MariaDB instance to
 * fit within the 3072-byte InnoDB index-prefix limit for their indexes: `getall`
 * (cpath+ctype+inheritable) and `cpath_userId` / `idx_users_workspaces_list_permission`
 * (cpath+userId[+list]) all have headroom at 4 bytes/char).
 *
 * `assets`.`filename`/`path` and `documents`.`key`/`path` are the exception: their composite
 * `fullpath` unique key (path+filename or path+key, 765+255 chars) already uses the full
 * 3072-byte budget at 3 bytes/char (765*3 + 255*3 = 3060) and would overflow it at 4 bytes/char
 * (4080 bytes). These move to the explicit `utf8mb3` name instead of the ambiguous `utf8` alias,
 * but note MySQL has deprecated `utf8mb3` itself too (not just `utf8`) — this is a stopgap, not
 * a modern target state. A real fix needs an index/schema redesign (e.g. a generated hash
 * column) and is tracked separately as out of scope for this deprecation-warning cleanup.
 * `objects`.`key`/`path` have the same constraint and are not touched here: Version20221003115124
 * already moved them to utf8mb3 when renaming o_key/o_path.
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

        $this->addSql('ALTER TABLE `properties` MODIFY `cpath` varchar(765) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;');

        $this->addSql('ALTER TABLE `tags` MODIFY `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL;');

        $this->addSql('ALTER TABLE `users_workspaces_asset` MODIFY `cpath` varchar(765) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL;');
        $this->addSql('ALTER TABLE `users_workspaces_document` MODIFY `cpath` varchar(765) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL;');
        $this->addSql('ALTER TABLE `users_workspaces_object` MODIFY `cpath` varchar(765) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL;');

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
