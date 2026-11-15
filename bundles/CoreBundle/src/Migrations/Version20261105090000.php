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

use function array_map;
use function implode;
use function in_array;
use function preg_match;
use function preg_match_all;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function strtolower;
use function strtoupper;
use function substr;

/**
 * Adds the `serviceuser` member to the `users`.`type` enum, so service users
 * (\Pimcore\Model\User objects with isServiceUser()) can be stored next to users, roles and their folders.
 *
 * The member is appended to the column's current definition instead of replacing it with a hardcoded
 * list, so members a project added itself survive; appending at the end is a metadata-only change.
 * The ALTER re-states the column's nullability, default, character set, collation and comment, so
 * MODIFY does not reset them. The generated `uniqueName` column and the unique key
 * (`type`, `uniqueName`) stay as they are: a service user's `uniqueName` is its plain name.
 */
final class Version20261105090000 extends AbstractMigration
{
    private const MEMBER = 'serviceuser';

    private const IDENTIFIER = '/^[A-Za-z0-9_]+$/';

    public function getDescription(): string
    {
        return "Add the 'serviceuser' member to the users.type enum";
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $column = $this->getTypeColumn();
        if ($column === null || in_array(self::MEMBER, $column['members'], true)) {
            return;
        }

        $this->alterType(substr($column['type'], 0, -1) . ",'" . self::MEMBER . "')", $column);
    }

    public function down(Schema $schema): void
    {
        $column = $this->getTypeColumn();
        if ($column === null || !in_array(self::MEMBER, $column['members'], true)) {
            return;
        }

        $serviceUsers = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM `users` WHERE `type` = ?',
            [self::MEMBER]
        );
        $this->abortIf(
            $serviceUsers > 0,
            "Cannot remove the 'serviceuser' member from users.type: {$serviceUsers} service user(s) exist."
            . ' Delete them, then retry.'
        );

        $remaining = [];
        foreach ($column['members'] as $index => $member) {
            if ($member !== self::MEMBER) {
                $remaining[] = $column['tokens'][$index];
            }
        }

        $this->abortIf(
            $remaining === [],
            "Cannot remove the 'serviceuser' member from users.type: it is the only member of the column."
        );

        $this->alterType('enum(' . implode(',', $remaining) . ')', $column);
    }

    /**
     * @param array{nullable: bool, default: string|null, charset: string|null, collation: string|null,
     *     comment: string} $column
     */
    private function alterType(string $enumType, array $column): void
    {
        $sql = 'ALTER TABLE `users` MODIFY `type` ' . $enumType;

        // names come from information_schema; only plain identifiers are ever interpolated
        if ($column['charset'] !== null && $column['collation'] !== null
            && preg_match(self::IDENTIFIER, $column['charset']) === 1
            && preg_match(self::IDENTIFIER, $column['collation']) === 1) {
            $sql .= ' CHARACTER SET ' . $column['charset'] . ' COLLATE ' . $column['collation'];
        }

        $sql .= $column['nullable'] ? ' NULL' : ' NOT NULL';

        if ($column['default'] !== null) {
            $sql .= ' DEFAULT ' . $this->connection->quote($column['default']);
        } elseif ($column['nullable']) {
            $sql .= ' DEFAULT NULL';
        }

        if ($column['comment'] !== '') {
            $sql .= ' COMMENT ' . $this->connection->quote($column['comment']);
        }

        $this->addSql($sql);
    }

    /**
     * @return array{type: string, tokens: string[], members: string[], nullable: bool, default: string|null,
     *     charset: string|null, collation: string|null, comment: string}|null
     *     null when the column is missing or not an enum. `tokens` are the quoted members as reported by the
     *     database, `members` the same members unquoted, `default` the unquoted default value.
     */
    private function getTypeColumn(): ?array
    {
        $column = $this->connection->fetchAssociative(
            'SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, CHARACTER_SET_NAME, COLLATION_NAME, COLUMN_COMMENT'
            . ' FROM information_schema.COLUMNS'
            . " WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'type'"
        );

        if ($column === false) {
            return null;
        }

        $type = (string) $column['COLUMN_TYPE'];
        if (!str_starts_with(strtolower($type), 'enum(') || !str_ends_with($type, ')')) {
            return null;
        }

        // members are single-quoted, a quote inside a member is doubled
        preg_match_all("/'((?:[^']|'')*)'/", substr($type, 5, -1), $matches);

        return [
            'type' => $type,
            'tokens' => $matches[0],
            'members' => array_map(static fn (string $member): string => str_replace("''", "'", $member), $matches[1]),
            'nullable' => $column['IS_NULLABLE'] === 'YES',
            'default' => $this->normalizeDefault($column['COLUMN_DEFAULT'] ?? null),
            'charset' => isset($column['CHARACTER_SET_NAME']) ? (string) $column['CHARACTER_SET_NAME'] : null,
            'collation' => isset($column['COLLATION_NAME']) ? (string) $column['COLLATION_NAME'] : null,
            'comment' => (string) ($column['COLUMN_COMMENT'] ?? ''),
        ];
    }

    /**
     * MariaDB reports a string default quoted ('user') and a missing one as 'NULL'; MySQL reports the
     * bare value and SQL NULL.
     */
    private function normalizeDefault(mixed $default): ?string
    {
        if ($default === null || strtoupper((string) $default) === 'NULL') {
            return null;
        }

        $default = (string) $default;
        if (preg_match("/^'((?:[^']|'')*)'$/", $default, $match) === 1) {
            return str_replace("''", "'", $match[1]);
        }

        return $default;
    }
}
