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
 
class Object_Concrete extends Object_Abstract {

    public static $systemColumnNames = array("id", "fullpath", "published", "creationDate", "modificationDate", "filename", "classname");

    /**
     * @var boolean
     */
    public $o_published;
    
    /**
     * @var Object_Class
     */
    public $o_class;
    
    /**
     * @var integer
     */
    public $o_classId;

    /**
     * @var string
     */
    public $o_className;

    /**
     * @var array
     */
    public $o_versions = null;

    /**
     * @var array
     */
    public $lazyLoadedFields = array ();

    /**
     * @var array
     */
    public $o___loadedLazyFields = array();

    /**
     * Contains all scheduled tasks
     *
     * @var array
     */
    public $scheduledTasks = null;


    /**
     * @var bool
     */
    protected $omitMandatoryCheck = false;


    /**
     *
     */
    public function __construct () {
        // nothing to do here
    }

    /**
     * @param  string $fieldName
     * @return void
     */
    public function addLazyLoadedField($fieldName){
        $this->lazyLoadedFields[]=$fieldName;
    }

    /**
     * @return array
     */
    public function getLazyLoadedFields(){
        return (array) $this->lazyLoadedFields;
    }

    /**
     * @param array $o___loadedLazyFields
     * @return void
     */
    public function setO__loadedLazyFields(array $o___loadedLazyFields) {
        $this->o___loadedLazyFields = $o___loadedLazyFields;
    }

    /**
     * @return array
     */
    public function getO__loadedLazyFields() {
        return $this->o___loadedLazyFields;
    }

    /**
     * @param string $o___loadedLazyField
     * @return void
     */
    public function addO__loadedLazyField($o___loadedLazyField) {
        $this->o___loadedLazyFields[] = $o___loadedLazyField;
    }

    /**
     * @return void
     */
    protected function update() {


        $fieldDefintions = $this->getO_class()->getFieldDefinitions();
        foreach($fieldDefintions as $fd){
            $getter = "get".ucfirst($fd->getName());
            $setter = "set".ucfirst($fd->getName());

            if(method_exists($this, $getter)){

                //To make sure, inherited values are not set again
                $inheritedValues = Object_Abstract::doGetInheritedValues();
                Object_Abstract::setGetInheritedValues(false);

                $value = $this->$getter();

                if(is_array($value) and ($fd instanceof Object_Class_Data_Multihref or $fd instanceof Object_Class_Data_Objects)){
                    //don't save relations twice
                    $this->$setter(array_unique($value));
                }
                Object_Abstract::setGetInheritedValues($inheritedValues);

                $value = $this->$getter();

                $omitMandatoryCheck = $this->getOmitMandatoryCheck();

                /*$timeSinceCreation = (time()-$this->getCreationDate());
                if($timeSinceCreation <= 5){
                    // legacy hack: in previous version there was no check for mandatory fields,
                    // and everybody uses the save method for new object creation - so now let's evict the mandatory check
                    // if the object was created within the last 5 seconds
                    $omitMandatoryCheck=true;
                    Logger::debug("executing mandatory fields check for object [ ".$this->getId()." ]");
                }
                */
                
                //check throws Exception
                try {
                    $fd->checkValidity($value, $omitMandatoryCheck);
                } catch (Exception $e) {

                    if($this->getO_class()->getAllowInherit()) {
                        //try again with parent data when inheritance in activated
                        try {

                            $getInheritedValues = Object_Abstract::doGetInheritedValues();
                            Object_Abstract::setGetInheritedValues(true);

                            $value = $this->$getter();
                            $fd->checkValidity($value, $omitMandatoryCheck);

                            Object_Abstract::setGetInheritedValues($getInheritedValues);

                        } catch(Exception $e) {
                            throw new Exception($e->getMessage() . " fieldname=" . $fd->getName());
                        }
                    } else {
                        throw new Exception($e->getMessage() . " fieldname=" . $fd->getName());
                    }
                }
            }

        }

        parent::update();

        $this->getResource()->update();
        $this->saveScheduledTasks();
        $this->saveVersion(false, false);
        $this->saveChilds();
        
        Pimcore_API_Plugin_Broker::getInstance()->postUpdateObject($this);

        // this is called already in parent::update() but we have too call it here again, because there are again
        // modifications after parent::update();, maybe this should be solved better, but for now this works fine
        $this->clearDependedCache();
    }

    /**
     * @return void
     */
    public function saveChilds () {
        if($this->getClass()->getAllowInherit()) {
            $this->getResource()->saveChilds();
        }
    }

    /**
     * @return void
     */
    public function saveScheduledTasks () {
        // update scheduled tasks
        $this->getScheduledTasks();
        $this->getResource()->deleteAllTasks();

        if (is_array($this->getScheduledTasks()) && count($this->getScheduledTasks()) > 0) {
            foreach ($this->getScheduledTasks() as $task) {
                $task->setId(null);
                $task->setResource(null);
                $task->setCid($this->getO_Id());
                $task->setCtype("object");
                $task->save();
            }
        }
    }

