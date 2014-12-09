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
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Object;
use Pimcore\Model\Webservice;
use Pimcore\Tool;

class Objectbricks extends Model\Object\ClassDefinition\Data
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
    public $phpdocType = "\\Pimcore\\Model\\Object\\Objectbrick";

    /**
     * @var array
     */
    public $allowedTypes = array();

    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataForEditmode($data, $object = null, $objectFromVersion = null)
    {
        $editmodeData = array();

        if ($data instanceof Object\Objectbrick) {
            $getters = $data->getBrickGetters();

            foreach ($getters as $getter) {
                $editmodeData[] = $this->doGetDataForEditmode($data, $getter, $objectFromVersion);
            }
        }

        return $editmodeData;
    }

    /**
     * @param $data
     * @param $getter
     * @param $objectFromVersion
     * @param int $level
     * @return array
     */
    private function doGetDataForEditmode($data, $getter, $objectFromVersion, $level = 0) {

        $parent = Object\Service::hasInheritableParentObject($data->getObject());
        $item = $data->$getter();
        if(!$item && !empty($parent)) {
            $data = $parent->{"get" . ucfirst($this->getName())}();
            return $this->doGetDataForEditmode($data, $getter, $objectFromVersion, $level + 1);
        }

        if (!$item instanceof Object\Objectbrick\Data\AbstractData) {
            return null;
        }

        try {
            $collectionDef = Object\Objectbrick\Definition::getByKey($item->getType());
        } catch (\Exception $e) {
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
     * @param  $item
     * @param  $key
     * @param  $fielddefinition
     * @return mixed
     */
    private function getDataForField($item, $key, $fielddefinition, $level, $baseObject, $getter, $objectFromVersion) {
        $result = new \stdClass();
        $parent = Object\Service::hasInheritableParentObject($baseObject);
        $valueGetter = "get" . ucfirst($key);

        // relations but not for objectsMetadata, because they have additional data which cannot be loaded directly from the DB
        if (!$objectFromVersion && method_exists($fielddefinition, "getLazyLoading") and $fielddefinition->getLazyLoading() and !$fielddefinition instanceof Object\ClassDefinition\Data\ObjectsMetadata) {

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

            if ($fielddefinition instanceof Object\ClassDefinition\Data\Href) {
                $data = $relations[0];
            } else {
                foreach ($relations as $rel) {
                    if ($fielddefinition instanceof Object\ClassDefinition\Data\Objects) {
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
            $editmodeValue = null;
            if(!empty($item)) {
                $fieldValue = $item->$valueGetter();
                $editmodeValue = $fielddefinition->getDataForEditmode($fieldValue, $baseObject);
            }
            if($fielddefinition->isEmpty($fieldValue) && !empty($parent)) {
                $parentItem = $parent->{"get" . ucfirst($this->getName())}()->$getter();
                if(!empty($parentItem)) {
                    return $this->getDataForField($parentItem, $key, $fielddefinition, $level + 1, $parent, $getter, $objectFromVersion);
                }
            }
            $result->objectData = $editmodeValue;
            $result->metaData['objectid'] = $baseObject->getId();
            $result->metaData['inherited'] = $level != 0;
        }
        return $result;
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataFromEditmode($data, $object = null)
    {
        $container = $this->getDataFromObjectParam($object);

        if(empty($container)) {
            $className = $object->getClass()->getName();

            $containerClass = "\\Pimcore\\Model\\Object\\" . ucfirst($className) . "\\" . ucfirst($this->getName());
            $container = new $containerClass($object, $this->getName());
        }

        if (is_array($data)) {
            foreach ($data as $collectionRaw) {

                $collectionData = array();
                $collectionDef = Object\Objectbrick\Definition::getByKey($collectionRaw["type"]);

                $getter = "get" . ucfirst($collectionRaw["type"]);
                $brick = $container->$getter();
                if(empty($brick)) {
                    $brickClass = "\\Pimcore\\Model\\Object\\Objectbrick\\Data\\" . ucfirst($collectionRaw["type"]);
                    $brick = new $brickClass($object);
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
     * @see Object\ClassDefinition\Data::getVersionPreview
     * @param string $data
     * @return string
     */
    public function getVersionPreview($data)
    {
        return "BRICKS";
    }

    /**
     * @param Model\Object\AbstractObject $object
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

    /**
     * @param $object
     * @param array $params
     */
    public function save($object, $params = array())
    {
        $container = $this->getDataFromObjectParam($object);
        if ($container instanceof Object\Objectbrick) {
            $container->save($object);
        }
    }

    /**
     * @param $object
     * @param array $params
     * @return null
     */
    public function load($object, $params = array())
    {
        $classname = "\\Pimcore\\Model\\Object\\" . ucfirst($object->getClass()->getName()) . "\\" . ucfirst($this->getName());

        if(Tool::classExists($classname)) {
            $container = new $classname($object, $this->getName());
            $container->load($object);

            return $container;
        } else {
            return null;
        }
    }

    /**
     * @param $object
     */
    public function delete($object)
    {
        $container = $this->load($object);
        if($container) {
            $container->delete($object);
        }
    }

    /**
     * @return array
     */
    public function getAllowedTypes()
    {

        return $this->allowedTypes;
    }

    /**
     * @param $allowedTypes
     * @return $this
     */
    public function setAllowedTypes($allowedTypes)
    {
        if (is_string($allowedTypes)) {
            $allowedTypes = explode(",", $allowedTypes);
        }

        if (is_array($allowedTypes)) {
            for ($i = 0; $i < count($allowedTypes); $i++) {
                try {
                    Object\Objectbrick\Definition::getByKey($allowedTypes[$i]);
                } catch (\Exception $e) {

                    \Logger::warn("Removed unknown allowed type [ $allowedTypes[$i] ] from allowed types of object brick");
                    unset($allowedTypes[$i]);
                }
            }
        }

        $this->allowedTypes = (array)$allowedTypes;
        return $this;
    }

    /**
     * @param Model\Object\AbstractObject $object
     * @return mixed
     */
    public function getForWebserviceExport($object)
    {
        $data = $this->getDataFromObjectParam($object);
        $wsData = array();

        if ($data instanceof Object\Objectbrick) {
            foreach ($data as $item) {


                if (!$item instanceof Object\Objectbrick\Data\AbstractData) {
                    continue;
                }

                $wsDataItem = new Webservice\Data\Object\Element();
                $wsDataItem->value = array();
                $wsDataItem->type = $item->getType();

                try {
                    $collectionDef = Object\Objectbrick\Definition::getByKey($item->getType());
                } catch (\Exception $e) {
                    continue;
                }

                foreach ($collectionDef->getFieldDefinitions() as $fd) {
                    $el = new Webservice\Data\Object\Element();
                    $el->name = $fd->getName();
                    $el->type = $fd->getFieldType();
                    $el->value = $fd->getForWebserviceExport($item);
                    if ($el->value ==  null && self::$dropNullValues) {
                        continue;
                    }

                    $wsDataItem->value[] = $el;

                }

                $wsData[] = $wsDataItem;
            }

        }

        return $wsData;


    }

    /**
     * @param mixed $data
     * @param null $relatedObject
     * @param null $idMapper
     * @return mixed
     * @throws \Exception
     */
    public function getFromWebserviceImport($data, $relatedObject, $idMapper = null)
    {
        $containerName = "\\Pimcore\\Model\\Object\\" . ucfirst($relatedObject->getClass()->getName()) . "\\" . ucfirst($this->getName());
        $container = new $containerName($relatedObject, $this->getName());

        if (is_array($data)) {
            foreach ($data as $collectionRaw) {
                if ($collectionRaw instanceof \stdClass) {
                    $class = "\\Pimcore\\Model\\Webservice\\Data\\Object\\Element";
                    $collectionRaw = Tool\Cast::castToClass($class, $collectionRaw);
                }

                if($collectionRaw != null) {
                    if (!$collectionRaw instanceof Webservice\Data\Object\Element) {

                        throw new \Exception("invalid data in objectbrick [" . $this->getName() . "]");
                    }

                    $brick = $collectionRaw->type;
                    $collectionData = array();
                    $collectionDef = Object\Objectbrick\Definition::getByKey($brick);

                    if (!$collectionDef) {
                        throw new \Exception("Unknown objectbrick in webservice import [" . $brick . "]");
                    }

                    foreach ($collectionDef->getFieldDefinitions() as $fd) {
                        foreach ($collectionRaw->value as $field) {
                            if ($field instanceof \stdClass) {
                                $class = "\\Pimcore\\Model\\Webservice\\Data\\Object\\Element";
                                $field = Tool\Cast::castToClass($class, $field);
                            }
                            if (!$field instanceof Webservice\Data\Object\Element) {
                                throw new \Exception("invalid data in objectbricks [" . $this->getName() . "]");
                            } else if ($field->name == $fd->getName()) {

                                if ($field->type != $fd->getFieldType()) {
                                    throw new \Exception("Type mismatch for objectbricks field [" . $field->name . "]. Should be [" . $fd->getFieldType() . "] but is [" . $field->type . "]");
                                }
                                $collectionData[$fd->getName()] = $fd->getFromWebserviceImport($field->value, $relatedObject, $idMapper);
                                break;
                            }
                        }
                    }

                    $collectionClass = "\\Pimcore\\Model\\Object\\Objectbrick\\Data\\" . ucfirst($brick);
                    $collection = new $collectionClass($relatedObject);
                    $collection->setValues($collectionData);
                    $collection->setFieldname($this->getName());

                    $setter = "set" . ucfirst($brick);

                    $container->$setter($collection);
                }

            }
        }

        return $container;

    }

    /**
     * @param $object
     * @param $value
     * @param array $params
     * @return mixed
     */
    public function preSetData($object, $value, $params = array())
    {
        if ($value instanceof Object\Objectbrick) {
            $value->setFieldname($this->getName());
        }
        return $value;
    }


    /**
     * @param $data
     * @return array
     */
    public function resolveDependencies($data)
    {
        $dependencies = array();

        if ($data instanceof Object\Objectbrick) {
            $items = $data->getItems();
            foreach ($items as $item) {
                if (!$item instanceof Object\Objectbrick\Data\AbstractData) {
                    continue;
                }

                try {
                    $collectionDef = Object\Objectbrick\Definition::getByKey($item->getType());
                } catch (\Exception $e) {
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
     * This is a dummy and is mostly implemented by relation types
     *
     * @param mixed $data
     * @param array $tags
     * @return array
     */
    public function getCacheTags($data, $tags = array())
    {
        $tags = is_array($tags) ? $tags : array();

        if ($data instanceof Object\Objectbrick) {
            $items = $data->getItems();
            foreach ($items as $item) {

                if (!$item instanceof Object\Objectbrick\Data\AbstractData) {
                    continue;
                }

                try {
                    $collectionDef = Object\Objectbrick\Definition::getByKey($item->getType());
                } catch (\Exception $e) {
                    continue;
                }

                foreach ($collectionDef->getFieldDefinitions() as $fd) {
                    $key = $fd->getName();
                    $getter = "get" . ucfirst($key);
                    $tags = $fd->getCacheTags($item->$getter(), $tags);
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

        $classname = "\\Pimcore\\Model\\Object\\" . ucfirst($class->getName()) . "\\" . ucfirst($this->getName());

        $code .= "\t\t" . 'if(\Pimcore\Tool::classExists("' . $classname . '")) { ' . "\n";
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
        $code .= "\t" . 'if($preValue !== null && !\Pimcore::inAdmin()) { return $preValue;}' . "\n";

        $code .= "\t return " . '$data' . ";\n";
        $code .= "}\n\n";

        return $code;
    }


    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false) {
        if(!$omitMandatoryCheck){
            if ($data instanceof Object\Objectbrick) {
                $items = $data->getItems();
                foreach ($items as $item) {
                    if($item->getDoDelete()) {
                        continue;
                    }

                    if (!$item instanceof Object\Objectbrick\Data\AbstractData) {
                        continue;
                    }

                    try {
                        $collectionDef = Object\Objectbrick\Definition::getByKey($item->getType());
                    } catch (\Exception $e) {
                        continue;
                    }

                    //needed when new brick is added but not saved yet - then validity check fails.
                    if(!$item->getFieldname()) {
                        $item->setFieldname($data->getFieldname());
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
     * @param Object\Concrete $object
     * @return string
     */
    public function getDataForGrid($data, $object = null) {
        return "NOT SUPPORTED";
    }


    private function getDiffDataForField($item, $key, $fielddefinition, $level, $baseObject, $getter, $objectFromVersion) {
        $valueGetter = "get" . ucfirst($key);

        $value = $fielddefinition->getDiffDataForEditmode($item->$valueGetter(), $baseObject);
        return $value;
    }

    /**
     * @param $data
     * @param $getter
     * @param $objectFromVersion
     * @param int $level
     * @return array
     */
    private function doGetDiffDataForEditmode($data, $getter, $objectFromVersion, $level = 0) {
        $parent = Object\Service::hasInheritableParentObject($data->getObject());
        $item = $data->$getter();

        if(!$item && !empty($parent)) {
            $data = $parent->{"get" . ucfirst($this->getName())}();
            return $this->doGetDiffDataForEditmode($data, $getter, $objectFromVersion, $level + 1);
        }

        if (!$item instanceof Object\Objectbrick\Data\AbstractData) {
            return null;
        }

        try {
            $collectionDef = Object\Objectbrick\Definition::getByKey($item->getType());
        } catch (\Exception $e) {
            return null;
        }

        $result = array();

        foreach ($collectionDef->getFieldDefinitions() as $fd) {
            $fieldData = $this->getDiffDataForField($item, $fd->getName(), $fd, $level, $data->getObject(), $getter, $objectFromVersion); //$fd->getDataForEditmode($item->{$fd->getName()});

            $diffdata = array();

            foreach ($fieldData as $subdata) {
                $diffdata["field"] = $this->getName();
                $diffdata["key"] = $this->getName() . "~" . $fd->getName();
                $diffdata["value"] = $subdata["value"];
                $diffdata["type"] = $subdata["type"];
                $diffdata["disabled"] = $subdata["disabled"];

                // this is not needed anymoe
                unset($subdata["type"]);
                unset($subdata["value"]);

                $diffdata["title"] = $this->getName() . " / " . $subdata["title"];
                $brickdata = array(
                    "brick" => substr($getter, 3),
                    "name" => $fd->getName(),
                    "subdata" => $subdata
                );
                $diffdata["data"] = $brickdata;
            }

            $result[] = $diffdata;
        }

        return $result;
    }

    /** See parent class.
     * @param mixed $data
     * @param null $object
     * @return array|null
     */
    public function getDiffDataForEditMode($data, $object = null, $objectFromVersion = null)
    {
        $editmodeData = array();

        if ($data instanceof Object\Objectbrick) {
            $getters = $data->getBrickGetters();

            foreach ($getters as $getter) {
                $brickdata = $this->doGetDiffDataForEditmode($data, $getter, $objectFromVersion);
                if ($brickdata) {
                    foreach ($brickdata as $item) {
                        $editmodeData[] = $item;
                    }
                }
            }
        }

        return $editmodeData;
    }

    /** See parent class.
     * @param $data
     * @param null $object
     * @return null|\Pimcore\Date
     */

    public function getDiffDataFromEditmode($data, $object = null) {

        $valueGetter = "get" . ucfirst($this->getName());
        $valueSetter = "set" . ucfirst($this->getName());
        $brickdata = $object->$valueGetter();

        foreach ($data as $item) {
            $subdata = $item["data"];
            if (!$subdata) {
                continue;
            }
            $brickname = $subdata["brick"];

            $getter = "get" . ucfirst($brickname);
            $setter = "set" . ucfirst($brickname);

            $brick = $brickdata->$getter();
            if (!$brick) {
                // brick must be added to object
                $brickClass = "\\Pimcore\\Model\\Object\\Objectbrick\\Data\\" . ucfirst($brickname);
                $brick = new $brickClass($object);
            }

            $fieldname = $subdata["name"];
            $fielddata = array($subdata["subdata"]);

            $collectionDef = Object\Objectbrick\Definition::getByKey($brickname);

            $fd = $collectionDef->getFieldDefinition($fieldname);
            if ($fd && $fd->isDiffChangeAllowed()) {
                $value = $fd->getDiffDataFromEditmode($fielddata, $object);
                $brick->setValue($fieldname, $value);

                $brickdata->$setter($brick);
            }

            $object->$valueSetter($brickdata);
        }
        return $brickdata;
    }

    /** True if change is allowed in edit mode.
     * @return bool
     */
    public function isDiffChangeAllowed() {
        return true;
    }

    /**
     * Rewrites id from source to target, $idMapping contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     * @param mixed $object
     * @param array $idMapping
     * @param array $params
     * @return Element\ElementInterface
     */
    public function rewriteIds($object, $idMapping, $params = array()) {
        $data = $this->getDataFromObjectParam($object, $params);

        if ($data instanceof Object\Objectbrick) {
            $items = $data->getItems();
            foreach ($items as $item) {
                if (!$item instanceof Object\Objectbrick\Data\AbstractData) {
                    continue;
                }

                try {
                    $collectionDef = Object\Objectbrick\Definition::getByKey($item->getType());
                } catch (\Exception $e) {
                    continue;
                }

                foreach ($collectionDef->getFieldDefinitions() as $fd) {
                    if(method_exists($fd, "rewriteIds")) {
                        $d = $fd->rewriteIds($item, $idMapping, $params);
                        $setter = "set" . ucfirst($fd->getName());
                        $item->$setter($d);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @param Object\ClassDefinition\Data $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Object\ClassDefinition\Data $masterDefinition) {
        $this->allowedTypes = $masterDefinition->allowedTypes;
    }
}
