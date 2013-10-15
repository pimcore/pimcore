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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Localizedfield extends Pimcore_Model_Abstract {

    private static $getFallbackValues = false;

    /**
     * @var array
     */
    public $items = array();

    /**
     * @var Object_Concrete
     */
    public $object;

    /**
     * @var Object_Class
     */
    public $class;

    /**
     * @param boolean $getFallbackValues
     */
    public static function setGetFallbackValues($getFallbackValues)
    {
        self::$getFallbackValues = $getFallbackValues;
    }

    /**
     * @return boolean
     */
    public static function getGetFallbackValues()
    {
        return self::$getFallbackValues;
    }

    /**
     * @return boolean
     */
    public static function doGetFallbackValues()
    {
        return self::$getFallbackValues;
    }

    /**
     * @param array $items
     */
    public function __construct($items = null) {
        if($items) {
            $this->setItems($items);
        }
    }

    /**
     * @param  $item
     * @return void
     */
    public function addItem($item)
    {
        $this->items[] = $item;
    }

    /**
     * @param  array $items
     * @return void
     */
    public function setItems($items)
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param Object_Concrete $object
     * @return void
     */
    public function setObject(Object_Concrete $object)
    {
        $this->object = $object;
        //$this->setClass($this->getObject()->getClass());
        return $this;
    }

    /**
     * @return Object_Concrete
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param Object_Class $class
     * @return void
     */
    public function setClass(Object_Class $class)
    {
        $this->class = $class;
        return $this;
    }

    /**
     * @return Object_Class
     */
    public function getClass()
    {
        if(!$this->class && $this->getObject()) {
            $this->class = $this->getObject()->getClass();
        }
        return $this->class;
    }

    /**
     * @throws Exception
     * @param null $language
     * @return string
     */
    public function getLanguage ($language = null) {
        if($language) {
            return (string) $language;
        }

        // try to get the language from the registry
        try {
            $locale = Zend_Registry::get("Zend_Locale");
            if(Pimcore_Tool::isValidLanguage((string) $locale)) {
                return (string) $locale;
            }
            throw new Exception("Not supported language");
        } catch (Exception $e) {
            return Pimcore_Tool::getDefaultLanguage();
        }
    }

    /**
     * @param $language
     * @return bool
     */
    public function languageExists ($language) {
        return array_key_exists($language, $this->getItems());
    }

    /**
     * @param $name
     * @param null $language
     * @return 
     */
    public function getLocalizedValue ($name, $language = null, $ignoreFallbackLanguage = false) {
        $language = $this->getLanguage($language);
        $data = null;
        if($this->languageExists($language)) {
            if(array_key_exists($name, $this->items[$language])) {
                $data = $this->items[$language][$name];
            }
        }


        // check for inherited value
        $doGetInheritedValues = Object_Abstract::doGetInheritedValues();
        if(!$data && $doGetInheritedValues) {
            $object = $this->getObject();
            $class = $object->getClass();
            $allowInherit = $class->getAllowInherit();

            if ($allowInherit) {

                if ($object->getParent() instanceof Object_Abstract) {
                    $parent = $object->getParent();
                    while($parent && $parent->getType() == "folder") {
                        $parent = $parent->getParent();
                    }

                    if ($parent && ($parent->getType() == "object" || $parent->getType() == "variant")) {
                        if ($parent->getClassId() == $object->getClassId()) {
                            $method = "getLocalizedfields";
                            if (method_exists($parent, $method)) {
                                $localizedFields = $parent->getLocalizedFields();
                                if ($localizedFields instanceof Object_Localizedfield) {
                                    $data = $localizedFields->getLocalizedValue($name, $language, true);
                                }
                            }
                        }
                    }
                }
            }
        }

        // check for fallback value
        if(!$data && !$ignoreFallbackLanguage && self::doGetFallbackValues()) {
            foreach (Pimcore_Tool::getFallbackLanguagesFor($language) as $l) {
                if($this->languageExists($l)) {
                    if(array_key_exists($name, $this->items[$l])) {
                        $data = $this->getLocalizedValue($name, $l);
                    }
                }
            }
        }

        $fieldDefinition = $this->getObject()->getClass()->getFieldDefinition("localizedfields")->getFieldDefinition($name);
        if($fieldDefinition && method_exists($fieldDefinition, "preGetData")) {
            $data =  $fieldDefinition->preGetData($this, array(
                "data" => $data,
                "language" => $language,
                "name" => $name
            ));
        }

        return $data;
    }

    /**
     * @param $name
     * @param $value
     * @param null $language
     * @return void
     */
    public function setLocalizedValue ($name, $value, $language = null) {
        $language = $this->getLanguage($language);
        if(!$this->languageExists($language)) {
            $this->items[$language] = array();
        }

        $fieldDefinition = $this->getObject()->getClass()->getFieldDefinition("localizedfields")->getFieldDefinition($name);

        if(method_exists($fieldDefinition, "preSetData")) {
            $value =  $fieldDefinition->preSetData($this, $value, array(
                "language" => $language,
                "name" => $name
            ));
        }

        $this->items[$language][$name] = $value;
        return $this;
    }

    /**
     * @return array
     */
    public function __sleep() {
        return array("items");
    }
}
