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

    public function matchCollectionKey(string $tableIdentifier, array $collectionNames): ?string
    {
        $match = null;
        foreach ($collectionNames as $key) {
            // Case-insensitive, underscore-delimited prefix match. Prefer the longest matching key
            // so that e.g. "Foo_Bar" wins over "Foo" when both definitions exist.
            if (stripos($tableIdentifier, $key . '_') === 0
                && ($match === null || strlen($key) > strlen($match))
            ) {
                $match = $key;
            }
        }

        return $match;
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
    ): void {
        $classDefinition = ClassDefinition::getByIdIgnoreCase($classId);
        if (!$classDefinition) {
            $this->logger->error("Classdefinition '" . $classId . "' not found. Please check table " . $tableName);

            return;
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
    }
}
