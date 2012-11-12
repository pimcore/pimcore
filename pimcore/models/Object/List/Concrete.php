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

abstract class Object_List_Concrete extends Object_List {

    /**
     * @var int
     */
    public $classId;

    /**
     * @var string
     */
    public $className;

    /**
     * @var string|Zend_Locale
     */
    public $locale;

    /**
     * do not use the localized views for this list (in the case the class contains localized fields),
     * conditions on localized fields are not possible
     * @var bool
     */
    public $ignoreLocalizedFields = false;

    /**
    * ignore the locale and use the concatinated object_localized_ID_default view instead
    * @var bool
    */
    public $ignoreLocale;


    /**
     * @return void
     */
    public function __construct() {

        $this->objectTypeObject = true;
        $this->initResource("Object_List_Concrete");

    }

    /**
     * @todo remove always true
     * @param string $key
     * @return boolean
     */
    public function isValidOrderKey($key) {
        return true;
    }

    /**
     * @return integer
     */
    public function getClassId() {
        return $this->classId;
    }

    /**
     * @return string
     */
    public function getClassName() {
        return $this->className;
    }

    /**
     * @param int $classId
     */
    public function setClassId($classId) {
        $this->classId = $classId;
    }

    /**
     * @param string $className
     */
    public function setClassName($className) {
        $this->className = $className;
    }

    /**
     * @return Object_Class
     */
    public function getClass() {
        $class = Object_Class::getById($this->getClassId());
        return $class;
    }

    /**
     * @param mixed $locale
     * @return void
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return string|Zend_Locale
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param bool $ignoreLocale
     * @return void
     */
    public function setIgnoreLocale($ignoreLocale)
    {
        $this->ignoreLocale = $ignoreLocale;
    }

    /**
     * @return bool
     */
    public function getIgnoreLocale()
    {
        return $this->ignoreLocale;
    }

    /**
     * @param bool $ignoreLocale
     * @return void
     */
    public function setIgnoreLocalizedFields($ignoreLocalizedFields)
    {
        $this->ignoreLocalizedFields = $ignoreLocalizedFields;
    }

    /**
     * @return bool
     */
    public function getIgnoreLocalizedFields()
    {
        return $this->ignoreLocalizedFields;
    }


    /**
     * field collection queries
     * @var array
     */
    private $fieldCollectionConfigs = array();

    /**
     * @param $type
     * @param null $fieldname
     * @throws Exception
     */
    public function addFieldCollection($type, $fieldname = null) {

        if(empty($type)) {
            throw new Exception("No fieldcollectiontype given");
        }

        Object_Fieldcollection_Definition::getByKey($type);
        $this->fieldCollectionConfigs[] = array("type" => $type, "fieldname" => $fieldname);  ;
    }

    /**
     * @param $fieldCollections
     */
    public function setFieldCollections($fieldCollections) {
        foreach($fieldCollections as $fc) {
            $this->addFieldCollection($fc['type'], $fc['fieldname']);
        }
    }

    /**
     * @return array
     */
    public function getFieldCollections() {
        return $this->fieldCollectionConfigs;
    }


    /**
     * object brick queries
     * @var array
     */
    private $objectBrickConfigs = array();

    /**
     * @param $type
     * @throws Exception
     */
    public function addObjectbrick($type) {

        if(empty($type)) {
            throw new Exception("No objectbrick given");
        }

        Object_Objectbrick_Definition::getByKey($type);
        if (!in_array($type, $this->objectBrickConfigs)) {
            $this->objectBrickConfigs[] = $type;
        }
    }

    /**
     * @param $objectbricks
     */
    public function setObjectbricks($objectbricks) {
        foreach($objectbricks as $ob) {
            if(!in_array($ob,$this->objectBrickConfigs)){
                $this->addObjectbrick($ob);
            }
        }
    }

    /**
     * @return array
     */
    public function getObjectbricks() {
        return $this->objectBrickConfigs;
    }



}
