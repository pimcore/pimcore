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
 * Widen the `ownername` column from VARCHAR(70) to VARCHAR(190) in all relation-like
 * tables. The generated ownername for a localized field nested inside an object brick
 * or field collection (e.g. `/objectbrick~<field>/<brickKey>/localizedfield~localizedfield`)
 * can exceed 70 characters, causing "Data too long for column 'ownername'" on save
 * under strict SQL mode (see PEES-1253).
 */
final class Version20260721000000 extends AbstractMigration
{
    private const NEW_LENGTH = 190;

    private const OLD_LENGTH = 70;

    public function getDescription(): string
    {
        return sprintf(
            'Increase `ownername` length from %d to %d in object_relations_*, object_metadata_* and object_url_slugs tables.',
            self::OLD_LENGTH,
            self::NEW_LENGTH
        );
    }

    public function up(Schema $schema): void
    {
        foreach ($this->getPerClassTables() as $tableName) {
            $this->addSql(sprintf(
                "ALTER TABLE `%s` MODIFY `ownername` VARCHAR(%d) NOT NULL DEFAULT '';",
                $tableName,
                self::NEW_LENGTH
            ));
        }

        $this->addSql(sprintf(
            "ALTER TABLE `object_url_slugs` MODIFY `ownername` VARCHAR(%d) NOT NULL DEFAULT '';",
            self::NEW_LENGTH
        ));
    }

    public function down(Schema $schema): void
    {
        // Note: reverting to the shorter length can fail under strict SQL mode if any
        // ownername value longer than the old length has been persisted in the meantime.
        foreach ($this->getPerClassTables() as $tableName) {
            $this->addSql(sprintf(
                "ALTER TABLE `%s` MODIFY `ownername` VARCHAR(%d) NOT NULL DEFAULT '';",
                $tableName,
                self::OLD_LENGTH
            ));
        }

        $this->addSql(sprintf(
            "ALTER TABLE `object_url_slugs` MODIFY `ownername` VARCHAR(%d) NOT NULL DEFAULT '';",
            self::OLD_LENGTH
        ));
    }

    /**
     * @return string[] names of all per-class object_relations_* and object_metadata_* tables
     */
    private function getPerClassTables(): array
    {
        return array_values(array_filter(array_merge(
            $this->connection->fetchFirstColumn("SHOW TABLES LIKE 'object_relations_%'"),
            $this->connection->fetchFirstColumn("SHOW TABLES LIKE 'object_metadata_%'")
        ), static fn (string $tableName): bool => preg_match('/^object_(?:relations|metadata)_\d+$/D', $tableName) === 1));
    }
}
