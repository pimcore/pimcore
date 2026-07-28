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

namespace Pimcore\Maintenance\Tasks\DataObject;

use Doctrine\DBAL\Connection;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Objectbrick;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class DataObjectTaskHelper implements DataObjectTaskHelperInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private Connection $db
    ) {
    }

    public function getObjectBrickCollectionNames(): array
    {
        return $this->mapCollectionNames((new Objectbrick\Definition\Listing())->loadNames());
    }

    public function getFieldcollectionCollectionNames(): array
    {
        return $this->mapCollectionNames((new Fieldcollection\Definition\Listing())->loadNames());
    }

    /**
     * @param string[] $names
     *
     * @return array<string, string>
     */
    private function mapCollectionNames(array $names): array
    {
        $mapLowerToActual = [];
        foreach ($names as $name) {
            $mapLowerToActual[strtolower($name)] = $name;
        }

        return $mapLowerToActual;
    }

    /**
     * @param array<string, string> $collectionNames lowercased => actual collection key
     *
     * @return string[]
     */
    public function matchCollectionKeys(string $tableIdentifier, array $collectionNames): array
    {
        $matches = [];
        foreach ($collectionNames as $key) {
            // Case-insensitive, underscore-delimited prefix match.
            if (stripos($tableIdentifier, $key . '_') === 0) {
                $matches[] = $key;
            }
        }

        // Because both collection keys and class ids may contain underscores, several keys can
        // claim the same identifier (e.g. "Foo" and "Foo_Bar" both match "Foo_Bar_5"). Longest
        // (most specific) key first: its class-id split is the most likely correct one, and the
        // caller probes the candidates in order until one resolves to a live class.
        usort($matches, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        return $matches;
    }

    public function dropOrphanedTable(string $tableName): void
    {
        $this->logger->warning('Dropping orphaned data object table ' . $tableName);
        $this->db->executeStatement('DROP TABLE IF EXISTS ' . $this->db->quoteIdentifier($tableName));
    }

    public function cleanupTable(
        string $tableName,
        string $classId,
        bool $isLocalized = true
    ): bool {
        $classDefinition = ClassDefinition::getByIdIgnoreCase($classId);
        if (!$classDefinition) {
            // The collection key matched a live definition, but the class id of this particular
            // parse does not resolve to any existing class - either the split point was wrong
            // (underscores make it ambiguous) or the owning class itself was deleted. Report the
            // parse as not-owning so the caller can try the remaining candidate parses and only
            // drop the table when none of them resolves to a live class.
            return false;
        }

        $fieldsQuery = 'SELECT fieldname FROM ' . $tableName . ' GROUP BY fieldname';
        $fieldNames = $this->db->fetchFirstColumn($fieldsQuery);

        foreach ($fieldNames as $fieldName) {
            $fieldDef = $classDefinition->getFieldDefinition($fieldName);
            if (!$fieldDef && $isLocalized) {
                $lfDef = $classDefinition->getFieldDefinition('localizedfields');
                if ($lfDef instanceof ClassDefinition\Data\Localizedfields) {
                    $fieldDef = $lfDef->getFieldDefinition($fieldName);
                }
            }

            if (!$fieldDef) {
                $this->logger->info(
                    "Field '" . $fieldName . "' of class '" . $classId .
                    "' does not exist anymore. Cleaning " . $tableName
                );
                $this->db->delete($tableName, ['fieldname' => $fieldName]);
            }
        }

        return true;
    }
}
