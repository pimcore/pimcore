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

class Object_Class_Data_Href extends Object_Class_Data_Relations_Abstract { 

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "href";

    /**
     * @var integer
     */
    public $width;

    /**
     * @var string
     */
    public $assetUploadPath;

    /**
     * @var bool
     */
    public $relationType = true;
    
    /**
     * Type for the column to query
     *
     * @var array
     */
    public $queryColumnType = array(
        "id" => "int(11)",
        "type" => "enum('document','asset','object')"
    );

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "Document_Page | Document_Snippet | Document | Asset | Object_Abstract";
    
    /**
     *
     * @var boolean
     */
    public $objectsAllowed;

     /**
     *
     * @var boolean
     */
    public $assetsAllowed;

    /**
     * Allowed asset types
     *
     * @var array
     */
    public $assetTypes;

     /**
     *
     * @var boolean
     */
    public $documentsAllowed;

    /**
     * Allowed document types
     *
     * @var array
     */
    public $documentTypes;


    /**
     * @return boolean
     */
    public function getObjectsAllowed() {
        return $this->objectsAllowed;
    }

    /**
     * @param boolean $objectsAllowed
     * @return void
     */
    public function setObjectsAllowed($objectsAllowed) {
        $this->objectsAllowed = $objectsAllowed;
    }
    
    /**
     * @return boolean
     */
    public function getDocumentsAllowed() {
        return $this->documentsAllowed;
    }

    /**
     * @param boolean $documentsAllowed
     * @return void
     */
    public function setDocumentsAllowed($documentsAllowed) {
        $this->documentsAllowed = $documentsAllowed;
    }


    /**
     * @return array
     */
    public function getDocumentTypes() {
        return $this->documentTypes;
    }

    /**
     * @param array
     * @return void $documentTypes
     */
    public function setDocumentTypes($documentTypes) {

        // this is the new method with Ext.form.MultiSelect
        if(is_string($documentTypes) && !empty($documentTypes)) {
            $parts = explode(",", $documentTypes);
            $documentTypes = array();
            foreach ($parts as $type) {
                $documentTypes[] = array("documentTypes" => $type);
            }
        }

        $this->documentTypes = $documentTypes;
    }

    /**
     *
     * @return boolean
     */
    public function getAssetsAllowed() {
        return $this->assetsAllowed;
    }

    /**
     *
     * @param boolean $assetsAllowed
     * @return void
     */
    public function setAssetsAllowed($assetsAllowed) {
        $this->assetsAllowed = $assetsAllowed;
    }

    /**
     * @return array
     */
    public function getAssetTypes() {
        return $this->assetTypes;
    }

    /**
     * @param array
     * @return void $assetTypes
     */
    public function setAssetTypes($assetTypes) {

        // this is the new method with Ext.form.MultiSelect
        if(is_string($assetTypes) && !empty($assetTypes)) {
            $parts = explode(",", $assetTypes);
            $assetTypes = array();
            foreach ($parts as $type) {
                $assetTypes[] = array("assetTypes" => $type);
            }
        }

        $this->assetTypes = $assetTypes;
    }

    /**
     * @see Object_Class_Data::getDataForResource
     * @param Asset | Document | Object_Abstract $data
     * @param null|Object_Abstract $object
     * @return array
     */
    public function getDataForResource($data, $object = null) {

        if($data instanceof Element_Interface){
            $type =  Element_Service::getType($data);
            $id = $data->getId();

            return array(array(
                "dest_id" => $id,
                "type" => $type,
                "fieldname" => $this->getName()
            ));
        } else return null;

    }

    /**
     * @see Object_Class_Data::getDataFromResource
     * @param array $data
     * @return Asset|Document|Object_Abstract
     */
    public function getDataFromResource($data, $notRelationTable = false) {
        
        if($notRelationTable) {   
            return Element_Service::getElementById($data[$this->getName()."__type"],$data[$this->getName()."__id"]);        
        }        
        
        // data from relation table
        $data = is_array($data) ? $data : array();
        $data = current($data);

        if ($data["dest_id"] && $data["type"]) {
            return Element_Service::getElementById($data["type"], $data["dest_id"]);
        }

        return null;
    }

    /**
     * @see Object_Class_Data::getDataForQueryResource
     * @param Asset|Document|Object_Abstract $data
     * @param null|Object_Abstract $object
     * @return array
     */
    public function getDataForQueryResource($data, $object = null) {
        
        $rData = $this->getDataForResource($data, $object);

        $return = array();
        $return[$this->getName() . "__id"] = $rData[0]["dest_id"];
        $return[$this->getName() . "__type"] = $rData[0]["type"];

        return $return;
    }

