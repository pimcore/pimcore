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
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Element;

class Href extends Model\Object\ClassDefinition\Data\Relations\AbstractRelations {

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
    public $phpdocType = "\\Pimcore\\Model\\Document\\Page | \\Pimcore\\Model\\Document\\Snippet | \\Pimcore\\Model\\Document | \\Pimcore\\Model\\Asset | \\Pimcore\\Model\\Object\\AbstractObject";
    
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
        return $this;
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
        return $this;
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
        return $this;
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
        return $this;
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
        return $this;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     * @param Asset | Document | Object\AbstractObject $data
     * @param null|Model\Object\AbstractObject $object
     * @return array
     */
    public function getDataForResource($data, $object = null) {

        if($data instanceof Element\ElementInterface){
            $type =  Element\Service::getType($data);
            $id = $data->getId();

            return array(array(
                "dest_id" => $id,
                "type" => $type,
                "fieldname" => $this->getName()
            ));
        } else return null;

    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     * @param array $data
     * @return Asset|Document|Object\AbstractObject
     */
    public function getDataFromResource($data, $notRelationTable = false) {
        
        if($notRelationTable) {   
            return Element\Service::getElementById($data[$this->getName()."__type"],$data[$this->getName()."__id"]);        
        }        
        
        // data from relation table
        $data = is_array($data) ? $data : array();
        $data = current($data);

        if ($data["dest_id"] && $data["type"]) {
            return Element\Service::getElementById($data["type"], $data["dest_id"]);
        }

        return null;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     * @param Asset|Document|Object\AbstractObject $data
     * @param null|Model\Object\AbstractObject $object
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
     * @see Object\ClassDefinition\Data::getDataForEditmode
     * @param Asset|Document|Object\AbstractObject $data
     * @param null|Model\Object\AbstractObject $object
     * @return array
     */
    public function getDataForEditmode($data, $object = null) {
        if ($data instanceof Element\ElementInterface) {

            $r = array(
                "id" => $data->getId(),
                "path" => $data->getFullPath(),
                "subtype" => $data->getType(),
                "type" => Element\Service::getElementType($data)
            );
            return $r;
        }
        return;
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     * @param array $data
     * @param null|Model\Object\AbstractObject $object
     * @return Asset|Document|Object\AbstractObject
     */
    public function getDataFromEditmode($data, $object = null) {

        if ($data["id"] && $data["type"]) {
            return Element\Service::getElementById($data["type"], $data["id"]);
        }

        return null;
    }


    public function getDataForGrid($data, $object = null) {
        if ($data instanceof Element\ElementInterface) {
            return $data->getFullPath();
        }
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     * @param Document | Asset | Object\AbstractObject $data
     * @return string
     */
    public function getVersionPreview($data) {
        if ($data instanceof Element\ElementInterface) {
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
        return $this;
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

        
        if ($data instanceof Document) {
            $allow = $this->allowDocumentRelation($data);
        } else if ($data instanceof Asset) {
            $allow = $this->allowAssetRelation($data);
        } else if ($data instanceof Object\AbstractObject) {
            $allow = $this->allowObjectRelation($data);
        } else if(empty($data)){
            $allow = true;
        } else {
            \Logger::error("invalid data in href");
            $allow = false;
        }

        if (!$allow) {
            throw new \Exception("Invalid href relation", null, null);
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
        if ($data instanceof Element\ElementInterface) {
            return Element\Service::getType($data).":".$data->getFullPath();
        } else return null;
    }

    /**
     * @param $importValue
     * @return mixed|null|Asset|Document|Element\ElementInterface
     */
    public function getFromCsvImport($importValue) {
        $value = null;

        $values = explode(":",$importValue);
        if(count($values)==2){
            $type = $values[0];
            $path = $values[1];
            $value = Element\Service::getElementByPath($type,$path);
        } else {
            //fallback for old export files
            if ($el = Asset::getByPath($importValue)) {
            $value = $el;
            }
            else if ($el = Document::getByPath($importValue)) {
                $value = $el;
            }
            else if ($el = Object::getByPath($importValue)) {
                $value = $el;
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

        if ($data instanceof Element\ElementInterface) {
            if (!array_key_exists($data->getCacheTag(), $tags)) {
                $tags = $data->getCacheTags($tags);
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
        
        if ($data instanceof Element\ElementInterface) {
            $elementType = Element\Service::getElementType($data);
			$dependencies[$elementType . "_" . $data->getId()] = array(
				"id" => $data->getId(),
				"type" => $elementType
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
        $data = $this->getDataFromObjectParam($object);
        if ($data instanceof Element\ElementInterface) {
            return array(
                "type" => Element\Service::getType($data),
                "subtype" => $data->getType(),
                "id" => $data->getId()
            );
        } else return null;
    }

    /**
     * @param mixed $value
     * @param null $relatedObject
     * @param null $idMapper
     * @return mixed|void
     * @throws \Exception
     */
    public function getFromWebserviceImport ($value, $relatedObject = null, $idMapper = null) {
        if(empty($value)){
            return null;        
        } else  {
            $value = (array) $value;
            if(array_key_exists("id",$value) and array_key_exists("type",$value)){
                $type = $value["type"];
                $id = $value["id"];

                if ($idMapper) {
                    $id = $idMapper->getMappedId($type, $id);
                }

                if ($id) {
                    $el = Element\Service::getElementById($type, $id);
                }

                if($el instanceof Element\ElementInterface){
                    return $el;
                } else {
                    if ($idMapper && $idMapper->ignoreMappingFailures()) {
                        $idMapper->recordMappingFailure("object", $relatedObject->getId(), $type,  $value["id"]);
                    } else {
                        throw new \Exception("cannot get values from web service import - invalid href relation");
                    }
                }

            } else {
                throw new \Exception("cannot get values from web service import - invalid data");
            }
        }
    }

    /**
     * @param $object
     * @param array $params
     * @return null|Object\Fieldcollection\Data\Object\Concrete|Object\Objectbrick\Data\
     */
    public function preGetData ($object, $params = array()) {

        $data = null;
        if($object instanceof Object\Concrete) {
            $data = $object->{$this->getName()};

            if($this->getLazyLoading() and !in_array($this->getName(), $object->getO__loadedLazyFields())){
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

        if(Object\AbstractObject::doHideUnpublished() and ($data instanceof Element\ElementInterface)) {
            if(!Element\Service::isPublished($data)){
                return null;
            }
        }

        return $data;
    }

    /**
     * @param $object
     * @param $data
     * @param array $params
     * @return mixed
     */
    public function preSetData ($object, $data, $params = array()) {

        if($object instanceof Object\Concrete) {
            if($this->getLazyLoading() and !in_array($this->getName(), $object->getO__loadedLazyFields())){
                $object->addO__loadedLazyField($this->getName());
            }
        }

        return $data;
    }

    /**
     * @param $assetUploadPath
     * @return $this
     */
    public function setAssetUploadPath($assetUploadPath)
    {
        $this->assetUploadPath = $assetUploadPath;
        return $this;
    }

    /**
     * @return string
     */
    public function getAssetUploadPath()
    {
        return $this->assetUploadPath;
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
        if($data) {
            $data = $this->rewriteIdsService(array($data), $idMapping);
            $data = $data[0]; //get the first element
        }
        return $data;
    }

    /**
     * @param Object\ClassDefinition\Data $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Object\ClassDefinition\Data $masterDefinition) {
        $this->assetUploadPath = $masterDefinition->assetUploadPath;
        $this->relationType = $masterDefinition->relationType;
    }
}
