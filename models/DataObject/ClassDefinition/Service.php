<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition;

use Pimcore\Loader\ImplementationLoader\LoaderInterface;
use Pimcore\Logger;
use Pimcore\Model\DataObject;
use Pimcore\Tool;

/**
 * Class Service
 *
 * @package Pimcore\Model\DataObject\ClassDefinition
 */
class Service
{
    /**
     * @static
     *
     * @param  DataObject\ClassDefinition $class
     *
     * @return string
     */
    public static function generateClassDefinitionJson($class)
    {
        $class = clone $class;
        $data = json_decode(json_encode($class));
        unset($data->name);
        unset($data->creationDate);
        unset($data->userOwner);
        unset($data->userModification);
        unset($data->fieldDefinitions);

        self::removeDynamicOptionsFromLayoutDefinition($data->layoutDefinitions);

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    public static function removeDynamicOptionsFromLayoutDefinition(&$layout)
    {
        if (method_exists($layout, 'getChildren')) {
            $children = $layout->getChildren();
            if (is_array($children)) {
                foreach ($children as $child) {
                    if ($child instanceof DataObject\ClassDefinition\Data\Select) {
                        if ($child->getOptionsProviderClass()) {
                            $child->options = null;
                        }
                    }
                    self::removeDynamicOptionsFromLayoutDefinition($child);
                }
            }
        }
    }

    /**
     * @param DataObject\ClassDefinition $class
     * @param string $json
     * @param bool $throwException
     * @param bool $ignoreId
     *
     * @return bool
     */
    public static function importClassDefinitionFromJson($class, $json, $throwException = false, $ignoreId = false)
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

        foreach (['description', 'icon', 'group', 'allowInherit', 'allowVariants', 'showVariants', 'parentClass',
                    'implementsInterfaces', 'listingParentClass', 'useTraits', 'listingUseTraits', 'previewUrl', 'propertyVisibility',
                    'linkGeneratorReference', 'compositeIndices', 'generateTypeDeclarations', ] as $importPropertyName) {
            if (isset($importData[$importPropertyName])) {
                $class->{'set' . ucfirst($importPropertyName)}($importData[$importPropertyName]);
            }
        }

        $class->save();

        return true;
    }

    /**
     * @param DataObject\Fieldcollection\Definition $fieldCollection
     *
     * @return string
     */
    public static function generateFieldCollectionJson($fieldCollection)
    {
        $fieldCollection = clone $fieldCollection;
        $fieldCollection->setKey(null);
        $fieldCollection->setFieldDefinitions([]);

        return json_encode($fieldCollection, JSON_PRETTY_PRINT);
    }

    /**
     * @param DataObject\Fieldcollection\Definition $fieldCollection
     * @param string $json
     * @param bool $throwException
     *
     * @return bool
     */
    public static function importFieldCollectionFromJson($fieldCollection, $json, $throwException = false)
    {
        $importData = json_decode($json, true);

        if (!is_null($importData['layoutDefinitions'])) {
            $layout = self::generateLayoutTreeFromArray($importData['layoutDefinitions'], $throwException);
            $fieldCollection->setLayoutDefinitions($layout);
        }

        foreach (['parentClass', 'implementsInterfaces', 'title', 'group', 'generateTypeDeclarations'] as $importPropertyName) {
            if (isset($importData[$importPropertyName])) {
                $fieldCollection->{'set' . ucfirst($importPropertyName)}($importData[$importPropertyName]);
            }
        }

        $fieldCollection->save();

        return true;
    }

    /**
     * @param DataObject\Objectbrick\Definition $objectBrick
     *
     * @return string
     */
    public static function generateObjectBrickJson($objectBrick)
    {
        $objectBrick = clone $objectBrick;
        $objectBrick->setKey(null);
        $objectBrick->setFieldDefinitions([]);

        // set classname attribute to the real class name not to the class ID
        // this will allow to import the brick on a different instance with identical class names but different class IDs
        if (is_array($objectBrick->classDefinitions)) {
            foreach ($objectBrick->classDefinitions as &$cd) {
                // for compatibility (upgraded pimcore4s that may deliver class ids in $cd['classname'] we need to
                // get the class by id in order to be able to correctly set the classname for the generated json
                if (!$class = DataObject\ClassDefinition::getByName($cd['classname'])) {
                    $class = DataObject\ClassDefinition::getById($cd['classname']);
                }

                if ($class) {
                    $cd['classname'] = $class->getName();
                }
            }
        }

        return json_encode($objectBrick, JSON_PRETTY_PRINT);
    }

    /**
     * @param DataObject\Objectbrick\Definition $objectBrick
     * @param string $json
     * @param bool $throwException
     *
     * @return bool
     */
    public static function importObjectBrickFromJson($objectBrick, $json, $throwException = false)
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
        $objectBrick->setParentClass($importData['parentClass']);
        $objectBrick->setImplementsInterfaces($importData['implementsInterfaces'] ?? null);
        $objectBrick->setGenerateTypeDeclarations($importData['generateTypeDeclarations'] ?? null);
        if (isset($importData['title'])) {
            $objectBrick->setTitle($importData['title']);
        }
        if (isset($importData['group'])) {
            $objectBrick->setGroup($importData['group']);
        }
        $objectBrick->save();

