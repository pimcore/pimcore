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
use Pimcore\Tool; 

class Localizedfield extends Model\AbstractModel {

    const STRICT_DISABLED = 0;

    const STRICT_ENABLED = 1;

    private static $getFallbackValues = false;

    /**
     * @var array
     */
    public $items = array();

    /**
     * @var Model\Object\Concrete
     */
    public $object;

    /**
     * @var Model\Object\ClassDefinition
     */
    public $class;

    /**
     * @var bool
     */
    private static $strictMode;

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
    public static function isStrictMode()
    {
        return self::$strictMode;
    }

    /**
     * @param boolean $strictMode
     */
    public static function setStrictMode($strictMode)
    {
        self::$strictMode = $strictMode;
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
     * @param Concrete $object
     * @return void
     */
    public function setObject(Concrete $object)
    {
        $this->object = $object;
        //$this->setClass($this->getObject()->getClass());
        return $this;
    }

    /**
     * @return Concrete
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param Model\Object\ClassDefinition $class
     * @return void
     */
    public function setClass(ClassDefinition $class)
    {
        $this->class = $class;
        return $this;
    }

    /**
     * @return Model\Object\ClassDefinition
     */
    public function getClass()
    {
        if(!$this->class && $this->getObject()) {
            $this->class = $this->getObject()->getClass();
        }
        return $this->class;
    }

    /**
     * @throws \Exception
     * @param null $language
     * @return string
     */
    public function getLanguage ($language = null) {
        if($language) {
            return (string) $language;
        }

        // try to get the language from the registry
        try {
            $locale = \Zend_Registry::get("Zend_Locale");
            if(Tool::isValidLanguage((string) $locale)) {
                return (string) $locale;
            }
            throw new \Exception("Not supported language");
        } catch (\Exception $e) {
            return Tool::getDefaultLanguage();
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

        $fieldDefinition = $this->getObject()->getClass()->getFieldDefinition("localizedfields")->getFieldDefinition($name);
        $language = $this->getLanguage($language);
        $data = null;
        if($this->languageExists($language)) {
            if(array_key_exists($name, $this->items[$language])) {
                $data = $this->items[$language][$name];
            }
        }


        // check for inherited value
        $doGetInheritedValues = AbstractObject::doGetInheritedValues();
        if($fieldDefinition->isEmpty($data) && $doGetInheritedValues) {
            $object = $this->getObject();
            $class = $object->getClass();
            $allowInherit = $class->getAllowInherit();

            if ($allowInherit) {

                if ($object->getParent() instanceof AbstractObject) {
                    $parent = $object->getParent();
                    while($parent && $parent->getType() == "folder") {
                        $parent = $parent->getParent();
                    }

                    if ($parent && ($parent->getType() == "object" || $parent->getType() == "variant")) {
                        if ($parent->getClassId() == $object->getClassId()) {
                            $method = "getLocalizedfields";
                            if (method_exists($parent, $method)) {
                                $localizedFields = $parent->getLocalizedFields();
                                if ($localizedFields instanceof Localizedfield) {
                                    if($localizedFields->object->getId() != $this->object->getId()) {
                                        $data = $localizedFields->getLocalizedValue($name, $language, true);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // check for fallback value
        if($fieldDefinition->isEmpty($data) && !$ignoreFallbackLanguage && self::doGetFallbackValues()) {
            foreach (Tool::getFallbackLanguagesFor($language) as $l) {
                if($this->languageExists($l)) {
                    if(array_key_exists($name, $this->items[$l])) {
                        $data = $this->getLocalizedValue($name, $l);
                    }
                }
            }
        }

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

        if (self::$strictMode) {
            if (!$language || !in_array($language, Tool::getValidLanguages())) {
                throw new \Exception("Language " . $language . " not accepted in strict mode");
            }
        }

        $language  = $this->getLanguage($language);
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
