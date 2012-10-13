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
 * @package    Object
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Abstract extends Pimcore_Model_Abstract implements Element_Interface {

    const OBJECT_TYPE_FOLDER = "folder";
    const OBJECT_TYPE_OBJECT = "object";
    const OBJECT_TYPE_VARIANT = "variant";

    /**
     * possible types of a document
     * @var array
     */
    public static $types = array(self::OBJECT_TYPE_FOLDER, self::OBJECT_TYPE_OBJECT, self::OBJECT_TYPE_VARIANT);

    /**
     * @var bool
     */
    private static $hidePublished = false;

    /**
     * @var bool
     */
    private static $getInheritedValues = false;

    /**
     * @static
     * @return bool
     */
    public static function getHideUnpublished() {
        return self::$hidePublished;
    }

    /**
     * @static
     * @param  $hidePublished
     * @return void
     */
    public static function setHideUnpublished($hidePublished) {
        self::$hidePublished = $hidePublished;
    }

    /**
     * @static
     * @return bool
     */
    public static function doHideUnpublished() {
        return self::$hidePublished;
    }

    /**
     * @static
     * @param  $getInheritedValues
     * @return void
     */
    public static function setGetInheritedValues($getInheritedValues) {
        self::$getInheritedValues = $getInheritedValues;
    }

    /**
     * @static
     * @return bool
     */
    public static function getGetInheritedValues() {
        return self::$getInheritedValues;
    }

    /**
     * @static
     * @return bool
     */
    public static function doGetInheritedValues(Object_Concrete $object = null) {
        if(self::$getInheritedValues && $object !== null) {
            $class = $object->getClass();
            return $class->getAllowInherit();
        }

        return self::$getInheritedValues;
    }


    /**
     * @var integer
     */
    public $o_id = 0;

    /**
     * @var integer
     */
    public $o_parentId;


    /**
     * @var Object_Abstract
     */
    public $o_parent;

    /**
     * @var string
     */
    public $o_type = "object";

    /**
     * @var string
     */
    public $o_key;

    /**
     * @var string
     */
    public $o_path;


     /**
     * @var string old path before update, later needed to update children
     */
    protected $_oldPath;

    /**
     * @var integer
     */
    public $o_index;

    /**
     * @var integer
     */
    public $o_creationDate;

    /**
     * @var integer
     */
    public $o_modificationDate;

    /**
     * @var integer
     */
    public $o_userOwner;

    /**
     * @var integer
     */
    public $o_userModification;


    /**
     * @var array
     */
    public $o_properties = null;

    /**
     * @var boolean
     */
    public $o_hasChilds;

    /**
     * @var Dependency
     */
    public $o_dependencies;

    /**
     * @var array
     */
    public $o_childs;

    /**
     * @var string
     */
    public $o_locked;


    /**
     * @var Element_AdminStyle
     */
    public $o_elementAdminStyle;


    /**
     * get possible types
     * @return array
     */
    public static function getTypes() {
        return self::$types;
    }

    /**
     * @param integer $id
     * @return Object_Abstract
     */
    public static function getById($id) {
        
        $id = intval($id);

        if ($id < 1) {
            return null;
        }

        $cacheKey = "object_" . $id;


        try {
            $object = Zend_Registry::get($cacheKey);
            if(!$object){
                throw new Exception("Object_Abstract: object in registry is null");   
            }
        }
        catch (Exception $e) {

            try {
                if (!$object = Pimcore_Model_Cache::load($cacheKey)) {
                    
                    $object = new Object_Abstract();
                    $typeInfo = $object->getResource()->getTypeById($id);

                    if ($typeInfo["o_type"] == "object" || $typeInfo["o_type"] == "variant" || $typeInfo["o_type"] == "folder") {

                        if($typeInfo["o_type"] == "folder") {
                            $concreteClassName = "Object_Folder";
                        } else {
                            $concreteClassName = "Object_" . ucfirst($typeInfo["o_className"]);
                        }

                        // check for a mapped class
                        $concreteClassName = Pimcore_Tool::getModelClassMapping($concreteClassName);

                        $object = new $concreteClassName();
                        Zend_Registry::set($cacheKey, $object);
                        $object->getResource()->getById($id);

                        Pimcore_Model_Cache::save($object, $cacheKey);
                    }
                    else {
                        throw new Exception("No entry for object id " . $id);
                    }
                }
                else {
                    Zend_Registry::set($cacheKey, $object);
                }
            }
            catch (Exception $e) {
                Logger::warning($e);
                return null;
            }
        }

        $selfType = get_class();
        $staticType = get_called_class();
        
        // check for type
        if ($selfType != $staticType) {
            if (!$object instanceof $staticType) {
                if(!($object instanceof Object_Concrete && $staticType == "Object_Concrete")) {
                    return null;
                }
            }
        }
        
        if(!$object) {
            return null;
        }

        return $object;
    }

    /**
     * @param string $path
     * @return Object_Abstract
     */
    public static function getByPath($path) {

        $path = Element_Service::correctPath($path);

        try {
            $object = new self();

            if (Pimcore_Tool::isValidPath($path)) {
                $object->getResource()->getByPath($path);
                return self::getById($object->getId());
            }
        }
        catch (Exception $e) {
            Logger::warning($e);
        }

        return null;
    }

    /**
     * @param array $config
     * @return Object_List
     */
    public static function getList($config = array()) {

        $className = "Object";
        // get classname
        if(get_called_class() != "Object_Abstract" && get_called_class() != "Object_Concrete") {
            $tmpObject = new static();
            $className = "Object_" . ucfirst($tmpObject->getO_className());
        }

        if (!empty($config["class"])) {
            $className = $config["class"];
        }

        //echo $className;exit;

        if (is_array($config)) {
            if ($className) {

                $listClass = ucfirst($className) . "_List";

                // check for a mapped class
                $listClass = Pimcore_Tool::getModelClassMapping($listClass);

                if (Pimcore_Tool::classExists($listClass)) {
                    $list = new $listClass();
                }
            }

            $list->setValues($config);
            $list->load();

            return $list;
        }
    }


    /*
      * @return array
      */
    public function getCacheTag() {
        return "object_" . $this->getO_id();
    }

    /*
      * @return array
      */
    public function getCacheTags($tags = array()) {
        
        $tags = is_array($tags) ? $tags : array();

        $tags[$this->getCacheTag()] = $this->getCacheTag();
        return $tags;
    }


    private $lastGetChildsObjectTypes = array();

    /**
     * @return array
     */
    public function getO_childs($objectTypes = array(self::OBJECT_TYPE_OBJECT, self::OBJECT_TYPE_FOLDER)) {

        if ($this->o_childs === null || $this->lastGetChildsObjectTypes != $objectTypes) {
            $this->lastGetChildsObjectTypes = $objectTypes;

            $list = new Object_List(true);
            $list->setCondition("o_parentId = ?", $this->getO_id());
            $list->setOrderKey("o_key");
            $list->setOrder("asc");
            $list->setObjectTypes($objectTypes);
            $this->o_childs = $list->load();
        } 

        return $this->o_childs;
    }
    
    /**
     * @return array
     */
    public function getChilds ($objectTypes = array(self::OBJECT_TYPE_OBJECT, self::OBJECT_TYPE_FOLDER)) {
        return $this->getO_childs($objectTypes);
    }
    
    /**
     * @return boolean
     */
    public function hasNoChilds() {
        if ($this->hasChilds()) {
            return false;
        }
        return true;
    }


    /**
     * @return boolean
     */
    public function hasChilds($objectTypes = array(self::OBJECT_TYPE_OBJECT, self::OBJECT_TYPE_FOLDER)) {
        if(is_bool($this->o_hasChilds)){
            if(($this->o_hasChilds and empty($this->o_childs)) or (!$this->o_hasChilds and !empty($this->o_childs))){
                return $this->getResource()->hasChilds($objectTypes);
            } else {
                return $this->o_hasChilds;
            }
        }
        return $this->getResource()->hasChilds($objectTypes);
    }

    /**
     * Returns true if the element is locked
     * @return string
     */
    public function getO_locked(){
        return $this->o_locked;
    }

    /**
     * @param  $locked
     * @return void
     */
    public function setO_locked($o_locked){
        $this->o_locked = $o_locked;
    }
    
    /**
     * Returns true if the element is locked
     * @return string
     */
    public function getLocked(){
        return $this->o_locked;
    }

    /**
     * @param  $locked
     * @return void
     */
    public function setLocked($o_locked){
        $this->o_locked = $o_locked;
    }
    
    /**
     * Returns true if the element is locked
     * @return bool
     */
    public function isLocked(){
        if($this->getO_locked()) {
            return true;
        }
        
        // check for inherited
        return $this->getResource()->isLocked();
    }

    /**
     * @return boolean
     */
    public function isAllowed($type) {

        $currentUser = Pimcore_Tool_Admin::getCurrentUser();
        //everything is allowed for admin
        if ($currentUser->isAdmin()) {
            return true;
        }

        return $this->getResource()->isAllowed($type, $currentUser);
    }

    /**
     * @return array
     */
    public function getUserPermissions () {

        $vars = get_class_vars("User_Workspace_Object");
        $ignored = array("userId","cid","cpath");
        $permissions = array();

        foreach ($vars as $name => $defaultValue) {
            if(!in_array($name, $ignored)) {
                $permissions[$name] = $this->isAllowed($name);
            }
        }

        return $permissions;
    }

    /**
     * @return void
     */
    public function delete() {

        Pimcore_API_Plugin_Broker::getInstance()->preDeleteObject($this);

        // delete childs
        if ($this->hasChilds(array(self::OBJECT_TYPE_OBJECT, self::OBJECT_TYPE_FOLDER, self::OBJECT_TYPE_VARIANT))) {
            foreach ($this->getO_childs(array(self::OBJECT_TYPE_OBJECT, self::OBJECT_TYPE_FOLDER, self::OBJECT_TYPE_VARIANT)) as $value) {
                $value->delete();
            }
        }


        // remove dependencies
        $d = $this->getDependencies();
        $d->cleanAllForElement($this);

        // remove all properties
        $this->getResource()->deleteAllProperties();

        // remove all permissions
        $this->getResource()->deleteAllPermissions();

        $this->getResource()->delete();
        
        // empty object cache
        $this->clearDependedCache();

        //set object to registry
        Zend_Registry::set("object_" . $this->getId(), null);
        
        Pimcore_API_Plugin_Broker::getInstance()->postDeleteObject($this);
    }


    /**
     * @return void
     */
    public function save() {

        if($this->getO_Id()) {
            // do not lock when creating a new object, this will cause a dead-lock because the cache-tag is used as key
            // and the cache tag is different when releasing the lock later, because the object has then an id
            Tool_Lock::acquire($this->getCacheTag());
        }

        $this->beginTransaction();

        try {
            // be sure that unpublished objects in relations are saved also in frontend mode, eg. in importers, ...
            $hideUnpublishedBackup = self::getHideUnpublished();
            self::setHideUnpublished(false);

            if(!Pimcore_Tool::isValidKey($this->getKey())){
                throw new Exception("invalid key for object with id [ ".$this->getId()." ] key is: [" . $this->getKey() . "]");
            }

           $this->correctPath();

            if ($this->getO_Id()) {
                $this->update();
            }
            else {
                Pimcore_API_Plugin_Broker::getInstance()->preAddObject($this);
                $this->getResource()->create();
                Pimcore_API_Plugin_Broker::getInstance()->postAddObject($this);
                $this->update();
            }

            self::setHideUnpublished($hideUnpublishedBackup);

            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();

            throw $e;
        }

        Tool_Lock::release($this->getCacheTag());
    }
    
    
    public function correctPath () {
        // set path
        if($this->getId() != 1) { // not for the root node
            $parent = Object_Abstract::getById($this->getParentId());

            if($parent) {
                $this->setPath(str_replace("//","/",$parent->getFullPath()."/"));
            } else {
                // parent document doesn't exist anymore, so delete this document
                //$this->delete();

                // parent document doesn't exist anymore, set the parent to to root
                $this->setO_parentId(1);
                $this->setO_path("/");
            }
        }

        if(Object_Service::pathExists($this->getFullPath())) {
            $duplicate = Object_Abstract::getByPath($this->getFullPath());
            if($duplicate instanceof Object_Abstract and $duplicate->getId() != $this->getId()){
                throw new Exception("Duplicate full path [ ".$this->getFullPath()." ] - cannot create object");
            }
        }
    }
    
    
    /**
     * @return void
     */
    protected function update() {

        Pimcore_API_Plugin_Broker::getInstance()->preUpdateObject($this);
        
        if(!$this->getKey() && $this->getId() != 1) {
            $this->delete();
            throw new Exception("Object requires key, object with id " . $this->getId() . " deleted");
        }
        
        // set mod date
        $this->setO_modificationDate(time());

        // save properties
        $this->getO_Properties();
        $this->getResource()->deleteAllProperties();

        if (is_array($this->getO_Properties()) and count(is_array($this->getO_Properties())) > 0) {
            foreach ($this->getO_Properties() as $property) {
                if (!$property->getInherited()) {
                    $property->setResource(null);
                    $property->setCid($this->getO_Id());
                    $property->setCpath($this->getO_Path() . $this->getO_Key());
                    $property->save();
                }
            }
        }

        // save dependencies
        $d = $this->getDependencies();
        $d->clean();

        foreach ($this->resolveDependencies() as $requirement) {

            if ($requirement["id"] == $this->getO_id() && $requirement["type"] == "object") {
                // dont't add a reference to yourself
                continue;
            }
            else {
                $d->addRequirement($requirement["id"], $requirement["type"]);
            }
        }

        $d->save();

        if($this->_oldPath){
            $this->getResource()->updateChildsPaths($this->_oldPath);
        }
        
        
        // empty object cache
        $this->clearDependedCache();

        //set object to registry
        Zend_Registry::set("object_" . $this->getId(), $this);

        
    }


    public function clearDependedCache() {
        try {
            Pimcore_Model_Cache::clearTag("object_" . $this->getO_Id());
        }
        catch (Exception $e) {
        }
        try {
            Pimcore_Model_Cache::clearTag("properties");
        }
        catch (Exception $e) {
        }
        try {
            Pimcore_Model_Cache::clearTag("output");
        }
        catch (Exception $e) {
        }
    }

    /**
     * @return array
     */
    public function resolveDependencies() {

        $dependencies = array();

        // check for properties
        if (method_exists($this, "getO_properties")) {
            $properties = $this->getO_properties();

            foreach ($properties as $property) {
                $dependencies = array_merge($dependencies, $property->resolveDependencies());
            }
        }

        return $dependencies;
    }

    /**
     * @return Dependency
     */
    public function getO_Dependencies() {

        if (!$this->o_dependencies) {
            $this->o_dependencies = Dependency::getBySourceId($this->getId(), "object");
        }
        return $this->o_dependencies;
    }
    
    /**
     * @return Dependency
     */
    public function getDependencies() {
        return $this->getO_Dependencies();
    }

    /**
     * @return string
     */
    public function getO_FullPath() {
        $path = $this->getO_Path() . $this->getO_Key();
        return $path;
    }

    /**
     * @return string
     */
    public function getFullPath() {
        return $this->getO_FullPath();
    }

    /**
     * @return integer
     */
    public function getO_id() {
        return $this->o_id;
    }
    
    /**
     * @return integer $id
     */
    public function getId() {
        return (int) $this->getO_id();
    }

    /**
     * @return integer
     */
    public function getO_parentId() {
        return $this->o_parentId;
    }

    /**
     * @return integer
     */
    public function getParentId() {
        return $this->getO_parentId();
    }

    /**
     * @return string
     */
    public function getO_type() {
        return $this->o_type;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->getO_type();
    }

    /**
     * @return string
     */
    public function getO_key() {
        return $this->o_key;
    }


    /**
     * @return string
     */
    public function getKey() {
        return $this->getO_key();
    }

    /**
     * @return path
     */
    public function getO_path() {
        return $this->o_path;
    }

    /**
     * @return path
     */
    public function getPath() {
        return $this->getO_path();
    }

    /**
     * @return integer
     */
    public function getO_index() {
        return $this->o_index;
    }

    /**
     * @return integer
     */
    public function getIndex() {
        return $this->getO_index();
    }

    /**
     * @return integer
     */
    public function getO_creationDate() {
        return $this->o_creationDate;
    }

    /**
     * @return integer
     */
    public function getCreationDate() {
        return $this->getO_creationDate();
    }

    /**
     * @return integer
     */
    public function getO_modificationDate() {
        return $this->o_modificationDate;
    }

    /**
     * @return integer
     */
    public function getModificationDate() {
        return $this->getO_modificationDate();
    }

    /**
     * @return integer
     */
    public function getO_userOwner() {
        return $this->o_userOwner;
    }

    /**
     * @return integer
     */
    public function getUserOwner() {
        return $this->getO_userOwner();
    }

    /**
     * @return integer
     */
    public function getO_userModification() {
        return $this->o_userModification;
    }

    /**
     * @return integer
     */
    public function getUserModification() {
        return $this->getO_userModification();
    }


    /**
     * @param int $o_id
     * @return void
     */
    public function setO_id($o_id) {
        $this->o_id = (int) $o_id;
    }

    /**
     * @param int $o_id
     * @return void
     */
    public function setId($o_id) {
        $this->setO_id($o_id);
    }

    /**
     * @param int $o_parentId
     * @return void
     */
    public function setO_parentId($o_parentId) {

        if($this->o_parentId!=null and $o_parentId!=null and $this->o_parentId!=$o_parentId){
            $this->_oldPath=$this->getResource()->getCurrentFullPath();
        }
        $this->o_parentId = (int) $o_parentId;

        try {
            $this->o_parent = Object_Abstract::getById($o_parentId);
        }
        catch (Exception $e) {
        }
    }

    /**
     * @param int $o_parentId
     * @return void
     */
    public function setParentId($o_parentId) {
        $this->setO_parentId($o_parentId);

    }

    /**
     * @param string $o_type
     * @return void
     */
    public function setO_type($o_type) {
        $this->o_type = $o_type;
    }

    /**
     * @param string $o_type
     * @return void
     */
    public function setType($o_type) {
        $this->setO_type($o_type);
    }

    /**
     * @param string $o_key
     * @return void
     */
    public function setO_key($o_key) {
        //set old path so that child paths are updated after this object was saved
        if($this->o_key!=null and $o_key!=null and $o_key!=$this->o_key){
            $this->_oldPath=$this->getResource()->getCurrentFullPath();
        }
        $this->o_key = $o_key;
    }

    /**
     * @param string $o_key
     * @return void
     */
    public function setKey($o_key) {
        $this->setO_key($o_key);
    }

    /**
     * @param string $o_path
     * @return void
     */
    public function setO_path($o_path) {
        $this->o_path = $o_path;
    }
    
    /**
     * @param string $o_path
     * @return void
     */
    public function setPath($o_path) {
        $this->setO_path($o_path);
    }

    /**
     * @param int $o_index
     * @return void
     */
    public function setO_index($o_index) {
        $this->o_index = (int) $o_index;
    }

    /**
     * @param int $o_index
     * @return void
     */
    public function setIndex($o_index) {
        $this->setO_index($o_index);
    }

    /**
     * @param int $o_creationDate
     * @return void
     */
    public function setO_creationDate($o_creationDate) {
        $this->o_creationDate = (int) $o_creationDate;
    }

    /**
     * @param int $o_creationDate
     * @return void
     */
    public function setCreationDate($o_creationDate) {
        $this->setO_creationDate($o_creationDate);
    }

    /**
     * @param int $o_modificationDate
     * @return void
     */
    public function setO_modificationDate($o_modificationDate) {
        $this->o_modificationDate = (int) $o_modificationDate;
    }

    /**
     * @param int $o_modificationDate
     * @return void
     */
    public function setModificationDate($o_modificationDate) {
        $this->setO_modificationDate($o_modificationDate);
    }

    /**
     * @param int $o_userOwner
     * @return void
     */
    public function setO_userOwner($o_userOwner) {
        $this->o_userOwner = (int) $o_userOwner;
    }

    /**
     * @param int $o_userOwner
     * @return void
     */
    public function setUserOwner($o_userOwner) {
        $this->setO_userOwner($o_userOwner);
    }

    /**
     * @param int $o_userModification
     * @return void
     */
    public function setO_userModification($o_userModification) {
        $this->o_userModification = (int) $o_userModification;
    }

    /**
     * @param int $o_userModification
     * @return void
     */
    public function setUserModification($o_userModification) {
        $this->setO_userModification($o_userModification);
    }

    /**
     * @param array $o_childs
     * @return void
     */
    public function setO_childs($o_childs) {
        $this->o_childs = $o_childs;
        if(is_array($o_childs) and count($o_childs)>0){
            $this->o_hasChilds=true;
        } else {
             $this->o_hasChilds=false;   
        }
    }

    /**
     * @param array $o_childs
     * @return void
     */
    public function setChilds($o_childs) {
        $this->setO_childs($o_childs);
    }

    /**
     * @return Object_Abstract
     */
    public function getO_parent() {

        if($this->o_parent === null) {
            $this->setO_parent(Object_Abstract::getById($this->getO_parentId()));
        }

        return $this->o_parent;
    }

    /**
     * @return Object_Abstract
     */
    public function getParent() {
        return $this->getO_parent();
    }

    /**
     * @param Object_Abstract $o_parent
     * @return void
     */
    public function setO_parent($o_parent) {
        $this->o_parent = $o_parent;

        if($o_parent instanceof Object_Abstract) {
            $this->setParentId($o_parent->getId());            
        }
    }

    /**
     * @param Object_Abstract $o_parent
     * @return void
     */
    public function setParent($o_parent) {
        $this->setO_parent($o_parent);
    }   

    /**
     * @return Property[]
     */
    public function getO_properties() {
        if ($this->o_properties === null) {
            // try to get from cache
            $cacheKey = "object_properties_" . $this->getId();
            ;
            if (!$properties = Pimcore_Model_Cache::load($cacheKey)) {
                $properties = $this->getResource()->getProperties();
                Pimcore_Model_Cache::save($properties, $cacheKey, array("object_properties", "properties"));
            }

            $this->setO_Properties($properties);
        }
        return $this->o_properties;
    }

    /**
     * @return array
     */
    public function getProperties() {
        return $this->getO_properties();
    }

    /**
     * @param array $o_properties
     * @return void
     */
    public function setO_properties($o_properties) {
        $this->o_properties = $o_properties;
    }

    /**
     * @param array $o_properties
     * @return void
     */
    public function setProperties($o_properties) {
        $this->setO_properties($o_properties);
    }
    
    /**
     * Get specific property data or the property object itself ($asContainer=true) by it's name, if the property doesn't exists return null
     * @param string $name
     * @param bool $asContainer
     * @return mixed
     */
    public function getProperty($name, $asContainer = false) {
        $properties = $this->getProperties();
        if ($this->hasProperty($name)) {
            if($asContainer) {
                return $properties[$name];
            } else {
                return $properties[$name]->getData();
            }
        }
        return null;
    }

    /**
     * @param  $name
     * @return bool
     */
    public function hasProperty ($name) {
        $properties = $this->getProperties();
        return array_key_exists($name, $properties);
    }
    
    /**
     * set a property
     *
     * @param string $name
     * @param string $type
     * @param mixed $data
     * @param boolean $inherited
     */
    public function setProperty($name, $type, $data, $inherited = false) {
        
        $this->getProperties();
        
        $property = new Property();
        $property->setType($type);
        $property->setCid($this->getId());
        $property->setName($name);
        $property->setCtype("object");
        $property->setData($data);
        $property->setInherited($inherited);
        
        $this->o_properties[$name] = $property;
    }

    /**
     * @return Element_AdminStyle
     */
    public function getO_elementAdminStyle() {
        return $this->getElementAdminStyle();
    }

    /**
     * @return Element_AdminStyle
     */
    public function getElementAdminStyle() {
        if(empty($this->o_elementAdminStyle)) {
            $this->o_elementAdminStyle = new Element_AdminStyle($this);
        }
        return $this->o_elementAdminStyle;
    }


    /**
     * @return string
     */
    public function __toString() {
        return $this->getFullPath();
    }


    /**
     * 
     */
    public function __sleep() {

        $finalVars = array();
        $parentVars = parent::__sleep();

        if(isset($this->_fulldump)) {
            // this is if we want to make a full dump of the object (eg. for a new version), including childs for recyclebin
            $blockedVars = array("o_userPermissions","o_dependencies","o_hasChilds","_oldPath","o_versions","o_class","scheduledTasks","o_parent","omitMandatoryCheck");
            $finalVars[] = "_fulldump";
            $this->removeInheritedProperties();
        } else {
            // this is if we want to cache the object
            $blockedVars = array("o_userPermissions","o_dependencies","o_childs","o_hasChilds","_oldPath","o_versions","o_class","scheduledTasks","o_properties","o_parent","o___loadedLazyFields","omitMandatoryCheck");
        }
        

        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }
    
    
    public function __wakeup() {
        if(isset($this->_fulldump) && $this->o_properties !== null) {
            unset($this->_fulldump);
            $this->renewInheritedProperties();
        }

        if(isset($this->_fulldump)) {
            // set current key and path this is necessary because the serialized data can have a different path than the original element ( element was renamed or moved )
            $originalElement = Object_Abstract::getById($this->getId());
            if($originalElement) {
                $this->setO_key($originalElement->getO_key());
                $this->setO_path($originalElement->getO_path());
            }
        }
    }
    
    public function removeInheritedProperties () {
        
        $myProperties = $this->getO_Properties();
        
        if($myProperties) {
            foreach ($this->getO_Properties() as $name => $property) {
                if($property->getInherited()) {
                    unset($myProperties[$name]);
                }
            }
        }
        
        $this->setO_Properties($myProperties);
    }
    
    public function renewInheritedProperties () {
        $this->removeInheritedProperties();
        
        $myProperties = $this->getO_Properties();
        $inheritedProperties = $this->getResource()->getProperties(true);
        $this->setO_Properties(array_merge($inheritedProperties, $myProperties));
    }
}