        return true;
    }

    /**
     * @param array $array
     * @param bool $throwException
     * @param bool $insideLocalizedField
     *
     * @return Data|Layout|false
     *
     * @throws \Exception
     */
    public static function generateLayoutTreeFromArray($array, $throwException = false, $insideLocalizedField = false)
    {
        if (is_array($array) && count($array) > 0) {
            /** @var LoaderInterface $loader */
            $loader = \Pimcore::getContainer()->get('pimcore.implementation_loader.object.' . $array['datatype']);

            if ($loader->supports($array['fieldtype'])) {
                /** @var Data|Layout $item */
                $item = $loader->build($array['fieldtype']);

                $insideLocalizedField = $insideLocalizedField || $item instanceof DataObject\ClassDefinition\Data\Localizedfields;

                if (method_exists($item, 'addChild')) { // allows childs
                    $item->setValues($array, ['childs']);
                    $childs = $array['childs'] ?? [];

                    if (!empty($childs['datatype'])) {
                        $childO = self::generateLayoutTreeFromArray($childs, $throwException, $insideLocalizedField);
                        $item->addChild($childO);
                    } elseif (is_array($childs) && count($childs) > 0) {
                        foreach ($childs as $child) {
                            $childO = self::generateLayoutTreeFromArray($child, $throwException, $insideLocalizedField);
                            if ($childO !== false) {
                                $item->addChild($childO);
                            } else {
                                if ($throwException) {
                                    throw new \Exception('Could not add child ' . var_export($child, true));
                                }

                                Logger::err('Could not add child ' . var_export($child, true));

                                return false;
                            }
                        }
                    }
                } else {
                    $item->setValues($array);

                    if ($item instanceof DataObject\ClassDefinition\Data\EncryptedField) {
                        $item->setupDelegate($array);
                    }
                }

                return $item;
            }
        }
        if ($throwException) {
            throw new \Exception('Could not add child ' . var_export($array, true));
        }

        return false;
    }

    /**
     * @param array $tableDefinitions
     * @param array $tableNames
     */
    public static function updateTableDefinitions(&$tableDefinitions, $tableNames)
    {
        if (!is_array($tableDefinitions)) {
            $tableDefinitions = [];
        }

        $db = \Pimcore\Db::get();
        $tmp = [];
        foreach ($tableNames as $tableName) {
            $tmp[$tableName] = $db->fetchAll('show columns from ' . $tableName);
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
     * @param array $tableDefinitions
     * @param string $table
     * @param string $colName
     * @param string $type
     * @param string $default
     * @param string $null
     *
     * @return bool
     */
    public static function skipColumn($tableDefinitions, $table, $colName, $type, $default, $null)
    {
        $tableDefinition = $tableDefinitions[$table] ?? false;
        if ($tableDefinition) {
            $colDefinition = $tableDefinition[$colName];
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
     * @param array $implementsParts
     * @param string|null $newInterfaces A comma separated list of interfaces
     *
     * @return string
     *
     * @throws \Exception
     */
    public static function buildImplementsInterfacesCode($implementsParts, ?string $newInterfaces)
    {
        if ($newInterfaces) {
            $customParts = explode(',', $newInterfaces);
            foreach ($customParts as $interface) {
                $interface = trim($interface);
                if (Tool::interfaceExists($interface)) {
                    $implementsParts[] = $interface;
                } else {
                    throw new \Exception("interface '" . $interface . "' does not exist");
                }
            }
        }

        if ($implementsParts) {
            return ' implements ' . implode(', ', $implementsParts);
        }

        return '';
    }

    /**
     * @param array $useParts
     * @param string|null $newTraits
     *
     * @return string
     *
     * @throws \Exception
     */
    public static function buildUseTraitsCode($useParts, ?string $newTraits)
    {
        if (!is_array($useParts)) {
            $useParts = [];
        }

        if ($newTraits) {
            $customParts = explode(',', $newTraits);
            foreach ($customParts as $trait) {
                $trait = trim($trait);
                if (Tool::traitExists($trait)) {
                    $useParts[] = $trait;
                } else {
                    throw new \Exception("trait '" . $trait . "' does not exist");
                }
            }
        }

        return self::buildUseCode($useParts);
    }

    /**
     * @param array $useParts
     *
     * @return string
     *
     * @throws \Exception
     */
    public static function buildUseCode($useParts)
    {
        if ($useParts) {
            $result = '';
            foreach ($useParts as $part) {
                $result .= 'use ' . $part . ";\r\n";
            }
            $result .= "\n";

            return $result;
        }

        return '';
    }
}
