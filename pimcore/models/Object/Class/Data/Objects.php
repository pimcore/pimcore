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

class Object_Class_Data_Objects extends Object_Class_Data_Relations_Abstract {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "objects";

    /**
     * @var integer
     */
    public $width;

    /**
     * Type for the column to query
     *
     * @var integer
     */
    public $height;

    /**
     * @var int
     */
    public $maxItems;

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = "text";

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "array";


    /**
     * @var boolean
     */
    public $relationType = true;


    /**
     * @return boolean
     */
    public function getObjectsAllowed() {
        return true;
    }

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
            foreach ($data as $object) {
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
                $o = Object_Abstract::getById($object["dest_id"]);
                if ($o instanceof Object_Concrete) {
                    $objects[] = $o;
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
            foreach ($data as $object) {
                if ($object instanceof Object_Concrete) {
                    $ids[] = $object->geto_id();
                }
            }
            return "," . implode(",", $ids) . ",";
        } else if (is_array($data) && count($data) === 0){
            return "";
        } else {
            throw new Exception("invalid data passed to getDataForQueryResource - must be array and it is: " . print_r($data, true));
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

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $object) {
                if ($object instanceof Object_Concrete) {
                    $return[] = array($object->geto_id(), $object->getO_FullPath(), $object->geto_className());
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

        $objects = array();
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $object) {

                $o = Object_Abstract::getById($object["id"]);
                if($o) {
                    $objects[]=$o;
                }
            }   
        }
        //must return array if data shall be set
        return $objects;
    }

    public function getDataForGrid($data, $object = null) {
        if (is_array($data)) {
            $pathes = array();
            foreach ($data as $eo) {
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
            foreach ($data as $o) {
                if($o instanceof Element_Interface) {
                    $pathes[] = $o->geto_FullPath();
                }
            }
            return implode("<br />", $pathes);
        }
    }

    /**
     * @return integer
     */
    public function getWidth() {
        return $this->width;
    }

    /**
     * @param integer $width
     * @return void
     */
    public function setWidth($width) {
        $this->width = $this->getAsIntegerCast($width);
    }

    /**
     * @return integer
     */
    public function getHeight() {
        return $this->height;
    }

    /**
     * @param integer $height
     * @return void
     */
    public function setHeight($height) {
        $this->height = $this->getAsIntegerCast($height);
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
            foreach ($data as $o) {
                if(empty($o)) {
                    continue;
                }
                
                $allowClass = $this->allowObjectRelation($o);
                if (!$allowClass or !($o instanceof Object_Concrete)) {
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
            foreach ($data as $eo) {
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
                $value[] = $el;
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
            foreach ($data as $object) {
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
			foreach ($data as $o) {
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
            foreach ($data as $eo) {
                if ($eo instanceof Element_Interface) {
                    $items[] = array(
                        "type" => $eo->getType(),
                        "id" => $eo->getId()
                    );
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
                $object = Object_Abstract::getById($item['id']);
                if($object instanceof Object_Abstract){
                    $objects[] = $object;
                } else {
                    throw new Exception("cannot get values from web service import - references unknown object with id [ ".$item['id']." ]");
                }
            }
        } else {
            throw new Exception("cannot get values from web service import - invalid data");
        }
        return $objects;
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
                if(Element_Service::isPublished($listElement)){
                    $publishedList[] = $listElement;
                }
            }
            return $publishedList;
        }
        return is_array($data) ? $data : array();
    }

    public function preSetData ($object, $data) {

        if($data === null) $data = array();

        if($this->getLazyLoading() and !in_array($this->getName(), $object->getO__loadedLazyFields())){
            $object->addO__loadedLazyField($this->getName());
        }
        return $data;
    }

    /**
     * @param string $fieldtype
     */
    public function setFieldtype($fieldtype)
    {
        $this->fieldtype = $fieldtype;
    }

    /**
     * @return string
     */
    public function getFieldtype()
    {
        return $this->fieldtype;
    }

    /**
     * @param int $maxItems
     */
    public function setMaxItems($maxItems)
    {
        $this->maxItems = $this->getAsIntegerCast($maxItems);
    }

    /**
     * @return int
     */
    public function getMaxItems()
    {
        return $this->maxItems;
    }
}
