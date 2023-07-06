<?php
declare(strict_types=1);

namespace Pimcore\Maintenance\Tasks;

use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use Psr\Log\LoggerInterface;

final class TaskHelper
{
    private static ?TaskHelper $instance = null;

    private LoggerInterface $logger;
    public static function create(LoggerInterface $logger): self
    {
        if(!self::$instance) {
            self::$instance = new self();
        }
        self::$instance->logger = $logger;

        return self::$instance;
    }
    public function getDataStructureNamesMapLowerToCamelCase(string $dir): array
    {
        $mapLowerToCamelCase = [];
        $files = array_diff(scandir($dir), ['..', '.']);
        foreach ($files as $file) {
            $classname = str_replace('.php', '', $file);
            $mapLowerToCamelCase[strtolower($classname)] = $classname ;
        }

        return $mapLowerToCamelCase;
    }

    public function getClassDefintionByClassId(string $classId, string $tableName): ClassDefinition|null
    {
        $classDefinition = ClassDefinition::getByIdIgnoreCase($classId);
        if (!$classDefinition) {
            $this->logger->error("Classdefinition '" . $classId . "' not found. Please check table " . $tableName);
        }
        return $classDefinition;
    }

    public function cleaningTable(
        string $tableName,
        ClassDefinition $classDefinition,
        string $classId,
        bool $isLocalized = true
    ): void
    {
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
