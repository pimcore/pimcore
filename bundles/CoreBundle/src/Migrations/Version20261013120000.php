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

use function str_contains;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function strtolower;
use function substr;

/**
 * Adds the `date` member to the `properties`.`type` enum.
 *
 * \Pimcore\Model\Property still reads and writes date properties, but with the member missing the
 * stored type is coerced to '' on save (this application intentionally runs with sql_mode='', see
 * default.yaml; a strict mode would fail the save instead), so the property loses its type and is
 * loaded back as the raw serialized string.
 *
 * The member is appended to the column's current definition instead of replacing it with a
 * hardcoded list, so members a project added itself survive. Appending at the end of the list is
 * a metadata-only change for MariaDB/MySQL (no table rebuild). Rows that were already saved with
 * the coerced '' type are left untouched: their former type cannot be told apart from other
 * values reliably.
 */
final class Version20261013120000 extends AbstractMigration
{
    private const MEMBER = "'date'";

    public function getDescription(): string
    {
        return "Add the 'date' member to the properties.type enum";
    }

    public function up(Schema $schema): void
    {
        $column = $this->getTypeColumn();
        if ($column === null || str_contains($column['type'], self::MEMBER)) {
            return;
        }

        $this->alterType(substr($column['type'], 0, -1) . ',' . self::MEMBER . ')', $column['nullable']);
    }

    public function down(Schema $schema): void
    {
        $column = $this->getTypeColumn();
        if ($column === null || !str_contains($column['type'], self::MEMBER)) {
            return;
        }

        $dateRows = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM `properties` WHERE `type` = ?', ['date']);
        $this->abortIf(
            $dateRows > 0,
            "Cannot remove the 'date' member from properties.type: {$dateRows} property row(s) use it and would be"
            . " silently coerced to '' (sql_mode=''). Delete or convert them, then retry."
        );

        $this->alterType(str_replace([',' . self::MEMBER, self::MEMBER . ','], '', $column['type']), $column['nullable']);
    }

    private function alterType(string $enumType, bool $nullable): void
    {
        $this->addSql('ALTER TABLE `properties` MODIFY `type` ' . $enumType . ($nullable ? ' DEFAULT NULL' : ' NOT NULL'));
    }

    /**
     * @return array{type: string, nullable: bool}|null null when the column is missing or not an enum
     */
    private function getTypeColumn(): ?array
    {
        $column = $this->connection->fetchAssociative(
            'SELECT COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS'
            . " WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'properties' AND COLUMN_NAME = 'type'"
        );

        if ($column === false) {
            return null;
        }

        $type = (string) $column['COLUMN_TYPE'];
        if (!str_starts_with(strtolower($type), 'enum(') || !str_ends_with($type, ')')) {
            return null;
        }

        return ['type' => $type, 'nullable' => $column['IS_NULLABLE'] === 'YES'];
    }
}
