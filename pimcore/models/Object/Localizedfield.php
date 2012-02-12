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

class Object_Localizedfield extends Pimcore_Model_Abstract {

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
        $this->setClass($this->getObject()->getClass());
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
    }

    /**
     * @return Object_Class
     */
    public function getClass()
    {
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
            // try to get default from system settings
            $conf = Pimcore_Config::getSystemConfig();
            if($conf->general->validLanguages) {
                $languages = explode(",",$conf->general->validLanguages);
                return $languages[0];
            }
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
    public function getLocalizedValue ($name, $language = null) {
        $language = $this->getLanguage($language);
        if($this->languageExists($language)) {
            if(array_key_exists($name, $this->items[$language])) {
                return $this->items[$language][$name];
            }
        }
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

        $this->items[$language][$name] = $value;
    }
}
