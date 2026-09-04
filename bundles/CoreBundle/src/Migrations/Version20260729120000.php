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

use function sprintf;

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
 * `assets`.`filename`/`path`, `documents`.`key`/`path` and `objects`.`key`/`path` are the
 * exception: their composite `fullpath` unique key (path+filename or path+key, 765+255 chars)
 * already uses the full 3072-byte budget at 3 bytes/char (765*3 + 255*3 = 3060) and would
 * overflow it at 4 bytes/char (4080 bytes). These move to the explicit `utf8mb3` name instead
 * of the ambiguous `utf8` alias, but note MySQL has deprecated `utf8mb3` itself too (not just
 * `utf8`) — this is a stopgap, not a modern target state. A real fix needs an index/schema
 * redesign (e.g. a generated hash column) and is tracked separately as out of scope for this
 * deprecation-warning cleanup.
 *
 * `objects`.`key`/`path` are included here even though Version20221003115124 (2022) already
 * renamed o_key/o_path to key/path with utf8mb3 explicitly: a fresh install's `install.sql`
 * still declared them as bare `utf8` until this PR, and the installer marks every migration as
 * already executed (`doctrine:migrations:version --all --add`, see
 * InstallBundle\Console\ConsoleCommandRunner::markMigrationsAsDone()) without ever running its
 * SQL. Any install created between that migration's release and this PR therefore never ran
 * the 2022 rename's charset change and still has bare `utf8` here - this ALTER is a no-op for
 * databases that already went through the real upgrade path.
 *
 * Each column is only touched when its current collation and length still match the stock
 * legacy definition below; a project that already widened or otherwise customized one of these
 * columns is left untouched (with a log line) instead of being silently reset. This matters
 * because this application intentionally runs with `sql_mode=''` (see default.yaml): under that
 * setting a `MODIFY`/`CONVERT TO CHARACTER SET` that narrows a column truncates or replaces
 * incompatible data with `?` instead of raising an error, so a blind ALTER against a customized
 * column would corrupt data silently. The legacy-collation check accepts both the literal
 * `utf8_*` spelling (MySQL) and its `utf8mb3_*` equivalent (MariaDB normalizes the deprecated
 * `utf8` alias to `utf8mb3` in its catalogs, so an untouched column is introspected under that
 * name there) - see `matchesLegacyCollation()`.
 */
