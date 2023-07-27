<?php
declare(strict_types=1);

namespace Pimcore\Maintenance\Tasks\DataObject;

use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class DataObjectTaskHelper implements DataObjectTaskHelperInterface
{
    public function __construct(private LoggerInterface $logger)
    {
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
            $mapLowerToCamelCase[strtolower($classname)] = $classname ;
        }

        return $mapLowerToCamelCase;
    }

    public function cleanupTable(
        string $tableName,
        string $classId,
        bool $isLocalized = true
    ): void
    {
        $classDefinition = ClassDefinition::getByIdIgnoreCase($classId);
        if (!$classDefinition) {
            $this->logger->error("Classdefinition '" . $classId . "' not found. Please check table " . $tableName);
            return;
        }

        $db = Db::get();
        $fieldsQuery = 'SELECT fieldname FROM ' . $tableName . ' GROUP BY fieldname';
        $fieldNames = $db->fetchFirstColumn($fieldsQuery);

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
                $db->delete($tableName, ['fieldname' => $fieldName]);
            }
        }
    }
}
