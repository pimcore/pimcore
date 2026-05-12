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

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use JsonException;

final class Version20250312132759 extends AbstractMigration
{
    private const string ASSET_TABLE = 'assets';

    private const string SETTINGS_COLUMN = 'customSettings';

    private const string ID_COLUMN = 'id';

    public function getDescription(): string
    {
        return 'Migrate customSettings asset column from php serialized to json';
    }

    /**
     *
     * When migrating from serialized to json, we need to convert the data in the columns first.
     * Afterward, we need to change the column type.
     *
     * @throws JsonException
     * @throws Exception
     */
    public function up(Schema $schema): void
    {
        $this->migrateAssets();
        $this->alterColumn();
    }

    /**
     *
     *  When migrating from json to serialized, we need to change the column type first,
     *  to get rid of the json_valid check.
     *  Afterward, we need to convert the data in the columns.
     *
     * @throws JsonException
     * @throws Exception
     */
    public function down(Schema $schema): void
    {
        $this->alterColumn(false);
        $this->migrateAssets(false);
    }

    /**
     * @throws Exception
     * @throws JsonException
     */
    private function migrateAssets(bool $up = true): void
    {
        $sql = sprintf(
            'SELECT %s, %s FROM %s',
            $this->connection->quoteIdentifier(self::ID_COLUMN),
            $this->connection->quoteIdentifier(self::SETTINGS_COLUMN),
            $this->connection->quoteIdentifier(self::ASSET_TABLE)
        );

        $assets = $this->connection->iterateAssociative($sql);

        foreach ($assets as $asset) {
            $this->migrateAsset($asset, $up);
        }
    }

    /**
     * @throws Exception
     * @throws JsonException
     */
    private function migrateAsset(array $assetData, bool $up = true): void
    {
        $value = $assetData[self::SETTINGS_COLUMN] ?? null;

        if (
            !is_string($value) ||
            $value === '' ||
            !$this->isTargetColumn($value, $up)
        ) {
            return;
        }

        $converted = $up
            ? json_encode(
                unserialize($value, ['allowed_classes' => false]),
                JSON_THROW_ON_ERROR
            )
            : serialize(
                json_decode($value, true, 512, JSON_THROW_ON_ERROR)
            );

        $this->connection->executeStatement(
            sprintf(
                'UPDATE %s SET %s = ? WHERE %s = ?',
                $this->connection->quoteIdentifier(self::ASSET_TABLE),
                $this->connection->quoteIdentifier(self::SETTINGS_COLUMN),
                $this->connection->quoteIdentifier(self::ID_COLUMN)
            ),
            [
                $converted,
                $assetData[self::ID_COLUMN],
            ]
        );
    }

    private function isTargetColumn(string $value, bool $up): bool
    {
        $value = trim($value);

        if ($up) {
            if (preg_match('/^a:\d+:\{.*\}$/s', $value) !== 1) {
                return false;
            }

            return is_array(@unserialize($value, ['allowed_classes' => false]));
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded);
    }

    private function alterColumn(bool $up = true): void
    {
        $this->addSql(
            sprintf(
                'ALTER TABLE %s MODIFY COLUMN %s %s',
                $this->connection->quoteIdentifier(self::ASSET_TABLE),
                $this->connection->quoteIdentifier(self::SETTINGS_COLUMN),
                $up ? 'json' : 'longtext'
            )
        );
    }
}