    /**
     * @return void
     */
    public function delete() {

        // delete all versions
        foreach ($this->getO_versions() as $v) {
            $v->delete();
        }

        $this->getResource()->deleteAllTasks();

        parent::delete();
    }

    /**
     * $directCall is true when the method is called from outside (eg. directly in the controller "save only version")
     * it is false when the method is called by $this->update()
     * @param bool $setModificationDate
     * @param bool $directCall
     * @return Version
     */
    public function saveVersion($setModificationDate = true, $directCall = true) {

        if ($setModificationDate) {
            $this->setO_modificationDate(time());
        }

        // hook should be also called if "save only new version" is selected
        if($directCall) {
            Pimcore_API_Plugin_Broker::getInstance()->preUpdateObject($this);
        }

        // scheduled tasks are saved always, they are not versioned!
        if($directCall) {
            $this->saveScheduledTasks();
        }

        $version = null;

        // only create a new version if there is at least 1 allowed
        if(Pimcore_Config::getSystemConfig()->objects->versions) {
            // create version
            $version = new Version();
            $version->setCid($this->getO_Id());
            $version->setCtype("object");
            $version->setDate($this->getO_modificationDate());
            $version->setUserId($this->getO_userModification());
            $version->setData($this);
            $version->save();
        }

        // hook should be also called if "save only new version" is selected
        if($directCall) {
            Pimcore_API_Plugin_Broker::getInstance()->postUpdateObject($this);
        }

        return $version;
    }

    /**
     * @return array
     */
    public function getO_versions() {
        if ($this->o_versions === null) {
            $this->setO_Versions($this->getResource()->getVersions());
        }
        return $this->o_versions;
    }

    /**
     * @return array
     */
    public function getVersions () {
        return $this->getO_versions();
    }

    /**
     * @param array $o_versions
     * @return void
     */
    public function setO_versions($o_versions) {
        $this->o_versions = $o_versions;
    }

    /**
     * @param array $o_versions
     * @return void
     */
    public function setVersions ($o_versions) {
        $this->setO_versions($o_versions);
    }


    /**
     * @param string $key
     * @return void
     */
    public function getValueForFieldName($key) {
        if ($this->$key) {
            return $this->$key;
        }
        return false;
    }

    /**
     * @return array
     */
    public function getCacheTags($tags = array()) {
        
        $tags = is_array($tags) ? $tags : array();

        $tags = parent::getCacheTags($tags);

        $tags["class_" . $this->getO_classId()] = "class_" . $this->getO_classId();
        foreach ($this->getO_class()->getFieldDefinitions() as $name => $def) {
            // no need to add lazy-loading fields to the cache tags
            if (!method_exists($def, "getLazyLoading") or !$def->getLazyLoading()) {
                $tags = $def->getCacheTags($this->getValueForFieldName($name), $this, $tags);
            }
        }
        return $tags;
    }

    /**
     * @return array
     */
    public function resolveDependencies() {

        $dependencies = parent::resolveDependencies();

        // check in fields
        if ($this->geto_class() instanceof Object_Class) {
        	foreach ($this->geto_class()->getFieldDefinitions() as $field) {
        		$key = $field->getName();
                $dependencies = array_merge($dependencies, $field->resolveDependencies($this->$key));
        	}
        }
        return $dependencies;
    }

    /**
     * @param Object_Class $o_class
     */
    public function setO_class($o_class) {
        $this->o_class = $o_class;
    }

    /**
     * @param int $o_class
     * @return void
     */
    public function setClass($o_class) {
        $this->setO_class($o_class);
    }

    /**
     * @return Object_Class
     */
    public function getO_class() {
        if (!$this->o_class) {
            $this->setO_class(Object_Class::getById($this->getO_classId()));
        }
        return $this->o_class;
    }

    /**
     * @return Object_Class
     */
    public function getClass() {
        return $this->getO_class();
    }

    /**
     * @return integer
     */
    public function getO_classId() {
        return (int) $this->o_classId;
    }

    /**
     * @return integer
     */
    public function getClassId() {
        return $this->getO_classId();
    }

    /**
     * @param int $o_classId
     * @return void
     */
    public function setO_classId($o_classId) {
        $this->o_classId = (int) $o_classId;
    }

    /**
     * @param int $o_classId
     * @return void
     */
    public function setClassId($o_classId) {
        $this->setO_classId($o_classId);
    }

    /**
     * @return string
     */
    public function getO_className() {
        return $this->o_className;
    }

    /**
     * @param string $o_className
     * @return void
     */
    public function setO_className($o_className) {
        $this->o_className = $o_className;
    }

    /**
     * @return string
     */
    public function getClassName() {
        return $this->getO_className();
    }

    /**
     * @param string $o_className
     * @return void
     */
    public function setClassName($o_className) {
        $this->setO_className($o_className);
    }