final class Version20260729120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Modernize deprecated utf8/utf8_bin/utf8_general_ci charset and collation usage across the core schema';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->modifyColumnIfStockLegacy($schema, 'assets', 'filename', 'utf8_bin', 255, "ALTER TABLE `assets` MODIFY `filename` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT '';");
        $this->modifyColumnIfStockLegacy($schema, 'assets', 'path', 'utf8_bin', 765, 'ALTER TABLE `assets` MODIFY `path` varchar(765) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL;');

        if ($schema->hasTable('assets_image_thumbnail_cache')) {
            $this->modifyColumnIfStockLegacy($schema, 'assets_image_thumbnail_cache', 'filename', 'utf8_bin', 190, 'ALTER TABLE `assets_image_thumbnail_cache` MODIFY `filename` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL;');
        }

        $this->modifyColumnIfStockLegacy($schema, 'documents', 'key', 'utf8_bin', 255, "ALTER TABLE `documents` MODIFY `key` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT '';");
        $this->modifyColumnIfStockLegacy($schema, 'documents', 'path', 'utf8_bin', 765, 'ALTER TABLE `documents` MODIFY `path` varchar(765) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL;');

        $this->modifyColumnIfStockLegacy($schema, 'objects', 'key', 'utf8_bin', 255, "ALTER TABLE `objects` MODIFY `key` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT '';");
        $this->modifyColumnIfStockLegacy($schema, 'objects', 'path', 'utf8_bin', 765, 'ALTER TABLE `objects` MODIFY `path` varchar(765) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL;');

        $this->convertLockKeysCharsetIfStockLegacy($schema);

        $this->modifyColumnIfStockLegacy($schema, 'properties', 'cpath', 'utf8_general_ci', 765, 'ALTER TABLE `properties` MODIFY `cpath` varchar(765) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;');

        $this->modifyColumnIfStockLegacy($schema, 'tags', 'name', 'utf8_bin', 255, 'ALTER TABLE `tags` MODIFY `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL;');

        $this->modifyColumnIfStockLegacy($schema, 'users_workspaces_asset', 'cpath', 'utf8_bin', 765, 'ALTER TABLE `users_workspaces_asset` MODIFY `cpath` varchar(765) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL;');
        $this->modifyColumnIfStockLegacy($schema, 'users_workspaces_document', 'cpath', 'utf8_bin', 765, 'ALTER TABLE `users_workspaces_document` MODIFY `cpath` varchar(765) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL;');
        $this->modifyColumnIfStockLegacy($schema, 'users_workspaces_object', 'cpath', 'utf8_bin', 765, 'ALTER TABLE `users_workspaces_object` MODIFY `cpath` varchar(765) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL;');

        if ($schema->hasTable('search_backend_data') && $schema->getTable('search_backend_data')->hasColumn('key')) {
            $this->modifyColumnIfStockLegacy($schema, 'search_backend_data', 'key', 'utf8_bin', 255, "ALTER TABLE `search_backend_data` MODIFY `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '';");
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'This migration cannot be safely reverted: converting `utf8mb4` columns (e.g. `tags`.`name`, the `cpath` '
            . 'columns) back to `utf8`/`utf8mb3` can silently replace stored 4-byte characters (e.g. emoji) with "?" '
            . "instead of raising an error, because this application intentionally runs with sql_mode='' (see "
            . 'default.yaml). Restore the affected columns from a backup if you need to go back.',
        );
    }

    /**
     * Only runs the ALTER when the column's current collation and length still match the stock
     * legacy definition; otherwise the column looks customized (e.g. a project intentionally
     * widened it) and is left untouched, with a log line, rather than silently truncated.
     */
    private function modifyColumnIfStockLegacy(
        Schema $schema,
        string $table,
        string $column,
        string $expectedLegacyCollation,
        int $expectedLegacyLength,
        string $alterSql,
    ): void {
        if (!$schema->hasTable($table)) {
            return;
        }

        $tableSchema = $schema->getTable($table);

        if (!$tableSchema->hasColumn($column)) {
            return;
        }

        $currentColumn = $tableSchema->getColumn($column);

        if (!$this->matchesLegacyCollation($currentColumn->getCollation(), $expectedLegacyCollation) || $currentColumn->getLength() !== $expectedLegacyLength) {
            $this->write(sprintf(
                'Skipping charset modernization of `%s`.`%s`: current definition (collation=%s, length=%s) no longer '
                . 'matches the stock legacy definition (collation=%s, length=%d), so it looks customized. Leaving it '
                . 'untouched - modernize it manually if desired.',
                $table,
                $column,
                $currentColumn->getCollation() ?? 'null',
                $currentColumn->getLength() ?? 'null',
                $expectedLegacyCollation,
                $expectedLegacyLength,
            ));

            return;
        }

        $this->addSql($alterSql);
    }

    /**
     * `lock_keys` is converted as a whole table rather than column by column, so both of its
     * varchar columns must still match their stock legacy definition before the CONVERT runs.
     */
    private function convertLockKeysCharsetIfStockLegacy(Schema $schema): void
    {
        if (!$schema->hasTable('lock_keys')) {
            return;
        }

        $table = $schema->getTable('lock_keys');

        foreach (['key_id' => 64, 'key_token' => 44] as $column => $expectedLegacyLength) {
            if (!$table->hasColumn($column)) {
                return;
            }

            $currentColumn = $table->getColumn($column);

            if (!$this->matchesLegacyCollation($currentColumn->getCollation(), 'utf8_general_ci') || $currentColumn->getLength() !== $expectedLegacyLength) {
                $this->write(sprintf(
                    'Skipping charset modernization of `lock_keys`: `%s` (collation=%s, length=%s) no longer matches '
                    . 'its stock legacy definition, so the table looks customized. Leaving it untouched - modernize '
                    . 'it manually if desired.',
                    $column,
                    $currentColumn->getCollation() ?? 'null',
                    $currentColumn->getLength() ?? 'null',
                ));

                return;
            }
        }

        $this->addSql('ALTER TABLE `lock_keys` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;');
    }

    /**
     * MariaDB normalizes the deprecated `utf8` alias to `utf8mb3` in its catalogs, so a column
     * declared as e.g. `utf8_bin` in install.sql is introspected as `utf8mb3_bin` on MariaDB even
     * though it was never touched - only MySQL reports the legacy `utf8_*` spelling verbatim.
     * Accept either spelling as "still the stock legacy definition".
     */
    private function matchesLegacyCollation(?string $currentCollation, string $expectedLegacyCollation): bool
    {
        return $currentCollation === $expectedLegacyCollation
            || $currentCollation === str_replace('utf8_', 'utf8mb3_', $expectedLegacyCollation);
    }
}
