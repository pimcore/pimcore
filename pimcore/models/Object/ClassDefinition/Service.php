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
 * @package    Object|Class
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\ClassDefinition;

use Pimcore\Loader\ImplementationLoader\LoaderInterface;
use Pimcore\Model\Object;
use Pimcore\Model\Webservice;

class Service
{
    /**
     * @static
     *
     * @param  Object\ClassDefinition $class
     *
     * @return string
     */
    public static function generateClassDefinitionJson($class)
    {
        $data = Webservice\Data\Mapper::map($class, '\\Pimcore\\Model\\Webservice\\Data\\ClassDefinition\\Out', 'out');
        unset($data->id);
        unset($data->name);
        unset($data->creationDate);
        unset($data->modificationDate);
        unset($data->userOwner);
        unset($data->userModification);
        unset($data->fieldDefinitions);

        //add propertyVisibility to export data
        $data->propertyVisibility = $class->propertyVisibility;

        $json = json_encode($data, JSON_PRETTY_PRINT);

        return $json;
    }

    /**
     * @param $class
     * @param $json
     * @param bool $throwException
     *
     * @return bool
     */
    public static function importClassDefinitionFromJson($class, $json, $throwException = false)
    {
        $userId = 0;
        $user = \Pimcore\Tool\Admin::getCurrentUser();
        if ($user) {
            $userId = $user->getId();
        }

        $importData = json_decode($json, true);

        if (!is_null($importData['layoutDefinitions'])) {
            // set layout-definition
            $layout = self::generateLayoutTreeFromArray($importData['layoutDefinitions'], $throwException);
            if ($layout === false) {
                return false;
            }
            $class->setLayoutDefinitions($layout);
        }

        // set properties of class
        $class->setDescription($importData['description']);
        $class->setModificationDate(time());
        $class->setUserModification($userId);
        $class->setIcon($importData['icon']);
        $class->setAllowInherit($importData['allowInherit']);
        $class->setAllowVariants($importData['allowVariants']);
        $class->setShowVariants($importData['showVariants']);
        $class->setParentClass($importData['parentClass']);
        $class->setUseTraits($importData['useTraits']);
        $class->setPreviewUrl($importData['previewUrl']);
        $class->setPropertyVisibility($importData['propertyVisibility']);

        $class->save();

        return true;
    }

    /**
     * @param $fieldCollection
     *
     * @return string
     */
    public static function generateFieldCollectionJson($fieldCollection)
    {
        unset($fieldCollection->key);
        unset($fieldCollection->fieldDefinitions);

        $json = json_encode($fieldCollection, JSON_PRETTY_PRINT);

        return $json;
    }

    /**
     * @param $fieldCollection
     * @param $json
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

        $fieldCollection->setParentClass($importData['parentClass']);
        $fieldCollection->save();

        return true;
    }

    /**
     * @param $objectBrick
     *
     * @return string
     */
    public static function generateObjectBrickJson($objectBrick)
    {
        unset($objectBrick->key);
        unset($objectBrick->fieldDefinitions);

        // set classname attribute to the real class name not to the class ID
        // this will allow to import the brick on a different instance with identical class names but different class IDs
        if (is_array($objectBrick->classDefinitions)) {
            foreach ($objectBrick->classDefinitions as &$cd) {
                $class = Object\ClassDefinition::getById($cd['classname']);
                if ($class) {
                    $cd['classname'] = $class->getName();
                }
            }
        }

        $json = json_encode($objectBrick, JSON_PRETTY_PRINT);

        return $json;
    }

    /**
     * @param $objectBrick
     * @param $json
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
                    $class = Object\ClassDefinition::getById($cd['classname']);
                    if ($class) {
                        $toAssignClassDefinitions[] = $cd;
                    }
                } else {
                    $class = Object\ClassDefinition::getByName($cd['classname']);
                    if ($class) {
                        $cd['classname'] = $class->getId();
                        $toAssignClassDefinitions[] = $cd;
                    }
                }
            }
        }

        if (!is_null($importData['layoutDefinitions'])) {
            $layout = self::generateLayoutTreeFromArray($importData['layoutDefinitions'], $throwException);
            $objectBrick->setLayoutDefinitions($layout);
        }

        $objectBrick->setClassDefinitions($toAssignClassDefinitions);
        $objectBrick->setParentClass($importData['parentClass']);
        $objectBrick->save();

        return true;
    }

    /**
     * @param $array
     * @param bool $throwException
     *
     * @return bool
     *
     * @throws \Exception
     */
    public static function generateLayoutTreeFromArray($array, $throwException = false)
    {
        if (is_array($array) && count($array) > 0) {
            /** @var LoaderInterface $loader */
            $loader = \Pimcore::getContainer()->get('pimcore.implementation_loader.object.' . $array['datatype']);

            if ($loader->supports($array['fieldtype'])) {
                $item = $loader->build($array['fieldtype']);

                if (method_exists($item, 'addChild')) { // allows childs

                    $item->setValues($array, ['childs']);

                    if (is_array($array) && is_array($array['childs']) && isset($array['childs']['datatype']) && $array['childs']['datatype']) {
                        $childO = self::generateLayoutTreeFromArray($array['childs'], $throwException);
                        $item->addChild($childO);
                    } elseif (is_array($array['childs']) && count($array['childs']) > 0) {
                        foreach ($array['childs'] as $child) {
                            $childO = self::generateLayoutTreeFromArray($child, $throwException);
                            if ($childO !== false) {
                                $item->addChild($childO);
                            } else {
                                if ($throwException) {
                                    throw new \Exception('Could not add child ' . var_export($child, true));
                                }

                                return false;
                            }
                        }
                    }
                } else {
                    $item->setValues($array);
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
     * @param $tableDefinitions
     * @param $tableNames
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
                if (strtolower($column['Null']) == 'yes') {
                    $column['Null'] = 'null';
                }
//                $fieldName = strtolower($column["Field"]);
                $fieldName = $column['Field'];
                $tableDefinitions[$tableName][$fieldName] = $column;
            }
        }
    }

    /**
     * @param $tableDefinitions
     * @param $table
     * @param $colName
     * @param $type
     * @param $default
     * @param $null
     *
     * @return bool
     */
    public static function skipColumn($tableDefinitions, $table, $colName, $type, $default, $null)
    {
        $tableDefinition = $tableDefinitions[$table];
        if ($tableDefinition) {
            $colDefinition = $tableDefinition[$colName];
            if ($colDefinition) {
                if (!strlen($default) && strtolower($null) === 'null') {
                    $default = null;
                }

                if ($colDefinition['Type'] == $type && strtolower($colDefinition['Null']) == strtolower($null)
                    && $colDefinition['Default'] == $default) {
                    return true;
                }
            }
        }

        return false;
    }
}