    /**
     * @see Object_Class_Data::getDataForEditmode
     * @param Asset|Document|Object_Abstract $data
     * @param null|Object_Abstract $object
     * @return array
     */
    public function getDataForEditmode($data, $object = null) {
        if ($data) {

            $r = array(
                "id" => $data->getId(),
                "path" => $data->getFullPath()
            );

            if ($data instanceof Document) {
                $r["subtype"] = $data->getType();
                $r["type"] = "document";
            }
            else if ($data instanceof Asset) {
                $r["subtype"] = $data->getType();
                $r["type"] = "asset";
            }
            else if ($data instanceof Object_Abstract) {
                $r["subtype"] = $data->getO_Type();
                $r["type"] = "object";
            }

            return $r;
        }
        return;
    }

    /**
     * @see Object_Class_Data::getDataFromEditmode
     * @param array $data
     * @param null|Object_Abstract $object
     * @return Asset|Document|Object_Abstract
     */
    public function getDataFromEditmode($data, $object = null) {

        if ($data["id"] && $data["type"]) {
            return Element_Service::getElementById($data["type"], $data["id"]);
        }

        return null;
    }


    public function getDataForGrid($data, $object = null) {
        if ($data instanceof Element_Interface) {
            return $data->getFullPath();
        }
    }

    /**
     * @see Object_Class_Data::getVersionPreview
     * @param Document | Asset | Object_Abstract $data
     * @return string
     */
    public function getVersionPreview($data) {
        if ($data instanceof Element_Interface) {
            return $data->getFullPath();
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

        
        if ($data instanceof Document) {
            $allow = $this->allowDocumentRelation($data);
        } else if ($data instanceof Asset) {
            $allow = $this->allowAssetRelation($data);
        } else if ($data instanceof Object_Abstract) {
            $allow = $this->allowObjectRelation($data);
        } else if(empty($data)){
            $allow = true;
        } else {
            Logger::error("invalid data in href");
            $allow = false;
        }

        if (!$allow) {
            throw new Exception("Invalid href relation", null, null);
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
        if ($data instanceof Element_Interface) {
            return Element_Service::getType($data).":".$data->getFullPath();
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
        $value = null;

        $values = explode(":",$importValue);
        if(count($values)==2){
            $type = $values[0];
            $path = $values[1];
            $value = Element_Service::getElementByPath($type,$path);
        } else {
            //fallback for old export files
            if ($el = Asset::getByPath($importValue)) {
            $value = $el;
            }
            else if ($el = Document::getByPath($importValue)) {
                $value = $el;
            }
            else if ($el = Object_Abstract::getByPath($importValue)) {
                $value = $el;
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

        if ($data instanceof Document || $data instanceof Asset || $data instanceof Object_Abstract) {
            if (!array_key_exists($data->getCacheTag(), $tags)) {
                if(!$ownerObject instanceof Element_Interface || $data->getId() != $ownerObject->getId()) {
                    $tags = $data->getCacheTags($tags);
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
        
        if ($data instanceof Asset) {
			$dependencies["asset_" . $data->getId()] = array(
				"id" => $data->getId(),
				"type" => "asset"
			);
		}
		else if ($data instanceof Document) {
			$dependencies["document_" . $data->getId()] = array(
				"id" => $data->getId(),
				"type" => "document"
			);
		}
		else if ($data instanceof Object_Abstract) {
			$dependencies["object_" . $data->getO_Id()] = array(
				"id" => $data->getO_Id(),
				"type" => "object"
			);
		}
        
        return $dependencies;
    }

    /**
     * converts data to be exposed via webservices
     * @param string $object
     * @return mixed
     */
    public function getForWebserviceExport ($object) {
        $key = $this->getName();
        $getter = "get".ucfirst($key);
        $data = $object->$getter();
        if ($data instanceof Element_Interface) {
            return array(
                "type" => Element_Service::getType($data),
                "subtype" => $data->getType(),
                "id" => $data->getId()
            );
        } else return null;
    }

    /**
     * converts data to be imported via webservices
     * @param mixed $value
     * @return mixed
     */
    public function getFromWebserviceImport ($value) {
        if(empty($value)){
            return null;        
        } else if(is_array($value) and key_exists("id",$value) and key_exists("type",$value)){
            $el =  $this->getDataFromEditmode($value);
            if(!empty($value['id']) and !$el instanceof Element_Interface){
                throw new Exception("cannot get values from web service import - invalid href relation");
            }
            return $el;
        } else {
            throw new Exception("cannot get values from web service import - invalid data");
        }
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

        if(Object_Abstract::doHideUnpublished() and ($data instanceof Element_Interface)) {
            if(!Element_Service::isPublished($data)){
                return null;
            }
        }

        return $data;
    }

    public function preSetData ($object, $data) {

        if($this->getLazyLoading() and !in_array($this->getName(), $object->getO__loadedLazyFields())){
            $object->addO__loadedLazyField($this->getName());
        }

        return $data;
    }

    /**
     * @param string $assetUploadPath
     */
    public function setAssetUploadPath($assetUploadPath)
    {
        $this->assetUploadPath = $assetUploadPath;
    }

    /**
     * @return string
     */
    public function getAssetUploadPath()
    {
        return $this->assetUploadPath;
    }
}
