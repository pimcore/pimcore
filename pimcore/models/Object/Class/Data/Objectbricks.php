<?php 
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Object_Class
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Class_Data_Objectbricks extends Object_Class_Data
{

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "objectbricks";

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "Object_Objectbrick";

    /**
     * @var array
     */
    public $allowedTypes = array();

    /**
     * @see Object_Class_Data::getDataForEditmode
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataForEditmode($data, $object = null, $objectFromVersion = null)
    {
        $editmodeData = array();

        if ($data instanceof Object_Objectbrick) {
            $getters = $data->getBrickGetters();

            foreach ($getters as $getter) {
                $editmodeData[] = $this->doGetDataForEditmode($data, $getter, $objectFromVersion);
            }
        }

        return $editmodeData;
    }

    private function doGetDataForEditmode($data, $getter, $objectFromVersion, $level = 0) {
//        p_r($data); die();
        $parent = Object_Service::hasInheritableParentObject($data->getObject());
        $item = $data->$getter();
        if(!$item && !empty($parent)) {
            $data = $parent->{"get" . ucfirst($this->getName())}();
            return $this->doGetDataForEditmode($data, $getter, $objectFromVersion, $level + 1);
        }

        if (!$item instanceof Object_Objectbrick_Data_Abstract) {
            return null;
        }

        try {
            $collectionDef = Object_Objectbrick_Definition::getByKey($item->getType());
        } catch (Exception $e) {
            return null;
        }

        $brickData = array();
        $brickMetaData = array();

        $inherited = false;
        foreach ($collectionDef->getFieldDefinitions() as $fd) {
            $fieldData = $this->getDataForField($item, $fd->getName(), $fd, $level, $data->getObject(), $getter, $objectFromVersion); //$fd->getDataForEditmode($item->{$fd->getName()});
            $brickData[$fd->getName()] = $fieldData->objectData;
            $brickMetaData[$fd->getName()] = $fieldData->metaData;
            if($fieldData->metaData['inherited'] == true) {
                $inherited = true;
            }
        }

        $editmodeDataItem = array(
            "data" => $brickData,
            "type" => $item->getType(),
            "metaData" => $brickMetaData,
            "inherited" => $inherited
        );
        return $editmodeDataItem;
    }


    /**
     * gets recursively attribute data from parent and fills objectData and metaData
     *
     * @param  $object
     * @param  $key
     * @param  $fielddefinition
     * @return void
     */
    private function getDataForField($item, $key, $fielddefinition, $level, $baseObject, $getter, $objectFromVersion) {
        $result = new stdClass();
        $parent = Object_Service::hasInheritableParentObject($baseObject);
        $valueGetter = "get" . ucfirst($key);

        // relations but not for objectsMetadata, because they have additional data which cannot be loaded directly from the DB
        if (!$objectFromVersion && method_exists($fielddefinition, "getLazyLoading") and $fielddefinition->getLazyLoading() and !$fielddefinition instanceof Object_Class_Data_ObjectsMetadata) {

            //lazy loading data is fetched from DB differently, so that not every relation object is instantiated
            if ($fielddefinition->isRemoteOwner()) {
                $refKey = $fielddefinition->getOwnerFieldName();
                $refId = $fielddefinition->getOwnerClassId();
            } else {
                $refKey = $key;
            }

            $relations = $item->getRelationData($refKey, !$fielddefinition->isRemoteOwner(), $refId);
            if(empty($relations) && !empty($parent)) {
                $parentItem = $parent->{"get" . ucfirst($this->getName())}();
                if(!empty($parentItem)) {
                    $parentItem = $parentItem->$getter();
                    if($parentItem) {
                        return $this->getDataForField($parentItem, $key, $fielddefinition, $level + 1, $parent, $getter, $objectFromVersion);
                    }
                }
            }
            $data = array();

            if ($fielddefinition instanceof Object_Class_Data_Href) {
                $data = $relations[0];
            } else {
                foreach ($relations as $rel) {
                    if ($fielddefinition instanceof Object_Class_Data_Objects) {
                        $data[] = array($rel["id"], $rel["path"], $rel["subtype"]);
                    } else {
                        $data[] = array($rel["id"], $rel["path"], $rel["type"], $rel["subtype"]);
                    }
                }
            }
            $result->objectData = $data;
            $result->metaData['objectid'] = $baseObject->getId();
            $result->metaData['inherited'] = $level != 0;

        } else {
            $value = null;
            if(!empty($item)) {
                $value = $fielddefinition->getDataForEditmode($item->$valueGetter(), $baseObject);
            }
            if(empty($value) && !empty($parent)) {
                $parentItem = $parent->{"get" . ucfirst($this->getName())}()->$getter();
                if(!empty($parentItem)) {
                    return $this->getDataForField($parentItem, $key, $fielddefinition, $level + 1, $parent, $getter, $objectFromVersion);
                }
            }
            $result->objectData = $value;
            $result->metaData['objectid'] = $baseObject->getId();
            $result->metaData['inherited'] = $level != 0;
        }
        return $result;
    }

    /**
     * @see Object_Class_Data::getDataFromEditmode
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataFromEditmode($data, $object = null)
    {

        $getter = "get" . ucfirst($this->getName());


        $container = $object->$getter(); //$this->getObject()->$getter();
        if(empty($container)) {
            $className = $object->getClass()->getName(); //$this->getObject()->getClass()->getName();

            $containerClass = "Object_" . ucfirst($className) . "_" . ucfirst($this->getName());
            $container = new $containerClass($object, $this->getName()); //$this->getObject(), $this->getName());
        }

        if (is_array($data)) {
            foreach ($data as $collectionRaw) {

                $collectionData = array();
                $collectionDef = Object_Objectbrick_Definition::getByKey($collectionRaw["type"]);

                $getter = "get" . ucfirst($collectionRaw["type"]);
                $brick = $container->$getter();
                if(empty($brick)) {
                    $brickClass = "Object_Objectbrick_Data_" . ucfirst($collectionRaw["type"]);
                    $brick = new $brickClass($object); //$this->getObject());
                }


                if($collectionRaw["data"] == "deleted") {
                    $brick->setDoDelete(true);
                } else {
                    foreach ($collectionDef->getFieldDefinitions() as $fd) {
                        if (array_key_exists($fd->getName(), $collectionRaw["data"])) {
                            $collectionData[$fd->getName()] = $fd->getDataFromEditmode($collectionRaw["data"][$fd->getName()], $object);
                        }
                    }
                    $brick->setValues($collectionData);
                    $brick->setFieldname($this->getName());
                }

                $setter = "set" . ucfirst($collectionRaw["type"]);
                $container->$setter($brick);
            }
        }

        return $container;
    }

    /**
     * @see Object_Class_Data::getVersionPreview
     * @param string $data
     * @return string
     */
    public function getVersionPreview($data)
    {
        return "BRICKS";
    }

    /**
     * @param Object_Abstract $object
     * @return string
     */
    public function getForCsvExport($object)
    {
        return "NOT SUPPORTED";
    }

    /**
     * @param string $importValue
     * @return null
     */
    public function getFromCsvImport($importValue)
    {
        return;
    }


    public function save($object, $params = array())
    {
        $getter = "get" . ucfirst($this->getName());
        $container = $object->$getter();
        if ($container instanceof Object_Objectbrick) {
            $container->save($object);
        }
    }

    public function load($object, $params = array())
    {
        $classname = "Object_" . ucfirst($object->getClass()->getName()) . "_" . ucfirst($this->getName());

        if(Pimcore_Tool::classExists($classname)) {
            $container = new $classname($object, $this->getName());
            $container->load($object);

            return $container;
        } else {
            return null;
        }
    }

    public function delete($object)
    {
        $container = $this->load($object);
        if($container) {
            $container->delete($object);
        }
    }

    public function getAllowedTypes()
    {

        return $this->allowedTypes;
    }

    public function setAllowedTypes($allowedTypes)
    {
        if (is_string($allowedTypes)) {
            $allowedTypes = explode(",", $allowedTypes);
        }

        if (is_array($allowedTypes)) {
            for ($i = 0; $i < count($allowedTypes); $i++) {
                try {
                    Object_Objectbrick_Definition::getByKey($allowedTypes[$i]);
                } catch (Exception $e) {

                    Logger::warn("Removed unknown allowed type [ $allowedTypes[$i] ] from allowed types of object brick");
                    unset($allowedTypes[$i]);
                }
            }
        }

        $this->allowedTypes = (array)$allowedTypes;
    }

    /**
     * @param Object_Abstract $object
     * @return mixed
     */
    public function getForWebserviceExport($object)
    {
        $getter = "get" . ucfirst($this->getName());
        $data = $object->$getter();
        $wsData = array();

        if ($data instanceof Object_Objectbrick) {
            foreach ($data as $item) {


                if (!$item instanceof Object_Objectbrick_Data_Abstract) {
                    continue;
                }

                $wsDataItem = new Webservice_Data_Object_Element();
                $wsDataItem->value = array();
                $wsDataItem->type = $item->getType();

                try {
                    $collectionDef = Object_Objectbrick_Definition::getByKey($item->getType());
                } catch (Exception $e) {
                    continue;
                }

                foreach ($collectionDef->getFieldDefinitions() as $fd) {
                    $el = new Webservice_Data_Object_Element();
                    $el->name = $fd->getName();
                    $el->type = $fd->getFieldType();
                    $el->value = $fd->getForWebserviceExport($item);

                    $wsDataItem->value[] = $el;

                }


            }
            $wsData[] = $wsDataItem;

        }

        return $wsData;


    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function getFromWebserviceImport($data, $object)
    {
        $containerName = "Object_" . ucfirst($object->getClass()->getName()) . "_" . ucfirst($this->getName());
        $container = new $containerName($object, $this->getName());

        if (is_array($data)) {
            foreach ($data as $collectionRaw) {
                if($collectionRaw != null) {
                    if (!$collectionRaw instanceof Webservice_Data_Object_Element) {

                        throw new Exception("invalid data in objectbrick [" . $this->getName() . "]");
                    }

                    $brick = $collectionRaw->type;
                    $collectionData = array();
                    $collectionDef = Object_Objectbrick_Definition::getByKey($brick);

                    if (!$collectionDef) {
                        throw new Exception("Unknown objectbrick in webservice import [" . $brick . "]");
                    }

                    foreach ($collectionDef->getFieldDefinitions() as $fd) {
                        foreach ($collectionRaw->value as $field) {
                            if (!$field instanceof Webservice_Data_Object_Element) {
                                throw new Exception("invalid data in objectbricks [" . $this->getName() . "]");
                            } else if ($field->name == $fd->getName()) {

                                if ($field->type != $fd->getFieldType()) {
                                    throw new Exception("Type mismatch for objectbricks field [" . $field->name . "]. Should be [" . $fd->getFieldType() . "] but is [" . $field->type . "]");
                                }
                                $collectionData[$fd->getName()] = $fd->getFromWebserviceImport($field->value);
                                break;
                            }


                        }

                    }

                    $collectionClass = "Object_Objectbrick_Data_" . ucfirst($brick);
                    $collection = new $collectionClass($object);
                    $collection->setValues($collectionData);
                    $collection->setFieldname($this->getName());

                    $setter = "set" . ucfirst($brick);

                    $container->$setter($collection);
                }

            }
        }

        return $container;

    }


    public function preSetData($object, $value)
    {
        if ($value instanceof Object_Objectbrick) {
            $value->setFieldname($this->getName());
        }
        return $value;
    }


    /**
     * @param mixed $data
     */
    public function resolveDependencies($data)
    {
        $dependencies = array();

        if ($data instanceof Object_Objectbrick) {
            $items = $data->getItems();
            foreach ($items as $item) {
                if (!$item instanceof Object_Objectbrick_Data_Abstract) {
                    continue;
                }

                try {
                    $collectionDef = Object_Objectbrick_Definition::getByKey($item->getType());
                } catch (Exception $e) {
                    continue;
                }

                foreach ($collectionDef->getFieldDefinitions() as $fd) {
                    $key = $fd->getName();
                    $getter = "get" . ucfirst($key);
                    $dependencies = array_merge($dependencies, $fd->resolveDependencies($item->$getter()));
                }
            }
        }

        return $dependencies;
    }

    /**
     * @param mixed $data
     * @param Object_Concrete $ownerObject
     * @param array $blockedTags
     */
    public function getCacheTags($data, $ownerObject, $tags = array())
    {
        $tags = is_array($tags) ? $tags : array();

        if ($data instanceof Object_Objectbrick) {
            $items = $data->getItems();
            foreach ($items as $item) {

                if (!$item instanceof Object_Objectbrick_Data_Abstract) {
                    continue;
                }

                try {
                    $collectionDef = Object_Objectbrick_Definition::getByKey($item->getType());
                } catch (Exception $e) {
                    continue;
                }

                foreach ($collectionDef->getFieldDefinitions() as $fd) {
                    $key = $fd->getName();
                    $getter = "get" . ucfirst($key);
                    $tags = $fd->getCacheTags($item->$getter(), $item, $tags);
                }
            }
        }

        return $tags;
    }


    public function getGetterCode ($class) {
        // getter

        $key = $this->getName();
        $code = "";

        $code .= '/**' . "\n";
        $code .= '* @return ' . $this->getPhpdocType() . "\n";
        $code .= '*/' . "\n";
        $code .= "public function get" . ucfirst($key) . " () {\n";

        $code .= "\t" . '$data = $this->' . $key . ";\n";
        $code .= "\t" . 'if(!$data) { ' . "\n";

        $classname = "Object_" . ucfirst($class->getName()) . "_" . ucfirst($this->getName());

        $code .= "\t\t" . 'if(Pimcore_Tool::classExists("' . $classname . '")) { ' . "\n";
        $code .= "\t\t\t" . '$data = new ' . $classname . '($this, "' . $key . '");' . "\n";
        $code .= "\t\t\t" . '$this->' . $key . ' = $data;' . "\n";
        $code .= "\t\t" . '} else {' . "\n";
        $code .= "\t\t\t" . 'return null;' . "\n";
        $code .= "\t\t" . '}' . "\n";
        $code .= "\t" . '}' . "\n";


        if(method_exists($this,"preGetData")) {
            $code .= "\t" . '$data = $this->getClass()->getFieldDefinition("' . $key . '")->preGetData($this);' . "\n";
        }

        // adds a hook preGetValue which can be defined in an extended class
        $code .= "\t" . '$preValue = $this->preGetValue("' . $key . '");' . " \n";
        $code .= "\t" . 'if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}' . "\n";

        $code .= "\t return " . '$data' . ";\n";
        $code .= "}\n\n";

        return $code;
    }


    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false) {
        if(!$omitMandatoryCheck){
            if ($data instanceof Object_Objectbrick) {
                $items = $data->getItems();
                foreach ($items as $item) {
                    if($item->getDoDelete()) {
                        continue;
                    }

                    if (!$item instanceof Object_Objectbrick_Data_Abstract) {
                        continue;
                    }

                    try {
                        $collectionDef = Object_Objectbrick_Definition::getByKey($item->getType());
                    } catch (Exception $e) {
                        continue;
                    }

                    foreach ($collectionDef->getFieldDefinitions() as $fd) {
                        $key = $fd->getName();
                        $getter = "get" . ucfirst($key);
                        $fd->checkValidity($item->$getter());
                    }
                }
            }
        }
    }

    /**
     * @param $data
     * @param Object_Concrete $object
     * @return string
     */
    public function getDataForGrid($data, $object = null) {
        return "NOT SUPPORTED";
    }
}
