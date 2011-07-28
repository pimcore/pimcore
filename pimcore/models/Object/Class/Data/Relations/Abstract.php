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

abstract class Object_Class_Data_Relations_Abstract extends Object_Class_Data {

    /**
     * @var bool
     */
    public static $remoteOwner = false;


    /**
     * @var boolean
     */
    public $lazyLoading;

    /**
     * Set of allowed classes
     *
     * @var array
     */
    public $classes;

    /**
     * @return array
     */
    public function getClasses() {
        $this->classes = $this->correctClasses($this->classes);
        return $this->classes;
    }

    /**
     * @param array
     * @return void $classes
     */
    public function setClasses($classes) {
        $this->classes = $this->correctClasses($classes);
    }

    /**
     * this is a hack for import see: http://www.pimcore.org/issues/browse/PIMCORE-790
     * @param array
     * @return array
     */
    protected function correctClasses ($classes) {
        if(is_array($classes) && array_key_exists("classes",$classes)) {
            $classes = array($classes);
        }
        return $classes;
    }

    /**
     * @return boolean
     */
    public function getLazyLoading(){
        return $this->lazyLoading;
    }

    /**
     * @param  $lazyLoading
     * @return void
     */
    public function setLazyLoading($lazyLoading){
        $this->lazyLoading = $lazyLoading;
    }

    /**
     * @return bool
     */
    public function isRemoteOwner(){
        return self::$remoteOwner;
    }

    /**
     *
     * Checks if an object is an allowed relation
     * @param Object_Abstract $object
     * @return boolean
     */
    protected function allowObjectRelation($object) {
        $allowedClasses = $this->getClasses();
        $allowed = true;
        if (!$this->getObjectsAllowed()) {
            $allowed = false;
        } else if ($this->getObjectsAllowed() and is_array($allowedClasses) and count($allowedClasses) > 0) {
            //check for allowed classes
            if($object instanceof Object_Concrete){
                $classname = $object->getO_className();
                foreach ($allowedClasses as $c) {
                    $allowedClassnames[] = $c['classes'];
                }
                if (!in_array($classname, $allowedClassnames)) {
                    $allowed = false;
                }
            } else {
                $allowed = false;
            }
        } else {
            //don't check if no allowed classes set
        }

        if($object instanceof Object_Abstract){
            Logger::debug("checked object relation to target object [" . $object->getId() . "] in field [" . $this->getName() . "], allowed:" . $allowed);
        } else {
            Logger::debug("checked object relation to target in field [" . $this->getName() . "], not allowed, target ist not an object");
            Logger::debug($object);
        }
        return $allowed;
    }

    /**
     *
     * Checks if an asset is an allowed relation
     * @param Asset $asset
     * @return boolean
     */
    protected function allowAssetRelation($asset) {
        $allowedAssetTypes = $this->getAssetTypes();
        $allowed = true;
        if (!$this->getAssetsAllowed()) {
            $allowed = false;
        } else if ($this->getAssetsAllowed() and  is_array($allowedAssetTypes) and count($allowedAssetTypes) > 0) {
            //check for allowed asset types
            foreach ($allowedAssetTypes as $t) {
                $allowedTypes[] = $t['assetTypes'];
            }
            if (!in_array($asset->getType(), $allowedTypes)) {
                $allowed = false;
            }
        } else {
            //don't check if no allowed asset types set
        }

        Logger::debug("checked object relation to target asset [" . $asset->getId() . "] in field [" . $this->getName() . "], allowed:" . $allowed);
        return $allowed;

    }

    /**
     *
     * Checks if an document is an allowed relation
     * @param Document $document
     * @return boolean
     */
    protected function allowDocumentRelation($document) {
        $allowedDocumentTypes = $this->getDocumentTypes();

        $allowed = true;
        if (!$this->getDocumentsAllowed()) {
            $allowed = false;
        } else if ($this->getDocumentsAllowed() and  is_array($allowedDocumentTypes) and count($allowedDocumentTypes) > 0) {
            //check for allowed asset types
            foreach ($allowedDocumentTypes as $t) {
                $allowedTypes[] = $t['documentTypes'];
            }
            if (!in_array($document->getType(), $allowedTypes)) {
                $allowed = false;
            }
        } else {
            //don't check if no allowed document types set
        }

        Logger::debug("checked object relation to target document [" . $document->getId() . "] in field [" . $this->getName() . "], allowed:" . $allowed);
        return $allowed;

    }


