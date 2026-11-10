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

namespace Pimcore\Bundle\CoreBundle\DataObject\Classificationstore;

use Doctrine\DBAL\Connection;
use stdClass;

/**
 * Rewrites the `~classificationstore~<field>~<groupId>-<keyId>` tokens of the saved grid configurations to
 * `~classificationstore~<field>~<groupIdentifier>~<keyIdentifier>`.
 *
 * @internal
 */
final class GridConfigReferenceTranslator implements ReferenceTranslatorInterface
{
    private const PREFIX = '~classificationstore~';

    private const REASON_NOT_NUMERIC = 'the group and key are not both numeric ids';

    private const REASON_NOT_FOUND = 'the group or the key does not exist';

    private const REASON_NO_IDENTIFIER = 'the group or the key has no identifier';

    private const REASON_OTHER_STORE = 'the group and the key belong to different stores';

    private const REASON_CHANGED = 'the row was changed while the command was running';

    /**
     * @var array<string, array<int, array{identifier: ?string, storeId: int}|null>>
     */
    private array $rows = [];

    public function __construct(private readonly Connection $db)
    {
    }

    public function getName(): string
    {
        return 'Grid configurations';
    }

    public function translate(bool $dryRun): TranslationReport
    {
        $translated = 0;
        $unresolved = [];

        foreach ($this->db->iterateAssociative('SELECT `id`, `config`, `modificationDate` FROM `gridconfigs`') as $row) {
            // whatever the column decodes to is walked: a node that is neither a string, an array nor
            // an object is handed back as it is, so a row carrying no token translates nothing
            $config = $row['config'] === null ? null : json_decode((string) $row['config']);

            $location = 'gridconfigs row ' . $row['id'];
            $rowTranslated = 0;
            $rowUnresolved = [];
            $rewritten = $this->walk($config, $location, $rowTranslated, $rowUnresolved);

            $unresolved = array_merge($unresolved, $rowUnresolved);

            if ($rowTranslated === 0) {
                continue;
            }

            if (!$dryRun && !$this->write((int) $row['id'], $rewritten, $row['modificationDate'])) {
                $unresolved[] = new UnresolvedReference($location, '', self::REASON_CHANGED);

                continue;
            }

            $translated += $rowTranslated;
        }

        return new TranslationReport($translated, $unresolved);
    }

    /**
     * @param list<UnresolvedReference> $unresolved
     */
    private function walk(mixed $node, string $location, int &$translated, array &$unresolved): mixed
    {
        if (is_string($node)) {
            return $this->translateToken($node, $location, $translated, $unresolved);
        }

        if (is_array($node)) {
            foreach ($node as $index => $child) {
                $node[$index] = $this->walk($child, $location, $translated, $unresolved);
            }
        } elseif ($node instanceof stdClass) {
            foreach (get_object_vars($node) as $property => $child) {
                $node->$property = $this->walk($child, $location, $translated, $unresolved);
            }
        }

        return $node;
    }

    /**
     * @param list<UnresolvedReference> $unresolved
     */
    private function translateToken(string $value, string $location, int &$translated, array &$unresolved): string
    {
        if (!str_starts_with($value, self::PREFIX)) {
            return $value;
        }

        $parts = explode('~', $value);
        if (count($parts) !== 4) {
            // the identifier form has five segments and is final; anything else is not a token of this command
            return $value;
        }

        $reason = $this->resolveReason($parts[3], $groupIdentifier, $keyIdentifier);
        if ($reason !== null) {
            $unresolved[] = new UnresolvedReference($location, $value, $reason);

            return $value;
        }

        $translated++;

        return self::PREFIX . $parts[2] . '~' . $groupIdentifier . '~' . $keyIdentifier;
    }

    private function resolveReason(string $groupKey, ?string &$groupIdentifier, ?string &$keyIdentifier): ?string
    {
        if (preg_match('/\A(\d+)-(\d+)\z/', $groupKey, $matches) !== 1) {
            return self::REASON_NOT_NUMERIC;
        }

        $group = $this->load('classificationstore_groups', (int) $matches[1]);
        $key = $this->load('classificationstore_keys', (int) $matches[2]);

        if ($group === null || $key === null) {
            return self::REASON_NOT_FOUND;
        }

        if ($group['identifier'] === null || $key['identifier'] === null) {
            return self::REASON_NO_IDENTIFIER;
        }

        if ($group['storeId'] !== $key['storeId']) {
            return self::REASON_OTHER_STORE;
        }

        $groupIdentifier = $group['identifier'];
        $keyIdentifier = $key['identifier'];

        return null;
    }

    /**
     * @return array{identifier: ?string, storeId: int}|null
     */
    private function load(string $table, int $id): ?array
    {
        if (!array_key_exists($id, $this->rows[$table] ?? [])) {
            $row = $this->db->fetchAssociative(
                'SELECT `identifier`, `storeId` FROM `' . $table . '` WHERE `id` = ?',
                [$id]
            );

            $this->rows[$table][$id] = $row === false
                ? null
                : ['identifier' => $row['identifier'] === null ? null : (string) $row['identifier'], 'storeId' => (int) $row['storeId']];
        }

        return $this->rows[$table][$id];
    }

    /**
     * Only the `config` column is written, and only while the row still carries the modification date it was read with.
     */
    private function write(int $id, mixed $config, mixed $modificationDate): bool
    {
        $encoded = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);

        return $this->db->executeStatement(
            'UPDATE `gridconfigs` SET `config` = ? WHERE `id` = ? AND `modificationDate` <=> ?',
            [$encoded, $id, $modificationDate]
        ) > 0;
    }
}
