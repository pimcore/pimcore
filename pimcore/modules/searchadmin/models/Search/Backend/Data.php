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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Search_Backend_Data extends Pimcore_Model_Abstract {

    /**
     * @var Search_Backend_Data_Id
     */
    public $id;

    /**
     * @var string
     */
    public $fullPath;

    /**
     * document | object | asset
     * @var string
     */
    public $maintype;

    /**
     * webresource type (e.g. page, snippet ...)
     * @var string
     */
    public $type;

    /**
     * currently only relevant for objects where it portrays the class name
     * @var string
     */
    public $subtype;

    /**
     * published or not
     *
     * @var bool
     */
    public $published;

    /**
     * timestamp of creation date
     *
     * @var integer
     */
    public $creationDate;

    /**
     * timestamp of modification date
     *
     * @var integer
     */
    public $modificationDate;

    /**
     * User-ID of the owner
     *
     * @var integer
     */
    public $userOwner;

    /**
     * User-ID of the user last modified the element
     *
     * @var integer
     */
    public $userModification;

    /**
     * @var string
     */
    public $data;

    /**
     * @var string
     */
    public $properties;

    /**
     * @param  Element_Interface $element
     * @return void
     */
    public function __construct($element = null){

        if($element instanceof Element_Interface){
            $this->setDataFromElement($element);
        }
    }

    public function getResource() {

        if (!$this->resource) {
            $this->initResource("Search_Backend_Data");
        }
        return $this->resource;
    }


    /**
     * @return Search_Backend_Data_Id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param  Search_Backend_Data_Id $id
     * @return void
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getFullPath() {
        return $this->fullPath;
    }

    /**
     * @param  string $fullpath
     * @return void
     */
    public function setFullPath($fullpath) {
        $this->fullPath = $fullpath;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param  string $fullpath
     * @return void
     */
    public function setType($type) {
        $this->type = $type;
    }


    /**
     * @return string
     */
    public function getSubtype() {
        return $this->subtype;
    }

    /**
     * @param  string $type
     * @return void
     */
    public function setSubtype($subtype) {
        $this->subtype = $subtype;
    }

    /**
     * @return integer
     */
    public function getCreationDate() {
        return $this->creationDate;
    }

    public function setCreationDate($creationDate) {
        $this->creationDate = $creationDate;
    }


    /**
     * @return integer
     */
    public function getModificationDate() {
        return $this->modificationDate;
    }

    /**
     * @param integer $modificationDate
     * @return void
     */
    public function setModificationDate($modificationDate) {
        $this->modificationDate = $modificationDate;
    }

    /**
     * @return integer
     */
    public function getUserModification() {
        return $this->userModification;
    }

    /**
     * @param integer $userModification
     * @return void
     */
    public function setUserModification($userModification) {
        $this->userModification = $userModification;
    }

    /**
     * @return integer
     */
    public function getUserOwner() {
        return $this->userOwner;
    }

    /**
     * @param integer $userOwner
     * @return void
     */
    public function setUserOwner($userOwner) {
        $this->userOwner = $userOwner;
    }

    /**
     * @return boolean
     */
    public function isPublished() {
        return (bool) $this->getPublished();
    }

    /**
     * @return boolean
     */
    public function getPublished() {
        return (bool) $this->published;
    }

    /**
     * @param integer $published
     * @return void
     */
    public function setPublished($published) {
        $this->published = (bool) $published;
    }

    /**
     * @return string
     */
    public function getData(){
        return $this->data;
    }

    /**
     * @param  string $data
     * @return void
     */
    public function setData($data){
        $this->data = $data;
    }

    /**
    * @return string
    */
    public function getProperties(){
        return $this->properties;
    }

    /**
     * @param  string $properties
     * @return void
     */
    public function setProperties($properties){
        $this->properties = $properties;
    }



    /**
     * @param  Element_Interface  $element
     * @return void
     */
    public function setDataFromElement($element){

        $this->data = null;

        $this->id = new Search_Backend_Data_Id($element);
        $this->fullPath = $element->getFullPath();
        $this->creationDate=$element->getCreationDate();
        $this->modificationDate=$element->getModificationDate();
        $this->userModification = $element->getUserModification();
        $this->userOwner = $element->getUserOwner();

        $this->type = $element->getType();
        if($element instanceof Object_Concrete){
            $this->subtype = $element->getO_className();
        } else {
            $this->subtype = $this->type;
        }

        $this->properties = "";
        $properties = $element->getProperties();
        if(is_array($properties)){
            foreach($properties as $nextProperty){
                if($nextProperty->getType() == 'text'){
                    $this->properties.=$nextProperty->getData()." ";
                }
            }
        }

        $this->data = "";
        if($element instanceof Document){
            if($element instanceof Document_Folder){
                $this->data = $element->getKey();
                $this->published = true;
            } else if ($element instanceof Document_Link){
                $this->published = $element->isPublished();
                $this->data = $element->getName()." ".$element->getTitle()." ".$element->getHref();
            } else if ($element instanceof Document_PageSnippet){
                $this->published = $element->isPublished();
                $elements = $element->getElements();
                if(is_array($elements) && !empty($elements)) {
                    foreach($elements as $tag){
                        if($tag instanceof Document_Tag_Interface){
                            ob_start();
                            $this->data .= strip_tags($tag->frontend())." ";
                            $this->data .= ob_get_clean();
                        }
                    }
                }
                if($element instanceof Document_Page){
                    $this->published = $element->isPublished();
                    $this->data .= " ".$element->getName()." ".$element->getTitle()." ".$element->getDescription()." ".$element->getKeywords() . " " . $element->getPrettyUrl();
                }
            }
        } else if($element instanceof Asset) {
            $this->data = $element->getFilename();
            $this->published = true;
        } else if ($element instanceof Object_Abstract){
            if ($element instanceof Object_Concrete) {
                $getInheritedValues = Object_Abstract::doGetInheritedValues();
                Object_Abstract::setGetInheritedValues(true);

                $this->published = $element->isPublished();
                foreach ($element->getClass()->getFieldDefinitions() as $key => $value) {
                    // Object_Class_Data_Fieldcollections, Object_Class_Data_Localizedfields and Object_Class_Data_Objectbricks is special because it doesn't support the csv export
                    if($value instanceof Object_Class_Data_Fieldcollections) {
                        $getter = "get".$value->getName();

                        $fcData = $element->$getter();
                        if ($fcData instanceof Object_Fieldcollection) {
                            foreach ($fcData as $item) {

                                if (!$item instanceof Object_Fieldcollection_Data_Abstract) {
                                    continue;
                                }

                                try {
                                    $collectionDef = Object_Fieldcollection_Definition::getByKey($item->getType());
                                } catch (Exception $e) {
                                    continue;
                                }

                                foreach ($collectionDef->getFieldDefinitions() as $fd) {
                                    $this->data .= $fd->getForCsvExport($item) . " ";
                                }
                            }
                        }
                        //$this->data .= Pimcore_Tool_Serialize::serialize($value->getDataForEditmode($element->$getter(), $element))." ";
                    } else if ($value instanceof Object_Class_Data_Localizedfields){

                        // only for text-values at the moment, because the CSV doesn't work for localized fields (getter-problem)
                        $getter = "get".$value->getName();

                        $lfData = $element->$getter();
                        if ($lfData instanceof Object_Localizedfield) {
                            foreach ($lfData->getItems() as $language => $values) {
                                foreach ($values as $lData) {
                                    if(is_string($lData)) {
                                        $this->data .= $lData . " ";
                                    }
                                }
                            }
                        }
                        //$this->data .= Pimcore_Tool_Serialize::serialize($value->getDataForEditmode($element->$getter(), $element))." ";
                    } else if ($value instanceof Object_Class_Data_Objectbricks){
                        $getter = "get".$value->getName();

                        $obData = $element->$getter();
                        if ($obData instanceof Object_Objectbrick) {
                            $items = $obData->getItems();
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
                                    $this->data .= $fd->getForCsvExport($item) . " ";
                                }
                            }
                        }

                        //$this->data .= Pimcore_Tool_Serialize::serialize($value->getDataForEditmode($element->$getter(), $element))." ";
                    } else {
                        $this->data .= $value->getForCsvExport($element)." ";
                    }
                }
                Object_Abstract::setGetInheritedValues($getInheritedValues);

            } else if ($element instanceof Object_Folder){
                $this->data=$element->getKey();
                $this->published = true;
            }
        } else {
            Logger::crit("Search_Backend_Data received an unknown element!");
        }

        if($element instanceof Element_Interface) {
            $this->data = "ID: " . $element->getId() . "  \nPath: " . $this->getFullPath() . "  \n"  . $this->cleanupData($this->data);
        }


    }

    /**
     * @param $data
     */
    protected function cleanupData ($data) {

        $data = strip_tags($data);
        $data = str_replace("\r\n", " ", $data);
        $data = str_replace("\n", " ", $data);
        $data = str_replace("\r", " ", $data);
        $data = str_replace("\t", "", $data);
        $data = preg_replace ('#[ ]+#', ' ', $data);

        return $data;
    }

    /**
     * @param  Element_Interface $element
     * @return Search_Backend_Data
     */
    public static function getForElement($element){

        $data = new Search_Backend_Data();
		$data->getResource()->getForElement($element);
		return $data;

    }

    public function delete(){
        $this->getResource()->delete();
    }

    /**
	 * @return void
	 */
	public function save () {
        if($this->id instanceof Search_Backend_Data_Id){
            $this->getResource()->save();
        } else {
            throw new Exception("Search_Backend_Data cannot be saved - no id set!");
        }

	}



}