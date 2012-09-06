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

abstract class Pimcore_Placeholder_Abstract
{
    /**
     * The placeholder string e.g "%Object(object_id,{"method" : "getName"})"
     *
     * @var string
     */
    protected $placeholderString = null;

    /**
     * The placeholder key passed to determine the dynamic parameter
     *
     * @var string
     */
    protected $placeholderKey = null;

    /**
     * The config object passed from the placeholder
     * If no config object was passed a empty Zend_Config_Json is passed
     *
     * @var Zend_Config_Json
     */
    protected $placeholderConfig = null;

    /**
     * The passed Document Object
     *
     * @var Document | null
     */
    protected $document = null;

    /**
     * All dynamic parameters which are passed to the Placeholder
     *
     * @var array
     */
    protected $params = array();

    /**
     * The Content as string
     *
     * @var string
     */
    protected $contentString = null;

    /**
     * @var Zend_Locale
     */
    protected $locale = null;

    /*
     * Sets the Placeholder string e.g "%Object(object_id,{"method" : "getName"})"
     * @var string $string
     */
    public function setPlaceholderString($string)
    {
        $this->placeholderString = $string;
    }

    /*
     * Returns the Placeholder string
     */
    public function getPlaceholderString()
    {
        return $this->placeholderString;
    }

    /**
     * Sets the Placeholder key (first parameter of the placeholder)
     *
     * @param string $key
     */
    public function setPlaceholderKey($key)
    {
        $this->placeholderKey = $key;
    }

    /**
     * Returns the Placehodler key
     *
     * @return string
     */
    public function getPlaceholderKey()
    {
        return $this->placeholderKey;
    }

    /**
     * Sets the Placeholder config object (passed as second parameter)
     *
     * @param Zend_Config_Json $config
     */
    public function setPlaceholderConfig(Zend_Config_Json $config)
    {
        $this->placeholderConfig = $config;
    }

    /**
     * Returns the Placeholder config object
     *
     * @return Zend_Config_Json
     */
    public function getPlaceholderConfig()
    {
        return $this->placeholderConfig;
    }

    /**
     * Sets parameters to the Placeholder object
     *
     * @param array $params
     */
    public function setParams($params)
    {
        if (is_array($params)) {
            $this->params = $params;
        }
    }

    /**
     * Returns the Parameters ob the Placeholder object
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Returns a specific parameter
     *
     * @param string $key
     * @return mixed
     */
    public function getParam($key)
    {
        return $this->params[$key];
    }

    /**
     * Sets the full content string
     *
     * @param string $contentString
     */
    public function setContentString($contentString)
    {
        if (is_string($contentString)) {
            $this->contentString = $contentString;
        }
    }

    /**
     * returns the full content string
     *
     * @return null|string
     */
    public function getContentString()
    {
        return $this->contentString;
    }

    /**
     * Returns the the value of the current Placeholder parameter
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->getParam($this->getPlaceholderKey());
    }

    /**
     * Sets a document
     *
     * @param Document | null $document
     */
    public function setDocument($document)
    {
        if ($document instanceof Document) {
            $this->document = $document;
        }
    }

    /**
     * Returns the Document
     *
     * @return Document|null
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * Returns the current locale
     *
     * @return Zend_Locale
     */
    public function getLocale()
    {
        if (is_null($this->locale)) {
            $this->setLocale();
        }
        return $this->locale;
    }

    /**
     * Try to set the locale from different sources
     *
     * @param $locale
     * @return void
     */
    public function setLocale($locale = null)
    {
        if ($locale instanceof Zend_Locale) {
            $this->locale = $locale;
        } elseif (is_string($locale)) {
            $this->locale = new Zend_Locale($locale);
        } elseif ($this->getParam('locale') || $this->getParam('language')) {
            $this->setLocale(($this->getParam('locale')) ? $this->getParam('locale') : $this->getParam('language'));
        } else {
            $document = $this->getDocument();
            if ($document instanceof Document && $document->getProperty("language")) {
                $this->setLocale($document->getProperty("language"));
            }

            if (is_null($this->locale)) { //last chance -> get it from registry or use the first Language defined in the system settings
                if(Zend_Registry::isRegistered("Zend_Locale")) {
                    $this->locale = Zend_Registry::get("Zend_Locale");
                } else {
                    list($language) = Pimcore_Tool::getValidLanguages();
                    $this->locale = new Zend_Locale($language);
                }
            }
        }
    }

    /**
     * Returns the current language
     *
     * @return string
     */
    public function getLanguage()
    {
        return (string) $this->getLocale();
    }

    /**
     * Will be used as replacement if the passed parameter is empty
     *
     * @return string
     */
    public function getEmptyValue()
    {
        return '';
    }


    /**
     * Has to return an appropriate value for a test replacement
     *
     * @abstract
     * @return string
     */
    abstract function getTestValue();

    /**
     * Has to return the placeholder with the corresponding value
     *
     * @abstract
     * @return string
     */
    abstract function getReplacement();

}