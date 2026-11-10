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
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreField;
use Pimcore\Model\DataObject\Classificationstore\Service as ClassificationstoreService;
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

    private const REASON_NO_FIELD_STORE = 'the store of the classification store field does not resolve';

    private const REASON_NOT_FOUND = 'the group or the key does not exist';

    private const REASON_NO_IDENTIFIER = 'the group or the key has no identifier';

    private const REASON_OTHER_STORE = 'the group or the key is not in the store of the field';

    private const REASON_CHANGED = 'the row was changed while the command was running';

    /**
     * @var array<string, array<int, array{identifier: ?string, storeId: int}|null>>
     */
    private array $rows = [];

    /**
     * @var array<string, int|null> store id by class id and field name
     */
    private array $fieldStores = [];

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

        foreach ($this->db->iterateAssociative('SELECT `id`, `classId`, `config` FROM `gridconfigs`') as $row) {
            // whatever the column decodes to is walked: a node that is neither a string, an array nor
            // an object is handed back as it is, so a row carrying no token translates nothing
            $config = $row['config'] === null ? null : json_decode((string) $row['config']);

            $location = 'gridconfigs row ' . $row['id'];
            $classId = $row['classId'] === null ? null : (string) $row['classId'];
            $rowTokens = [];
            $rowUnresolved = [];
            $rewritten = $this->walk($config, $location, $classId, $rowTokens, $rowUnresolved);

            $unresolved = array_merge($unresolved, $rowUnresolved);

            if ($rowTokens === []) {
                continue;
            }

            if (!$dryRun && !$this->write((int) $row['id'], $rewritten, (string) $row['config'])) {
                // the row was not written, so every token it carried is still an integer pair: each one
                // is reported as itself, rather than the row being reported once as a nameless reference
                foreach ($rowTokens as $token) {
                    $unresolved[] = new UnresolvedReference($location, $token, self::REASON_CHANGED);
                }

                continue;
            }

            $translated += count($rowTokens);
        }

        return new TranslationReport($translated, $unresolved);
    }

    /**
     * @param list<string> $tokens the tokens that were rewritten, in the order they were met
     * @param list<UnresolvedReference> $unresolved
     */
    private function walk(
        mixed $node,
        string $location,
        ?string $classId,
        array &$tokens,
        array &$unresolved
    ): mixed {
        if (is_string($node)) {
            return $this->translateToken($node, $location, $classId, $tokens, $unresolved);
        }

        if (is_array($node)) {
            foreach ($node as $index => $child) {
                $node[$index] = $this->walk($child, $location, $classId, $tokens, $unresolved);
            }
        } elseif ($node instanceof stdClass) {
            foreach (get_object_vars($node) as $property => $child) {
                $node->$property = $this->walk($child, $location, $classId, $tokens, $unresolved);
            }
        }

        return $node;
    }

    /**
     * @param list<string> $tokens
     * @param list<UnresolvedReference> $unresolved
     */
    private function translateToken(
        string $value,
        string $location,
        ?string $classId,
        array &$tokens,
        array &$unresolved
    ): string {
        if (!str_starts_with($value, self::PREFIX)) {
            return $value;
        }

        $parts = explode('~', $value);
        if (count($parts) !== 4) {
            // the identifier form has five segments and is final; anything else is not a token of this command
            return $value;
        }

        $reason = $this->resolveReason(
            $parts[3],
            $this->resolveFieldStoreId($classId, $parts[2]),
            $groupIdentifier,
            $keyIdentifier
        );
        if ($reason !== null) {
            $unresolved[] = new UnresolvedReference($location, $value, $reason);

            return $value;
        }

        $tokens[] = $value;

        return self::PREFIX . $parts[2] . '~' . $groupIdentifier . '~' . $keyIdentifier;
    }

    /**
     * Both readers of the rewritten token resolve the two identifiers inside the store of the
     * classification store field the token names ({@see \Pimcore\Model\DataObject\Service::getStoreValueForObject()}
     * and {@see \Pimcore\Model\Helper\QueryParams::extractSortingSettings()}), and an identifier is unique
     * per store only. So a token whose ids belong to another store must not be rewritten: the integer form
     * reads nothing there, while the identifier form would resolve against whatever group and key of the
     * field's own store happen to carry the same identifiers.
     */
    private function resolveReason(
        string $groupKey,
        ?int $fieldStoreId,
        ?string &$groupIdentifier,
        ?string &$keyIdentifier
    ): ?string {
        if (preg_match('/\A(\d+)-(\d+)\z/', $groupKey, $matches) !== 1) {
            return self::REASON_NOT_NUMERIC;
        }

        if ($fieldStoreId === null) {
            return self::REASON_NO_FIELD_STORE;
        }

        $group = $this->load('classificationstore_groups', (int) $matches[1]);
        $key = $this->load('classificationstore_keys', (int) $matches[2]);

        if ($group === null || $key === null) {
            return self::REASON_NOT_FOUND;
        }

        if ($group['identifier'] === null || $key['identifier'] === null) {
            return self::REASON_NO_IDENTIFIER;
        }

        if ($group['storeId'] !== $fieldStoreId || $key['storeId'] !== $fieldStoreId) {
            return self::REASON_OTHER_STORE;
        }

        $groupIdentifier = $group['identifier'];
        $keyIdentifier = $key['identifier'];

        return null;
    }

    /**
     * The store of the classification store field the token names, resolved the way the readers resolve it:
     * through the class the row carries, and null when that class, that field or its store id is not there.
     */
    private function resolveFieldStoreId(?string $classId, string $fieldName): ?int
    {
        if ($classId === null || $classId === '') {
            return null;
        }

        $memoKey = $classId . '~' . $fieldName;
        if (!array_key_exists($memoKey, $this->fieldStores)) {
            $fieldDefinition = ClassDefinition::getById($classId)?->getFieldDefinition($fieldName);

            $this->fieldStores[$memoKey] = $fieldDefinition instanceof ClassificationstoreField
                ? ClassificationstoreService::getConfiguredStoreId($fieldDefinition)
                : null;
        }

        return $this->fieldStores[$memoKey];
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
                : [
                    'identifier' => $row['identifier'] === null ? null : (string) $row['identifier'],
                    'storeId' => (int) $row['storeId'],
                ];
        }

        return $this->rows[$table][$id];
    }

    /**
     * Only the `config` column is written, and only while the row still carries the configuration it was
     * read with. The guard is the value that was read rather than `modificationDate`, because no code in
     * this repository writes that column: whether it is bumped on every save is a property of whichever
     * bundle owns the editor, while the configuration itself is what this command decided against.
     */
    private function write(int $id, mixed $config, string $readConfig): bool
    {
        $encoded = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);

        return $this->db->executeStatement(
            'UPDATE `gridconfigs` SET `config` = ? WHERE `id` = ? AND `config` = ?',
            [$encoded, $id, $readConfig]
        ) > 0;
    }
}
