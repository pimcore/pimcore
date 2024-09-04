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

namespace Pimcore\Model\DataObject\ClassDefinition;

use Exception;
use Pimcore;
use Pimcore\Loader\ImplementationLoader\LoaderInterface;
use Pimcore\Logger;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\VarExporterInterface;
use Pimcore\Tool;

class Service
{
    private static bool $doRemoveDynamicOptions = false;

    /**
     * @internal
     *
     */
    public static function doRemoveDynamicOptions(): bool
    {
        return self::$doRemoveDynamicOptions;
    }

    /**
     * @internal
     *
     */
    public static function setDoRemoveDynamicOptions(bool $doRemoveDynamicOptions): void
    {
        self::$doRemoveDynamicOptions = $doRemoveDynamicOptions;
    }

    public static function generateClassDefinitionJson(DataObject\ClassDefinition $class): string
    {
        $class = clone $class;
        $layoutDefinitions = $class->getLayoutDefinitions();
        if ($layoutDefinitions instanceof Layout) {
            self::removeDynamicOptionsFromLayoutDefinition($layoutDefinitions);
        }

        self::setDoRemoveDynamicOptions(true);
        $data = json_decode(json_encode($class));
        self::setDoRemoveDynamicOptions(false);
        unset($data->name, $data->creationDate, $data->userOwner, $data->userModification, $data->fieldDefinitions);

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    private static function removeDynamicOptionsFromLayoutDefinition(mixed &$layout): void
    {
        if ($layout instanceof VarExporterInterface) {
            $blockedVars = $layout->resolveBlockedVars();
            foreach ($blockedVars as $blockedVar) {
                if (isset($layout->{$blockedVar})) {
                    unset($layout->{$blockedVar});
                }
            }

            if (isset($layout->blockedVarsForExport)) {
                unset($layout->blockedVarsForExport);
            }
        }

        if (method_exists($layout, 'getChildren')) {
            $children = $layout->getChildren();
            if (is_array($children)) {
                foreach ($children as $child) {
                    if ($child instanceof DataObject\ClassDefinition\Data\Select) {
                        if (!$child->useConfiguredOptions() && $child->getOptionsProviderClass()) {
                            $child->options = null;
                        }
                    }
                    self::removeDynamicOptionsFromLayoutDefinition($child);
                }
            }
        }
    }

    public static function importClassDefinitionFromJson(DataObject\ClassDefinition $class, string $json, bool $throwException = false, bool $ignoreId = false): bool
    {
        $userId = 0;
        $user = \Pimcore\Tool\Admin::getCurrentUser();
        if ($user) {
            $userId = $user->getId();
        }

        $importData = json_decode($json, true);

        if ($importData['layoutDefinitions'] !== null) {
            // set layout-definition
            $layout = self::generateLayoutTreeFromArray($importData['layoutDefinitions'], $throwException);
            if ($layout === false) {
                return false;
            }
            $class->setLayoutDefinitions($layout);
        }

        // set properties of class
        if (isset($importData['id']) && $importData['id'] && !$ignoreId) {
            $class->setId($importData['id']);
        }
        $class->setModificationDate(time());
        $class->setUserModification($userId);
        $importPropertyNames = [
            'description',
            'icon',
            'group',
            'allowInherit',
            'allowVariants',
            'showVariants',
            'parentClass',
            'implementsInterfaces',
            'listingParentClass',
            'useTraits',
            'listingUseTraits',
            'propertyVisibility',
            'linkGeneratorReference',
            'previewGeneratorReference',
            'compositeIndices',
            'showFieldLookup',
            'enableGridLocking',
            'showAppLoggerTab',
        ];

        foreach ($importPropertyNames as $importPropertyName) {
            if (isset($importData[$importPropertyName])) {
                $class->{'set' . ucfirst($importPropertyName)}($importData[$importPropertyName]);
            }
        }

        $class->save();

        return true;
    }

    public static function generateFieldCollectionJson(DataObject\Fieldcollection\Definition $fieldCollection): string
    {
        $fieldCollection = clone $fieldCollection;
        if ($fieldCollection->layoutDefinitions instanceof Layout) {
            self::removeDynamicOptionsFromLayoutDefinition($fieldCollection->layoutDefinitions);
        }

        self::setDoRemoveDynamicOptions(true);
        $data = json_decode(json_encode($fieldCollection));
        self::setDoRemoveDynamicOptions(false);
        unset($data->key, $data->fieldDefinitions);

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    public static function importFieldCollectionFromJson(DataObject\Fieldcollection\Definition $fieldCollection, string $json, bool $throwException = false): bool
    {
        $importData = json_decode($json, true);

        if (!is_null($importData['layoutDefinitions'])) {
            $layout = self::generateLayoutTreeFromArray($importData['layoutDefinitions'], $throwException);
            $fieldCollection->setLayoutDefinitions($layout);
        }

        $importPropertyNames = [
            'parentClass',
            'implementsInterfaces',
            'title',
            'group',
        ];

        foreach ($importPropertyNames as $importPropertyName) {
            if (isset($importData[$importPropertyName])) {
                $fieldCollection->{'set' . ucfirst($importPropertyName)}($importData[$importPropertyName]);
            }
        }

        $fieldCollection->save();

        return true;
    }

    public static function generateObjectBrickJson(DataObject\Objectbrick\Definition $objectBrick): string
    {
        $objectBrick = clone $objectBrick;

        // set classname attribute to the real class name not to the class ID
        // this will allow to import the brick on a different instance with identical class names but different class IDs
        foreach ($objectBrick->getClassDefinitions() as &$cd) {
            // for compatibility (upgraded pimcore4s that may deliver class ids in $cd['classname'] we need to
            // get the class by id in order to be able to correctly set the classname for the generated json
            if (!$class = DataObject\ClassDefinition::getByName($cd['classname'])) {
                $class = DataObject\ClassDefinition::getById($cd['classname']);
            }

            if ($class) {
                $cd['classname'] = $class->getName();
            }
        }

        if ($objectBrick->layoutDefinitions instanceof Layout) {
            self::removeDynamicOptionsFromLayoutDefinition($objectBrick->layoutDefinitions);
        }
        self::setDoRemoveDynamicOptions(true);
        $data = json_decode(json_encode($objectBrick));
        self::setDoRemoveDynamicOptions(false);
        unset($data->key, $data->fieldDefinitions);

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    public static function generateCustomLayoutJson(CustomLayout $customLayout): string
    {
        if ($layoutDefinitions = $customLayout->getLayoutDefinitions()) {
            self::removeDynamicOptionsFromLayoutDefinition($layoutDefinitions);
        }
        self::setDoRemoveDynamicOptions(true);
        $data = [
            'description' => $customLayout->getDescription(),
            'layoutDefinitions' => json_decode(json_encode($layoutDefinitions)),
            'default' => $customLayout->getDefault(),
        ];
        self::setDoRemoveDynamicOptions(false);

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    public static function importObjectBrickFromJson(DataObject\Objectbrick\Definition $objectBrick, string $json, bool $throwException = false): bool
    {
        $importData = json_decode($json, true);

        // reverse map the class name to the class ID, see: self::generateObjectBrickJson()
        $toAssignClassDefinitions = [];
        if (is_array($importData['classDefinitions'])) {
            foreach ($importData['classDefinitions'] as &$cd) {
                if (is_numeric($cd['classname'])) {
                    $class = DataObject\ClassDefinition::getById($cd['classname']);
                    if ($class) {
                        $cd['classname'] = $class->getName();
                        $toAssignClassDefinitions[] = $cd;
                    }
                } else {
                    $class = DataObject\ClassDefinition::getByName($cd['classname']);
                    if ($class) {
                        $toAssignClassDefinitions[] = $cd;
                    }
                }
            }
        }

        if ($importData['layoutDefinitions'] !== null) {
            $layout = self::generateLayoutTreeFromArray($importData['layoutDefinitions'], $throwException);
            $objectBrick->setLayoutDefinitions($layout);
        }

        $objectBrick->setClassDefinitions($toAssignClassDefinitions);
        $importPropertyNames = [
            'parentClass',
            'implementsInterfaces',
            'title',
            'group',
        ];

        foreach ($importPropertyNames as $importPropertyName) {
            if (isset($importData[$importPropertyName])) {
                $objectBrick->{'set' . ucfirst($importPropertyName)}($importData[$importPropertyName]);
            }
        }

        $objectBrick->save();

        return true;
    }

    /**
     *
     *
     * @throws Exception
     *
     * @internal
     */
    public static function generateLayoutTreeFromArray(array $array, bool $throwException = false, bool $insideLocalizedField = false): Data\EncryptedField|bool|Data|Layout
    {
        if ($array) {
            if ($title = $array['title'] ?? false) {
                if (preg_match('/<.+?>/', $title)) {
                    throw new Exception('not a valid title:' . htmlentities($title));
                }
            }
            if ($name = $array['name'] ?? false) {
                if (preg_match('/<.+?>/', $name)) {
                    throw new Exception('not a valid name:' . htmlentities($name));
                }
            }

            /** @var LoaderInterface $loader */
            $loader = Pimcore::getContainer()->get('pimcore.implementation_loader.object.' . $array['datatype']);

            if ($loader->supports($array['fieldtype'])) {
                /** @var Data|Layout $item */
                $item = $loader->build($array['fieldtype']);

                $insideLocalizedField = $insideLocalizedField || $item instanceof DataObject\ClassDefinition\Data\Localizedfields;

                if (method_exists($item, 'addChild')) { // allows children
                    //TODO remove childs in Pimcore 12
                    $item->setValues($array, ['children', 'childs']);
                    $children = $array['children'] ?? [];

                    if (!empty($children['datatype'])) {
                        $childO = self::generateLayoutTreeFromArray($children, $throwException, $insideLocalizedField);
                        $item->addChild($childO);
                    } elseif (is_array($children) && count($children) > 0) {
                        foreach ($children as $child) {
                            $childO = self::generateLayoutTreeFromArray($child, $throwException, $insideLocalizedField);
                            if ($childO !== false) {
                                $item->addChild($childO);
                            } else {
                                if ($throwException) {
                                    throw new Exception('Could not add child ' . var_export($child, true));
                                }

                                Logger::err('Could not add child ' . var_export($child, true));

                                return false;
                            }
                        }
                    }
                } else {
                    //for BC reasons
                    $blockedVars = [];
                    if ($item instanceof VarExporterInterface) {
                        $blockedVars = $item->resolveBlockedVars();
                    }
                    self::removeDynamicOptionsFromArray($array, $blockedVars);
                    $item->setValues($array);

                    if ($item instanceof DataObject\ClassDefinition\Data\EncryptedField) {
                        $item->setupDelegate($array);
                    }
                }

                return $item;
            }
        }
        if ($throwException) {
            throw new Exception('Could not add child ' . var_export($array, true));
        }

        return false;
    }

    private static function removeDynamicOptionsFromArray(array &$data, array $blockedVars): void
    {
        foreach ($blockedVars as $blockedVar) {
            if (isset($data[$blockedVar])) {
                unset($data[$blockedVar]);
            }
        }
    }

    /**
     *
     * @internal
     */
    public static function updateTableDefinitions(array &$tableDefinitions, array $tableNames): void
    {
        $db = \Pimcore\Db::get();
        $tmp = [];
        foreach ($tableNames as $tableName) {
            $tmp[$tableName] = $db->fetchAllAssociative('show columns from ' . $tableName);
        }

        foreach ($tmp as $tableName => $columns) {
            foreach ($columns as $column) {
                $column['Type'] = strtolower($column['Type']);
                if (strtolower($column['Null']) === 'yes') {
                    $column['Null'] = 'null';
                }
                //                $fieldName = strtolower($column["Field"]);
                $fieldName = $column['Field'];
                $tableDefinitions[$tableName][$fieldName] = $column;
            }
        }
    }

    /**
     *
     *
     * @internal
     */
    public static function skipColumn(array $tableDefinitions, string $table, string $colName, string $type, string $default, string $null): bool
    {
        $tableDefinition = $tableDefinitions[$table] ?? false;
        if ($tableDefinition) {
            $colDefinition = $tableDefinition[$colName] ?? false;
            if ($colDefinition) {
                if (!strlen($default) && strtolower($null) === 'null') {
                    $default = null;
                }

                if (str_replace(' ', '', strtolower($colDefinition['Type'])) === str_replace(' ', '', strtolower($type)) &&
                        strtolower($colDefinition['Null']) == strtolower($null) &&
                        $colDefinition['Default'] == $default) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param string|null $newInterfaces A comma separated list of interfaces
     *
     * @throws Exception
     *
     * @internal
     */
    public static function buildImplementsInterfacesCode(array $implementsParts, ?string $newInterfaces): string
    {
        if ($newInterfaces) {
            $customParts = explode(',', $newInterfaces);
            foreach ($customParts as $interface) {
                $interface = trim($interface);
                if (Tool::interfaceExists($interface)) {
                    $implementsParts[] = $interface;
                } else {
                    throw new Exception("interface '" . $interface . "' does not exist");
                }
            }
        }

        if ($implementsParts) {
            return ' implements ' . implode(', ', $implementsParts);
        }

        return '';
    }

    /**
     *
     *
     * @throws Exception
     *
     * @internal
     */
    public static function buildUseTraitsCode(array $useParts, ?string $newTraits): string
    {
        if ($newTraits) {
            $customParts = explode(',', $newTraits);
            foreach ($customParts as $trait) {
                $trait = trim($trait);
                if (Tool::traitExists($trait)) {
                    $useParts[] = $trait;
                } else {
                    throw new Exception("trait '" . $trait . "' does not exist");
                }
            }
        }

        return self::buildUseCode($useParts);
    }

    /**
     *
     *
     * @throws Exception
     *
     * @internal
     */
    public static function buildUseCode(array $useParts): string
    {
        if ($useParts) {
            $result = '';
            foreach ($useParts as $part) {
                $result .= 'use ' . $part . ";\n";
            }
            $result .= "\n";

            return $result;
        }

        return '';
    }

    /**
     * @internal
     */
    public static function buildFieldConstantsCode(Data ...$fieldDefinitions): string
    {
        $fieldConstants = '';
        foreach ($fieldDefinitions as $fieldDefinition) {
            if (!$fieldDefinition instanceof Data\Localizedfields) {
                $fieldConstants .= static::buildFieldConstantCode($fieldDefinition) . "\n";

                continue;
            }

            foreach ($fieldDefinition->getFieldDefinitions() as $localizedFieldDefinition) {
                $fieldConstants .= static::buildFieldConstantCode($localizedFieldDefinition) . "\n";
            }
        }

        return $fieldConstants . "\n";
    }

    /**
     * @internal
     */
    public static function buildFieldConstantCode(Data $fieldDefinition): string
    {
        $nameUpperSnakeCase = static::camelCaseToUpperSnakeCase($fieldDefinition->getName());

        return 'public const FIELD_' . $nameUpperSnakeCase . ' = \'' . $fieldDefinition->getName() . '\';';
    }

    /**
     * @internal
     */
    public static function camelCaseToUpperSnakeCase(string $camelCase): string
    {
        $snakeCase = ltrim(preg_replace('/[A-Z]+/', '_\\0', $camelCase), '_');

        return strtoupper($snakeCase);
    }
}