    /**
     * Checks if data for this field is valid and removes broken dependencies
     *
     * @param Object_Abstract $object
     * @return bool
     */
    public function sanityCheck($object) {
        $sane = true;

        $name = $this->getName();
        $getter = "get".ucfirst($name);
        $data = $object->$getter();
        $objectRelationIds = array();
        if (is_array($data)) {
            foreach ($data as $o) {
                if($o instanceof Element_Interface) {
                    $objectRelationIds[] = $o->getId();
                }
            }
        } else if ($data instanceof Element_Interface){
            $objectRelationIds[] = $data->getId();
        }

        $resourceRelationIds = $object->getResource()->getRelationIds($this->getName());

        $diff = array_diff($objectRelationIds, $resourceRelationIds);
        if (count($diff) > 0) {
            $sane = false;
            logger::notice("Detected insane relation(s), removing reference to non existent elements with ids [".implode(',',$diff)."]");
        }

        return $sane;
    }


    /**
     * @param Object_Concrete|Object_Fieldcollection_Data_Abstract|Object_Localizedfield $object
     * @return void
     */
    public function save ($object, $params = array()) {

        $db = Pimcore_Resource::get();
        $getter = "get" . ucfirst($this->getName());


        if (method_exists($object, $getter)) {
            // for fieldcollections and objects
            $relations = $this->getDataForResource($object->$getter(), $object);
        } else if ($object instanceof Object_Localizedfield) {
            // since Object_Localizedfield doesn't have their own getters, because they are located in the wrapping-object
            $relations = $this->getDataForResource($object->getLocalizedValue($this->getName(), $params["language"]), $object);
        }

        if (is_array($relations) && !empty($relations)) {
            foreach ($relations as $relation) {


                if($object instanceof Object_Concrete) {

                    $relation["src_id"] = $object->getId();
                    $relation["ownertype"] = "object";

                    $classId = $object->getClassId();

                } else if($object instanceof Object_Fieldcollection_Data_Abstract) {

                    $relation["src_id"] = $object->getObject()->getId(); // use the id from the object, not from the field collection
                    $relation["ownertype"] = "fieldcollection";
                    $relation["ownername"] = $object->getFieldname();
                    $relation["position"] = $object->getIndex();

                    $classId = $object->getObject()->getClassId();

                } else if ($object instanceof Object_Localizedfield) {
                    
                    $relation["src_id"] = $object->getObject()->getId();
                    $relation["ownertype"] = "localizedfield";
                    $relation["ownername"] = "localizedfield";
                    $relation["position"] = $params["language"];

                    $classId = $object->getObject()->getClassId();
                    
                } else if ($object instanceof Object_Objectbrick_Data_Abstract) {

                    $relation["src_id"] = $object->getObject()->getId();
                    $relation["ownertype"] = "objectbrick";
                    $relation["ownername"] = $object->getFieldname();

                    $classId = $object->getObject()->getClassId();
                }

                /*relation needs to be an array with src_id, dest_id, type, fieldname*/
                try {
                    $db->insert("object_relations_" . $classId, $relation);
                } catch (Exception $e) {
                    Logger::warning("It seems that the relation " . $relation["src_id"] . " => " . $relation["dest_id"] . " already exist");
                }
            }
        }
    }

    /**
     * @param Object_Concrete|Object_Fieldcollection_Data_Abstract|Object_Localizedfield $object
     * @return null | array
     */
    public function load($object, $params = array()) {
        $db = Pimcore_Resource::get();

        if($object instanceof Object_Concrete) {
            if (!method_exists($this, "getLazyLoading") or !$this->getLazyLoading() or $params["force"]) {
                $relations = $db->fetchAll("SELECT * FROM object_relations_" . $object->getClassId() . " WHERE src_id = ? AND fieldname = ? AND ownertype = 'object' ORDER BY `index` ASC", array($object->getO_id(), $this->getName()));
                return $this->getDataFromResource($relations);
            } else {
                return null;
            }
        } else if ($object instanceof Object_Fieldcollection_Data_Abstract) {
            $relations = $db->fetchAll("SELECT * FROM object_relations_" . $object->getObject()->getClassId() . " WHERE src_id = ? AND fieldname = ? AND ownertype = 'fieldcollection' AND ownername = ? AND position = ? ORDER BY `index` ASC", array($object->getObject()->getId(), $this->getName(), $object->getFieldname(), $object->getIndex()));
            return $this->getDataFromResource($relations);
        } else if ($object instanceof Object_Localizedfield) {
            $relations = $db->fetchAll("SELECT * FROM object_relations_" . $object->getObject()->getClassId() . " WHERE src_id = ? AND fieldname = ? AND ownertype = 'localizedfield' AND ownername = 'localizedfield' AND position = ? ORDER BY `index` ASC", array($object->getObject()->getId(), $this->getName(), $params["language"]));
            return $this->getDataFromResource($relations);
        } else if ($object instanceof Object_Objectbrick_Data_Abstract) {
            $relations = $db->fetchAll("SELECT * FROM object_relations_" . $object->getObject()->getClassId() . " WHERE src_id = ? AND fieldname = ? AND ownertype = 'objectbrick' AND ownername = ? ORDER BY `index` ASC", array($object->getObject()->getId(), $this->getName(), $object->getFieldname()));
            return $this->getDataFromResource($relations);
        }
    }
}