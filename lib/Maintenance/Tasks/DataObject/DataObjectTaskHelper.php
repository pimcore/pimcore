<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Maintenance\Tasks\DataObject;

use Doctrine\DBAL\Connection;
use Pimcore\Model\DataObject\ClassDefinition;
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

    public function getCollectionNames(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $mapLowerToCamelCase = [];
        $files = array_diff(scandir($dir), ['..', '.']);
        foreach ($files as $file) {
            $classname = str_replace('.php', '', $file);
            $mapLowerToCamelCase[strtolower($classname)] = $classname;
        }

        return $mapLowerToCamelCase;
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
