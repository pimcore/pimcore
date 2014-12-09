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
use Pimcore\Model\Element;
use Pimcore\Tool;
use Pimcore\Resource;

class ObjectsMetadata extends Model\Object\ClassDefinition\Data\Objects {

    /**
     * @var
     */
    public $allowedClassId;

    /**
     * @var
     */
    public $visibleFields;

    /**
     * @var
     */
    public $columns;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "objectsMetadata";

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "\\Pimcore\\Model\\Object\\Data\\ObjectMetadata[]";

    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     * @param array $data
     * @param null|Model\Object\AbstractObject $object
     * @return array
     */
    public function getDataForResource($data, $object = null) {

        $return = array();

        if (is_array($data) && count($data) > 0) {
            $counter = 1;
            foreach ($data as $metaObject) {
                $object = $metaObject->getObject();
                if ($object instanceof Object\Concrete) {
                    $return[] = array(
                        "dest_id" => $object->getId(),
                        "type" => "object",
                        "fieldname" => $this->getName(),
                        "index" => $counter
                    );
                }
                $counter++;
            }
            return $return;
        } else if (is_array($data) and count($data)===0) {
            //give empty array if data was not null
            return array();
        } else {
            //return null if data was null - this indicates data was not loaded
            return null;
        }


    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     * @param array $data
     * @return array
     */
    public function getDataFromResource($data) {
        $objects = array();

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $object) {
                $source = Object::getById($object["src_id"]);
                $destination = Object::getById($object["dest_id"]);

                if ($source instanceof Object\Concrete && $destination instanceof Object\Concrete && $destination->getClassId() == $this->getAllowedClassId()) {
                    $className = Tool::getModelClassMapping('\Pimcore\Model\Object\Data\ObjectMetadata'); // the name for the class mapping is still with underscores
                    $metaData = new $className($this->getName(), $this->getColumnKeys(), $destination);

                    $ownertype = $object["ownertype"] ? $object["ownertype"] : "";
                    $ownername = $object["ownername"] ? $object["ownername"] : "";
                    $position = $object["position"] ? $object["position"] : "0";

                    $metaData->load($source, $destination, $this->getName(), $ownertype, $ownername, $position);
                    $objects[] = $metaData;
                }
            }
        }
        //must return array - otherwise this means data is not loaded
        return $objects;
    }

    /**
     * @param $data
     * @param null $object
     * @throws \Exception
     */
    public function getDataForQueryResource($data, $object = null) {

        //return null when data is not set
        if(!$data) return null;

        $ids = array();

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaObject) {
                $object = $metaObject->getObject();
                if ($object instanceof Object\Concrete) {
                    $ids[] = $object->getId();
                }
            }
            return "," . implode(",", $ids) . ",";
        } else if (is_array($data) && count($data) === 0){
            return "";
        } else {
            throw new \Exception("invalid data passed to getDataForQueryResource - must be array");
        }
    }


    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     * @param array $data
     * @param null|Model\Object\AbstractObject $object
     * @return array
     */
    public function getDataForEditmode($data, $object = null) {
        $return = array();

        $visibleFieldsArray = explode(",", $this->getVisibleFields());

        $gridFields = (array)$visibleFieldsArray;

        // add data
        if (is_array($data) && count($data) > 0) {

            foreach ($data as $metaObject) {

                $object = $metaObject->getObject();
                if ($object instanceof Object\Concrete) {

                    $columnData = Object\Service::gridObjectData($object, $gridFields);
                    foreach($this->getColumns() as $c) {
                        $getter = "get" . ucfirst($c['key']);
                        $columnData[$c['key']] = $metaObject->$getter();
                    }
                    $return[] = $columnData;
                }
            }

        }

        return $return;
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     * @param array $data
     * @param null|Model\Object\AbstractObject $object
     * @return array
     */
    public function getDataFromEditmode($data, $object = null) {
        //if not set, return null
        if($data === null or $data === FALSE){ return null; }

        $objectsMetadata = array();
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $object) {

                $o = Object::getById($object["id"]);
                if($o && $o->getClassId() == $this->getAllowedClassId()) {
                    $className = Tool::getModelClassMapping('\Pimcore\Model\Object\Data\ObjectMetadata');
                    $metaData = new $className($this->getName(), $this->getColumnKeys(), $o);
                    foreach($this->getColumns() as $c) {
                        $setter = "set" . ucfirst($c["key"]);
                        $metaData->$setter($object[$c["key"]]);
                    }
                    $objectsMetadata[] = $metaData;
                }
            }
        }

        //must return array if data shall be set
        return $objectsMetadata;
    }

    /**
     * @param $data
     * @param null $object
     * @return array
     */
    public function getDataForGrid($data, $object = null) {
        if (is_array($data)) {
            $pathes = array();
            foreach ($data as $metaObject) {
                $eo = $metaObject->getObject();
                if ($eo instanceof Element\ElementInterface) {
                    $pathes[] = $eo->getFullPath();
                }
            }
            return $pathes;
        }
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     * @param array $data
     * @return string
     */
    public function getVersionPreview($data) {

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaObject) {
                $o = $metaObject->getObject();
                $pathes[] = $o->getFullPath();
            }
            return implode("<br />", $pathes);
        }
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false){

        if(!$omitMandatoryCheck and $this->getMandatory() and empty($data)){
            throw new \Exception("Empty mandatory field [ ".$this->getName()." ]");
        }

        if (is_array($data)) {
            foreach ($data as $objectMetadata) {
                if (!($objectMetadata instanceof Object\Data\ObjectMetadata)) {
                    throw new \Exception("Expected Object\\Data\\ObjectMetadata");
                }

                $o = $objectMetadata->getObject();
                if ($o->getClassId() != $this->getAllowedClassId() || !($o instanceof Object\Concrete)) {
                    if($o instanceof Object\Concrete){
                        $id = $o->getId();
                    } else {
                        $id = "??";
                    }
                    throw new \Exception("Invalid object relation to object [".$id."] in field " . $this->getName(), null, null);
                }
            }
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param Model\Object\AbstractObject $object
     * @return string
     */
    public function getForCsvExport($object) {
        $data = $this->getDataFromObjectParam($object);
        if (is_array($data)) {
            $paths = array();
            foreach ($data as $metaObject) {
                $eo = $metaObject->getObject();
                if ($eo instanceof Element\ElementInterface) {
                    $paths[] = $eo->getFullPath();
                }
            }
            return implode(",", $paths);
        } else return null;
    }

    /**
     * @param $importValue
     * @return array|mixed
     */
    public function getFromCsvImport($importValue) {
        $values = explode(",", $importValue);

        $value = array();
        foreach ($values as $element) {
            if ($el = Object::getByPath($element)) {
                $className = Tool::getModelClassMapping('\Pimcore\Model\Object\Data\ObjectMetadata');
                $metaObject = new $className($this->getName(), $this->getColumnKeys(), $el);

                $value[] = $metaObject;
            }
        }
        return $value;
    }


    /**
     * This is a dummy and is mostly implemented by relation types
     *
     * @param mixed $data
     * @param array $tags
     * @return array
     */
    public function getCacheTags ($data, $tags = array()) {

        $tags = is_array($tags) ? $tags : array();

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaObject) {
                $object = $metaObject->getObject();
                if ($object instanceof Element\ElementInterface && !array_key_exists($object->getCacheTag(), $tags)) {
                    $tags = $object->getCacheTags($tags);
                }
            }
        }
        return $tags;
    }

    /**
     * @param $data
     * @return array
     */
    public function resolveDependencies ($data) {

        $dependencies = array();

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaObject) {
                $o = $metaObject->getObject();
                if ($o instanceof Object\AbstractObject) {
                    $dependencies["object_" . $o->getId()] = array(
                        "id" => $o->getId(),
                        "type" => "object"
                    );
                }
            }
        }
        return $dependencies;
    }

    /**
     * @param Object\AbstractObject $object
     * @return array|mixed|null
     */
    public function getForWebserviceExport ($object) {

        $data = $this->getDataFromObjectParam($object);
        if (is_array($data)) {
            $items = array();
            foreach ($data as $metaObject) {
                $eo = $metaObject->getObject();
                if ($eo instanceof Element\ElementInterface) {
                    $item = array();
                    $item["type"] = $eo->getType();
                    $item["id"] = $eo->getId();

                    foreach($this->getColumns() as $c) {
                        $getter = "get" . ucfirst($c['key']);
                        $item[$c['key']] = $metaObject->$getter();
                    }
                    $items[] = $item;
                }
            }
            return $items;
        } else return null;
    }


    /**
     * @param mixed $value
     * @param null $object
     * @param null $idMapper
     * @return array|mixed
     * @throws \Exception
     */
    public function getFromWebserviceImport ($value, $object = null, $idMapper = null) {
        $objects = array();
        if(empty($value)){
            return null;
        } else if(is_array($value)){
            foreach($value as $key => $item){
                $item = (array) $item;
                $id = $item['id'];

                if ($idMapper) {
                    $id = $idMapper->getMappedId("object", $id);
                }

                if ($id) {
                    $dest = Object::getById($id);
                }

                if($dest instanceof Object\AbstractObject) {

                    $className = Tool::getModelClassMapping('\Pimcore\Model\Object\Data\ObjectMetadata');
                    $metaObject = new $className($this->getName(), $this->getColumnKeys(), $dest);

                    foreach($this->getColumns() as $c) {
                        $setter = "set" . ucfirst($c['key']);
                        $metaObject->$setter($item[$c['key']]);
                    }

                    $objects[] = $metaObject;
                } else {
                    if (!$idMapper || !$idMapper->ignoreMappingFailures()) {
                        throw new \Exception("cannot get values from web service import - references unknown object with id [ ".$item['id']." ]");
                    } else {
                        $idMapper->recordMappingFailure("object", $object->getId(), "object", $item['id']);
                    }

                }
            }
        } else {
            throw new \Exception("cannot get values from web service import - invalid data");
        }
        return $objects;
    }



    /**
     * @param Object\Concrete $object
     * @return void
     */
    public function save($object, $params = array()) {

        $objectsMetadata = $this->getDataFromObjectParam($object, $params);

        $classId = null;
        $objectId = null;

        if($object instanceof Object\Concrete) {
            $objectId = $object->getId();
        } else if($object instanceof Object\Fieldcollection\Data\AbstractData) {
            $objectId = $object->getObject()->getId();
        } else if ($object instanceof Object\Localizedfield) {
            $objectId = $object->getObject()->getId();
        } else if ($object instanceof Object\Objectbrick\Data\AbstractData) {
            $objectId = $object->getObject()->getId();
        }

        if ($object instanceof Object\Localizedfield) {
            $classId = $object->getClass()->getId();
        } else if ($object instanceof Object\Objectbrick\Data\AbstractData || $object instanceof Object\Fieldcollection\Data\AbstractData) {
            $classId = $object->getObject()->getClassId();
        } else {
            $classId = $object->getClassId();
        }

        $table = "object_metadata_" . $classId;
        $db = Resource::get();

        $this->enrichRelation($object, $params, $classId, $relation);

        $position = (isset($relation["position"]) && $relation["position"]) ? $relation["position"] : "0";

        $sql = $db->quoteInto("o_id = ?", $objectId) . " AND " . $db->quoteInto("fieldname = ?", $this->getName())
            . " AND " . $db->quoteInto("position = ?", $position);



        $db->delete($table, $sql);

        if(!empty($objectsMetadata)) {

            if ($object instanceof Object\Localizedfield || $object instanceof Object\Objectbrick\Data\AbstractData
                || $object instanceof Object\Fieldcollection\Data\AbstractData) {
                $objectConcrete = $object->getObject();
            } else {
                $objectConcrete = $object;
            }

            foreach($objectsMetadata as $meta) {
                $ownerName = isset($relation["ownername"]) ? $relation["ownername"] : null;
                $ownerType = isset($relation["ownertype"]) ? $relation["ownertype"] : null;
                $meta->save($objectConcrete, $ownerType, $ownerName, $position);
            }
        }

        parent::save($object, $params);
    }

    public function preGetData ($object, $params = array()) {

        $data = null;
        if($object instanceof Object\Concrete) {
            $data = $object->{$this->getName()};
            if($this->getLazyLoading() and !in_array($this->getName(), $object->getO__loadedLazyFields())){
                //$data = $this->getDataFromResource($object->getRelationData($this->getName(),true,null));
                $data = $this->load($object, array("force" => true));

                $setter = "set" . ucfirst($this->getName());
                if(method_exists($object, $setter)) {
                    $object->$setter($data);
                }
            }
        } else if ($object instanceof Object\Localizedfield) {
            $data = $params["data"];
        } else if ($object instanceof Object\Fieldcollection\Data\AbstractData) {
            $data = $object->{$this->getName()};
        } else if ($object instanceof Object\Objectbrick\Data\AbstractData) {
            $data = $object->{$this->getName()};
        }

        if(Object\AbstractObject::doHideUnpublished() and is_array($data)) {
            $publishedList = array();
            foreach($data as $listElement){

                if(Element\Service::isPublished($listElement->getObject())){
                    $publishedList[] = $listElement;
                }
            }
            return $publishedList;
        }

        return $data;
    }

    /**
     * @param Object\Concrete $object
     * @return void
     */
    public function delete($object) {
        $db = Resource::get();
        $db->delete("object_metadata_" . $object->getClassId(), $db->quoteInto("o_id = ?", $object->getId()) . " AND " . $db->quoteInto("fieldname = ?", $this->getName()));
    }

    /**
     * @param $allowedClassId
     * @return $this
     */
    public function setAllowedClassId($allowedClassId) {
        $this->allowedClassId = $allowedClassId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAllowedClassId() {
        return $this->allowedClassId;
    }

    /**
     * @param $visibleFields
     * @return $this
     */
    public function setVisibleFields($visibleFields) {
        $this->visibleFields = $visibleFields;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getVisibleFields() {
        return $this->visibleFields;
    }

    /**
     * @param $columns
     * @return $this
     */
    public function setColumns($columns) {
        if(isset($columns['key'])) {
            $columns = array($columns);
        }
        usort($columns, array($this, 'sort'));

        $this->columns = array();
        $this->columnKeys = array();
        foreach($columns as $c) {
            $c['key'] = strtolower($c['key']);
            $this->columns[] = $c;
            $this->columnKeys[] = $c['key'];
        }
        return $this;
    }

    /**
     * @return mixed
     */
    public function getColumns() {
        return $this->columns;
    }

    /**
     * @return array
     */
    public function getColumnKeys() {
        $this->columnKeys = array();
        foreach($this->columns as $c) {
            $this->columnKeys[] = $c['key'];
        }
        return $this->columnKeys;
    }

    /**
     * @param $a
     * @param $b
     * @return int
     */
    public function sort($a, $b) {
        if(is_array($a) && is_array($b)) {
            return $a['position'] - $b['position'];
        }
        return strcmp($a, $b);
    }

    /**
     * @return void
     */
    public function classSaved($class) {
        $className = Tool::getModelClassMapping('\Pimcore\Model\Object\Data\ObjectMetadata');
        $temp = new $className(null);
        $temp->getResource()->createOrUpdateTable($class);
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

        if (is_array($data)) {
            foreach ($data as &$metaObject) {
                $eo = $metaObject->getObject();
                if ($eo instanceof Element\ElementInterface) {
                    $id = $eo->getId();
                    $type = Element\Service::getElementType($eo);

                    if(array_key_exists($type, $idMapping) && array_key_exists($id, $idMapping[$type])) {
                        $newElement = Element\Service::getElementById($type, $idMapping[$type][$id]);
                        $metaObject->setObject($newElement);
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
        $this->allowedClassId = $masterDefinition->allowedClassId;
        $this->visibleFields = $masterDefinition->visibleFields;
        $this->columns = $masterDefinition->columns;
    }

    /**
     *
     */
    public function enrichLayoutDefinition() {
        $classId = $this->allowedClassId;
        $class = Object\ClassDefinition::getById($classId);

        if (!$classId) {
            return;
        }

        if (!$this->visibleFields) {
            return;
        }

        $this->visibleFieldDefinitions = array();

        $t = \Zend_Registry::get("Zend_Translate");

        $visibleFields = explode(',', $this->visibleFields);
        foreach ($visibleFields as $field) {
            $fd = $class->getFieldDefinition($field);

            if (!$fd) {
                $fieldFound = false;
                if($localizedfields = $class->getFieldDefinitions()['localizedfields']) {
                    if($fd = $localizedfields->getFieldDefinition($field)) {
                        $this->visibleFieldDefinitions[$field]["name"] = $fd->getName();
                        $this->visibleFieldDefinitions[$field]["title"] = $fd->getTitle();
                        $this->visibleFieldDefinitions[$field]["fieldtype"] = $fd->getFieldType();

                        if ($fd instanceof Object\ClassDefinition\Data\Select) {
                            $this->visibleFieldDefinitions[$field]["options"] = $fd->getOptions();
                        }

                        $fieldFound = true;
                    }
                }

                if (!$fieldFound) {
                    $this->visibleFieldDefinitions[$field]["name"] = $field;
                    $this->visibleFieldDefinitions[$field]["title"] = $t->translate($field);
                    $this->visibleFieldDefinitions[$field]["fieldtype"] = "input";
                }

            } else {
                $this->visibleFieldDefinitions[$field]["name"] = $fd->getName();
                $this->visibleFieldDefinitions[$field]["title"] = $fd->getTitle();
                $this->visibleFieldDefinitions[$field]["fieldtype"] = $fd->getFieldType();
                $this->visibleFieldDefinitions[$field]["noteditable"] = true;

                if ($fd instanceof Object\ClassDefinition\Data\Select) {
                    $this->visibleFieldDefinitions[$field]["options"] = $fd->getOptions();
                }
            }
        }
    }
}
