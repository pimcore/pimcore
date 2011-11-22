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

class Object_Class_Data_ObjectsMetadata extends Object_Class_Data_Objects {


    public $allowedClassId;
    public $visibleFields;
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
    public $phpdocType = "Object_Data_ObjectMetadata[]";

    /**
     * @see Object_Class_Data::getDataForResource
     * @param array $data
     * @param null|Object_Abstract $object
     * @return array
     */
    public function getDataForResource($data, $object = null) {

        $return = array();

        if (is_array($data) && count($data) > 0) {
            $counter = 1;
            foreach ($data as $metaObject) {
                $object = $metaObject->getObject();
                if ($object instanceof Object_Concrete) {
                    $return[] = array(
                        "dest_id" => $object->getO_id(),
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
     * @see Object_Class_Data::getDataFromResource
     * @param array $data
     * @return array
     */
    public function getDataFromResource($data) {
        $objects = array();

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $object) {
                $source = Object_Abstract::getById($object["src_id"]);
                $destination = Object_Abstract::getById($object["dest_id"]);

                if ($source instanceof Object_Concrete && $destination instanceof Object_Concrete) {
                    $metaData = new Object_Data_ObjectMetadata($this->getName(), $this->getColumnKeys(), $destination);
                    $metaData->load($source, $destination, $this->getName());
                    $objects[] = $metaData;
                }
            }
        }
        //must return array - otherwise this means data is not loaded
        return $objects;
    }

    /**
     * @see Object_Class_Data::getDataForQueryResource
     * @param array $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataForQueryResource($data, $object = null) {

        //return null when data is not set
        if(!$data) return null;

        $ids = array();

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaObject) {
                $object = $metaObject->getObject();
                if ($object instanceof Object_Concrete) {
                    $ids[] = $object->geto_id();
                }
            }
            return "," . implode(",", $ids) . ",";
        } else if (is_array($data) && count($data) === 0){
            return "";
        } else {
            throw new Exception("invalid data passed to getDataForQueryResource - must be array");
        }
    }


    /**
     * @see Object_Class_Data::getDataForEditmode
     * @param array $data
     * @param null|Object_Abstract $object
     * @return array
     */
    public function getDataForEditmode($data, $object = null) {
        $return = array();
        $visibleFieldsArray = explode(",", $this->getVisibleFields());
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaObject) {

                $object = $metaObject->getObject();
                if ($object instanceof Object_Concrete) {

                    $value = array($object->geto_id());
                    foreach($visibleFieldsArray as $v) {
                        $getter = "get" . ucfirst($v);
                        if(method_exists($object, $getter)) {
                            $v = $object->$getter();
                            if(is_object($v)) {
                                if($v instanceof Zend_Date) {
                                    $v = $v->get(Zend_Date::DATE_LONG);
                                } else {
                                    $v = (string)$v;
                                }

                            }

                            $value[] = $v;
                        }
                    }
                    foreach($this->getColumns() as $c) {
                        $getter = "get" . ucfirst($c['key']);
                        $value[] = $metaObject->$getter();
                    }
                    $return[] = $value;
                }
            }
            if (empty ($return)) {
                $return = false;
            }
            return $return;
        }

        return false;
    }

    /**
     * @see Object_Class_Data::getDataFromEditmode
     * @param array $data
     * @param null|Object_Abstract $object
     * @return array
     */
    public function getDataFromEditmode($data, $object = null) {
        //if not set, return null
        if($data === null or $data === FALSE){ return null; }

        $objectsMetadata = array();
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $object) {

                $o = Object_Abstract::getById($object["id"]);
                if($o) {
                    $metaData = new Object_Data_ObjectMetadata($this->getName(), $this->getColumnKeys(), $o);
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

    public function getDataForGrid($data, $object = null) {
        if (is_array($data)) {
            $pathes = array();
            foreach ($data as $metaObject) {
                $eo = $metaObject->getObject();
                if ($eo instanceof Element_Interface) {
                    $pathes[] = $eo->getFullPath();
                }
            }
            return $pathes;
        }
    }

    /**
     * @see Object_Class_Data::getVersionPreview
     * @param array $data
     * @return string
     */
    public function getVersionPreview($data) {

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaObject) {
                $o = $metaObject->getObject();
                $pathes[] = $o->geto_FullPath();
            }
            return implode("<br />", $pathes);
        }
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false){

        if(!$omitMandatoryCheck and $this->getMandatory() and empty($data)){
            throw new Exception("Empty mandatory field [ ".$this->getName()." ]");
        }

        if (is_array($data)) {
            foreach ($data as $objectMetadata) {
                $o = $objectMetadata->getObject();

                $allowClass = $this->allowObjectRelation($o);
                if (!$allowClass or!($o instanceof Object_Concrete)) {
                    if(!$allowClass && $o instanceof Object_Concrete){
                        $id = $o->getId();
                    } else {
                        $id = "??";
                    }
                    throw new Exception("Invalid object relation to object [".$id."] in field " . $this->getName(), null, null);
                }
            }
        }
    }

     /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param Object_Abstract $object
     * @return string
     */
    public function getForCsvExport($object) {
        $key = $this->getName();
        $getter = "get".ucfirst($key);
        $data = $object->$getter();
        if (is_array($data)) {
            $paths = array();
            foreach ($data as $metaObject) {
                $eo = $metaObject->getObject();
                if ($eo instanceof Element_Interface) {
                    $paths[] = $eo->getFullPath();
                }
            }
            return implode(",", $paths);
        } else return null;
    }

    /**
     * fills object field data values from CSV Import String
     * @abstract
     * @param string $importValue
     * @param Object_Abstract $abstract
     * @return Object_Class_Data
     */
    public function getFromCsvImport($importValue) {
        $values = explode(",", $importValue);

        $value = array();
        foreach ($values as $element) {
            if ($el = Object_Abstract::getByPath($element)) {

                $metaObject = new Object_Data_ObjectMetadata($this->getName(), $this->getColumnKeys(), $el);

                $value[] = $metaObject;
            }
        }
        return $value;
    }


    /**
     * @param mixed $data
     * @param Object_Concrete $ownerObject
     * @param array $blockedTags
     */
    public function getCacheTags ($data, $ownerObject, $tags = array()) {

        $tags = is_array($tags) ? $tags : array();

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaObject) {
                $object = $metaObject->getObject();
                if (!array_key_exists($object->getCacheTag(), $tags)) {
                    if(!$ownerObject instanceof Element_Interface || $object->getId() != $ownerObject->getId()) {
                        $tags = $object->getCacheTags($tags);
                    }
                }
            }
        }
        return $tags;
    }

    /**
     * @param mixed $data
     */
    public function resolveDependencies ($data) {

        $dependencies = array();

        if (is_array($data) && count($data) > 0) {
			foreach ($data as $metaObject) {
                $o = $metaObject->getObject();
				if ($o instanceof Object_Abstract) {
					$dependencies["object_" . $o->getO_Id()] = array(
						"id" => $o->getO_Id(),
						"type" => "object"
					);
				}
			}
		}
        return $dependencies;
    }



    public function getForWebserviceExport ($object) {

        $key = $this->getName();
        $getter = "get".ucfirst($key);
        $data = $object->$getter();
        if (is_array($data)) {
            $items = array();
            foreach ($data as $metaObject) {
                $eo = $metaObject->getObject();
                if ($eo instanceof Element_Interface) {
                    $item = array();
                    $item["type"] = $eo->getType();
                    $item["id"] = $eo->getId();

                    foreach($this->getColumns() as $c) {
                        $getter = "get" . ucfirst($c['key']);
                        $item[$c['key']] = $metaObject->$getter();
                    }
                }
            }
            return $items;
        } else return null;
    }


    /**
     * converts data to be imported via webservices
     * @param mixed $value
     * @return mixed
     */
    public function getFromWebserviceImport ($value, $object = null) {
        $objects = array();
        if(empty($value)){
           return null;
        } else if(is_array($value)){
            foreach($value as $key => $item){
                $dest = Object_Abstract::getById($item['id']);
                if($dest instanceof Object_Abstract) {

                    $metaObject = new Object_Data_ObjectMetadata($this->getName(), $this->getColumnKeys(), $dest);

                    foreach($this->getColumns() as $c) {
                        $setter = "set" . ucfirst($c['key']);
                        $metaObject->$setter($item[$c['key']]);
                    }

                    $objects[] = $metaObject;
                } else {
                    throw new Exception("cannot get values from web service import - references unknown object with id [ ".$item['id']." ]");
                }
            }
        } else {
            throw new Exception("cannot get values from web service import - invalid data");
        }
        return $objects;
    }



    /**
     * @param Object_Concrete $object
     * @return void
     */
    public function save($object, $params = array()) {

        $getter = "get" . ucfirst($this->getName());
        $objectsMetadata = $object->$getter();

        $classId = null;
        $objectId = null;

        if($object instanceof Object_Concrete) {
            $objectId = $object->getId();
            $classId = $object->getClassId();
            $class = $object->getClass();
        } else if($object instanceof Object_Fieldcollection_Data_Abstract) {
            $objectId = $object->getObject()->getId();
            $classId = $object->getObject()->getClassId();
            $class = $object->getObject()->getClass();
        } else if ($object instanceof Object_Localizedfield) {
            $objectId = $object->getObject()->getId();
            $classId = $object->getObject()->getClassId();
            $class = $object->getObject()->getClass();
        } else if ($object instanceof Object_Objectbrick_Data_Abstract) {
            $objectId = $object->getObject()->getId();
            $classId = $object->getObject()->getClassId();
            $class = $object->getObject()->getClass();
        }

        $table = "object_metadata_" . $classId;
        $db = Pimcore_Resource::get();

        if(!empty($objectsMetadata)) {
            $objectsMetadata[0]->getResource()->createOrUpdateTable($class);
        }

        $db->delete($table, $db->quoteInto("o_id = ?", $objectId) . " AND " . $db->quoteInto("fieldname = ?", $this->getName()));

        if(!empty($objectsMetadata)) {
            foreach($objectsMetadata as $meta) {
                $meta->save($object);
            }
        }

        parent::save($object, $params);
    }

    public function preGetData ($object) {
        $data = $object->{$this->getName()};
        if($this->getLazyLoading() and !in_array($this->getName(), $object->getO__loadedLazyFields())){
            //$data = $this->getDataFromResource($object->getRelationData($this->getName(),true,null));
            $data = $this->load($object, array("force" => true));

            $setter = "set" . ucfirst($this->getName());
            if(method_exists($object, $setter)) {
                $object->$setter($data);
            }
        }
        if(Object_Abstract::doHideUnpublished() and is_array($data)) {
            $publishedList = array();
            foreach($data as $listElement){

                if(Element_Service::isPublished($listElement->getObject())){
                    $publishedList[] = $listElement;
                }
            }
            return $publishedList;
        }

        return $data;
    }

    /**
     * @param Object_Concrete $object
     * @return void
     */
    public function delete($object) {
        $db = Pimcore_Resource::get();
        $db->delete("object_metadata_" . $object->getO_classId(), $db->quoteInto("o_id = ?", $object->getId()) . " AND " . $db->quoteInto("fieldname = ?", $this->getName()));
    }

    public function setAllowedClassId($allowedClassId) {
        $this->allowedClassId = $allowedClassId;
    }

    public function getAllowedClassId() {
        return $this->allowedClassId;
    }

    public function setVisibleFields($visibleFields) {
        $this->visibleFields = $visibleFields;
    }

    public function getVisibleFields() {
        return $this->visibleFields;
    }

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
    }

    public function getColumns() {
        return $this->columns;
    }

    public function getColumnKeys() {
        $this->columnKeys = array();
        foreach($this->columns as $c) {
            $this->columnKeys[] = $c['key'];
        }
        return $this->columnKeys;
    }

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
        $temp = new Object_Data_ObjectMetadata(null);
        $temp->getResource()->createOrUpdateTable($class);
    }
}