    /**
     * @return boolean
     */
    public function getO_published() {
        return (bool) $this->o_published;
    }

    /**
     * @return boolean
     */
    public function getPublished() {
        return (bool) $this->getO_published();
    }

    /**
     * @return boolean
     */
    public function isPublished() {
        return (bool) $this->getO_published();
    }

    /**
     * @param boolean $o_published
     * @return void
     */
    public function setO_published($o_published) {
        $this->o_published = (bool) $o_published;
    }

    /**
     * @param boolean $o_published
     * @return void
     */
    public function setPublished($o_published) {
        $this->setO_published($o_published);
    }

    /**
     * @param boolean $omitMandatoryCheck
     */
    public function setOmitMandatoryCheck($omitMandatoryCheck)
    {
        $this->omitMandatoryCheck = $omitMandatoryCheck; 
    }

    /**
     * @return boolean
     */
    public function getOmitMandatoryCheck()
    {
        return $this->omitMandatoryCheck;
    }

    /**
     * @return array
     */
    public function getScheduledTasks() {
        if ($this->scheduledTasks === null) {
            $taskList = new Schedule_Task_List();
            $taskList->setCondition("cid = ? AND ctype='object'", $this->getO_Id());
            $this->scheduledTasks = $taskList->load();
        }
        return $this->scheduledTasks;
    }

    /**
     * @param array $scheduledTasks
     */
    public function setScheduledTasks($scheduledTasks) {
        $this->scheduledTasks = $scheduledTasks;
    }

    /**
     * @return mixed
     */
    public function getValueFromParent($key) {
        if ($this->getO_parent() instanceof Object_Abstract) {

            $parent = $this->getO_parent();
            while($parent && $parent->getO_type() == "folder") {
                $parent = $parent->getO_parent();
            }

            if ($parent && ($parent->getO_type() == "object" || $parent->getO_type() == "variant")) {
                if ($parent->getO_classId() == $this->getO_classId()) {
                    $method = "get" . $key;
                    if (method_exists($parent, $method)) {
                        return $parent->$method();
                    }
                }
            }
        }
        return;
    }



    /**
     * Dummy which can be overwritten by a parent class, this is a hook executed in every getter of the properties in the object
     * @param string $key
     */
    public function preGetValue ($key) {
        return;
    }

    /**
     * get object relation data as array for a specific field
     *
     * @param string $fieldName
     * @param bool $forOwner
     * @return array
     */
    public function getRelationData($fieldName,$forOwner,$remoteClassId){
        $relationData = $this->getResource()->getRelationData($fieldName,$forOwner,$remoteClassId);
        return $relationData;
    }


    /**
     * @return Object_Concrete|array
     */
    public static function __callStatic ($method, $arguments) {

        // check for custom static getters like Object::getByMyfield()
        $propertyName = lcfirst(preg_replace("/^getBy/i","",$method));
        $tmpObj = new static();

        // get real fieldname (case sensitive)
        $fieldnames = array();
        foreach ($tmpObj->getClass()->getFieldDefinitions() as $fd) {
            $fieldnames[] = $fd->getName();
        }
        $propertyName = implode("",preg_grep('/^' . preg_quote($propertyName, '/') . '$/i', $fieldnames));

        if(property_exists($tmpObj,$propertyName)) {

            // check if the given fieldtype is valid for this shorthand
            $allowedDataTypes = array("input","numeric","checkbox","country","date","datetime","image","language","multihref","multiselect","select","slider","time","user");

            $field = $tmpObj->getClass()->getFieldDefinition($propertyName);
            if(!in_array($field->getFieldType(), $allowedDataTypes)) {
                throw new Exception("Static getter '::getBy".ucfirst($propertyName)."' is not allowed for fieldtype '" . $field->getFieldType() . "', it's only allowed for the following fieldtypes: " . implode(",",$allowedDataTypes));
            }

            list($value, $limit, $offset) = $arguments;

            $listConfig = array(
                "condition" => $propertyName . " = '" . $value . "'"
            );

            if($limit) {
                $listConfig["limit"] = $limit;
            }
            if($offset) {
                $listConfig["offset"] = $offset;
            }

            $list = static::getList($listConfig);

            if($limit == 1) {
                $elements = $list->getObjects();
                return $elements[0];
            }

            return $list;
        }

        // there is no property for the called method, so throw an exception
        Logger::error("Class: Object_Concrete => call to undefined static method " . $method);
        throw new Exception("Call to undefined static method " . $method . " in class Object_Concrete" );
    }


    /**
     *
     */
    public function __sleep() {

        $parentVars = parent::__sleep();

        $finalVars = array();
        foreach ($parentVars as $key) {
            if (in_array($key, $this->getLazyLoadedFields())) {
                // prevent lazyloading properties to go into the cache, only to version and recyclebin, ... (_fulldump)
                if(isset($this->_fulldump)) {
                    $finalVars[] = $key;
                }
            } else {
                $finalVars[] = $key;
            }
        } 

        return $finalVars;
    }
}
