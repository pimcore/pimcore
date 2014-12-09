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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object;

use Pimcore\Model;
use Pimcore\Config; 

class Concrete extends AbstractObject {

    public static $systemColumnNames = array("id", "fullpath", "published", "creationDate", "modificationDate", "filename", "classname");

    /**
     * @var boolean
     */
    public $o_published;
    
    /**
     * @var Object|Class
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
     * returns the class ID of the current object class
     * @return int
     */
    public static function classId() {
        $v = get_class_vars(get_called_class());
        return $v["o_classId"];
    }

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
        return $this;
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
     * @throws \Exception
     */
    protected function update() {


        $fieldDefintions = $this->getClass()->getFieldDefinitions();
        foreach($fieldDefintions as $fd){
            $getter = "get".ucfirst($fd->getName());
            $setter = "set".ucfirst($fd->getName());

            if(method_exists($this, $getter)){

                //To make sure, inherited values are not set again
                $inheritedValues = AbstractObject::doGetInheritedValues();
                AbstractObject::setGetInheritedValues(false);

                $value = $this->$getter();

                if(is_array($value) and ($fd instanceof ClassDefinition\Data\Multihref or $fd instanceof ClassDefinition\Data\Objects)){
                    //don't save relations twice
                    $this->$setter(array_unique($value));
                }
                AbstractObject::setGetInheritedValues($inheritedValues);

                $value = $this->$getter();

                $omitMandatoryCheck = $this->getOmitMandatoryCheck();

                /*$timeSinceCreation = (time()-$this->getCreationDate());
                if($timeSinceCreation <= 5){
                    // legacy hack: in previous version there was no check for mandatory fields,
                    // and everybody uses the save method for new object creation - so now let's evict the mandatory check
                    // if the object was created within the last 5 seconds
                    $omitMandatoryCheck=true;
                    \Logger::debug("executing mandatory fields check for object [ ".$this->getId()." ]");
                }
                */
                
                //check throws Exception
                try {
                    $fd->checkValidity($value, $omitMandatoryCheck);
                } catch (\Exception $e) {

                    if($this->getClass()->getAllowInherit()) {
                        //try again with parent data when inheritance in activated
                        try {

                            $getInheritedValues = AbstractObject::doGetInheritedValues();
                            AbstractObject::setGetInheritedValues(true);

                            $value = $this->$getter();
                            $fd->checkValidity($value, $omitMandatoryCheck);

                            AbstractObject::setGetInheritedValues($getInheritedValues);

                        } catch(\Exception $e) {
                            throw new \Exception($e->getMessage() . " fieldname=" . $fd->getName());
                        }
                    } else {
                        throw new \Exception($e->getMessage() . " fieldname=" . $fd->getName());
                    }
                }
            }

        }

        parent::update();

        $this->getResource()->update();

        // scheduled tasks are saved in $this->saveVersion();

        $this->saveVersion(false, false);
        $this->saveChilds();
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
                $task->setCid($this->getId());
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
        foreach ($this->getVersions() as $v) {
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
    public function saveVersion($setModificationDate = true, $callPluginHook = true) {

        if ($setModificationDate) {
            $this->setModificationDate(time());
        }

        // hook should be also called if "save only new version" is selected
        if($callPluginHook) {
            \Pimcore::getEventManager()->trigger("object.preUpdate", $this);
        }

        // scheduled tasks are saved always, they are not versioned!
        $this->saveScheduledTasks();

        $version = null;

        // only create a new version if there is at least 1 allowed
        if(Config::getSystemConfig()->objects->versions->steps
            || Config::getSystemConfig()->objects->versions->days) {
            // create version
            $version = new Model\Version();
            $version->setCid($this->getId());
            $version->setCtype("object");
            $version->setDate($this->getModificationDate());
            $version->setUserId($this->getUserModification());
            $version->setData($this);
            $version->save();
        }

        // hook should be also called if "save only new version" is selected
        if($callPluginHook) {
            \Pimcore::getEventManager()->trigger("object.postUpdate", $this);
        }

        return $version;
    }

    /**
     * @return array
     */
    public function getVersions() {
        if ($this->o_versions === null) {
            $this->setVersions($this->getResource()->getVersions());
        }
        return $this->o_versions;
    }

    /**
     * @param array $o_versions
     * @return void
     */
    public function setVersions($o_versions) {
        $this->o_versions = $o_versions;
        return $this;
    }

    /**
     * @param string $key
     * @return mixed
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

        $tags["class_" . $this->getClassId()] = "class_" . $this->getClassId();
        foreach ($this->getClass()->getFieldDefinitions() as $name => $def) {
            // no need to add lazy-loading fields to the cache tags
            if (!method_exists($def, "getLazyLoading") or !$def->getLazyLoading()) {
                $tags = $def->getCacheTags($this->getValueForFieldName($name), $tags);
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
        if ($this->getClass() instanceof ClassDefinition) {
        	foreach ($this->getClass()->getFieldDefinitions() as $field) {
        		$key = $field->getName();
                $dependencies = array_merge($dependencies, $field->resolveDependencies($this->$key));
        	}
        }
        return $dependencies;
    }

    /**
     * @param ClassDefinition $o_class
     */
    public function setClass($o_class) {
        $this->o_class = $o_class;
        return $this;
    }

    /**
     * @return ClassDefinition
     */
    public function getClass() {
        if (!$this->o_class) {
            $this->setClass(ClassDefinition::getById($this->getClassId()));
        }
        return $this->o_class;
    }

    /**
     * @return integer
     */
    public function getClassId() {
        return (int) $this->o_classId;
    }

    /**
     * @param int $o_classId
     * @return void
     */
    public function setClassId($o_classId) {
        $this->o_classId = (int) $o_classId;
        return $this;
    }

    /**
     * @return string
     */
    public function getClassName() {
        return $this->o_className;
    }

    /**
     * @param string $o_className
     * @return void
     */
    public function setClassName($o_className) {
        $this->o_className = $o_className;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getPublished() {
        return (bool) $this->o_published;
    }

    /**
     * @return boolean
     */
    public function isPublished() {
        return (bool) $this->getPublished();
    }

    /**
     * @param boolean $o_published
     * @return void
     */
    public function setPublished($o_published) {
        $this->o_published = (bool) $o_published;
        return $this;
    }

    /**
     * @param boolean $omitMandatoryCheck
     */
    public function setOmitMandatoryCheck($omitMandatoryCheck)
    {
        $this->omitMandatoryCheck = $omitMandatoryCheck;
        return $this;
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
            $taskList = new Model\Schedule\Task\Listing();
            $taskList->setCondition("cid = ? AND ctype='object'", $this->getId());
            $this->scheduledTasks = $taskList->load();
        }
        return $this->scheduledTasks;
    }

    /**
     * @param array $scheduledTasks
     */
    public function setScheduledTasks($scheduledTasks) {
        $this->scheduledTasks = $scheduledTasks;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValueFromParent($key, $params = null) {
        if ($this->getParent() instanceof AbstractObject) {

            $parent = $this->getParent();
            while($parent && $parent->getType() == "folder") {
                $parent = $parent->getParent();
            }

            if ($parent && ($parent->getType() == "object" || $parent->getType() == "variant")) {
                if ($parent->getClassId() == $this->getClassId()) {
                    $method = "get" . $key;
                    if (method_exists($parent, $method)) {
                        if (method_exists($parent, $method)) {
                            return call_user_func(array($parent, $method), $params);
                        }
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
     * @param $method
     * @param $arguments
     * @throws \Exception
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
            $allowedDataTypes = array("input","numeric","checkbox","country","date","datetime","image","language","multihref","multiselect","select","slider","time","user","email","firstname","lastname");

            $field = $tmpObj->getClass()->getFieldDefinition($propertyName);
            if(!in_array($field->getFieldType(), $allowedDataTypes)) {
                throw new \Exception("Static getter '::getBy".ucfirst($propertyName)."' is not allowed for fieldtype '" . $field->getFieldType() . "', it's only allowed for the following fieldtypes: " . implode(",",$allowedDataTypes));
            }

            $arguments = array_pad($arguments, 3, 0);
            list($value, $limit, $offset) = $arguments;

            $defaultCondition = $propertyName . " = " . \Pimcore\Resource::get()->quote($value) . " ";
            $listConfig = array(
                "condition" => $defaultCondition
            );

            if(!is_array($limit)){
                if($limit) {
                    $listConfig["limit"] = $limit;
                }
                if($offset) {
                    $listConfig["offset"] = $offset;
                }
            } else {
                $listConfig = array_merge($listConfig,$limit);
                $listConfig['condition'] = $defaultCondition . $limit['condition'];
            }

            $list = static::getList($listConfig);

            if(isset($listConfig['limit']) && $listConfig['limit'] == 1) {
                $elements = $list->getObjects();
                return $elements[0];
            }

            return $list;
        }

        // there is no property for the called method, so throw an exception
        \Logger::error("Class: Object\\Concrete => call to undefined static method " . $method);
        throw new \Exception("Call to undefined static method " . $method . " in class Object\\Concrete" );
    }


    /**
     *
     */
    public function __sleep() {

        $parentVars = parent::__sleep();

        $finalVars = array();
        $lazyLoadedFields = $this->getLazyLoadedFields();

        foreach ($parentVars as $key) {
            if (in_array($key, $lazyLoadedFields)) {
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

    /**
     *
     */
    public function __wakeup() {

        parent::__wakeup();

        // renew localized fields
        // do not use the getter ($this->getLocalizedfields()) as it somehow slows down the process around a sec
        // no clue why this happens
        if(property_exists($this, "localizedfields") && $this->localizedfields instanceof Localizedfield) {
            $this->localizedfields->setObject($this);
        }
    }
}
