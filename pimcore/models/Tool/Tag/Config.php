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
 * @package    Tool
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Tool\Tag;

use Pimcore\Model\Cache; 

class Config {

    /**
     * @var array
     */
    public $items = array();

    /**
     * @var string
     */
    public $name = "";

    /**
     * @var string
     */
    public $description = "";

    /**
     * @var int
     */
    public $siteId;

    /**
     * @var string
     */
    public $urlPattern = "";

    /**
     * @var string
     */
    public $textPattern = "";

    /**
     * @var string
     */
    public $httpMethod = "";

    /**
     * @var array
     */
    public $params = array(
        array("name" => "", "value" => ""),
        array("name" => "", "value" => ""),
        array("name" => "", "value" => ""),
        array("name" => "", "value" => ""),
        array("name" => "", "value" => ""),
    );


    /**
     * @param $name
     * @return Config
     * @throws \Exception
     */
    public static function getByName ($name) {
        $tag = new self();
        $tag->setName($name);
        if(!$tag->load()) {
            throw new \Exception("tag definition : " . $name . " does not exist");
        }

        return $tag;
    }

    /**
     * @static
     * @return string
     */
    public static function getWorkingDir () {
        $dir = PIMCORE_CONFIGURATION_DIRECTORY . "/tags";
        if(!is_dir($dir)) {
            \Pimcore\File::mkdir($dir);
        }

        return $dir;
    }


    /**
     * @return void
     */
    public function save () {

        $arrayConfig = object2array($this);
        $items = $arrayConfig["items"];
        $arrayConfig["items"] = array("item" => $items);

        $params = $arrayConfig["params"];
        $arrayConfig["params"] = array("param" => $params);
        
        $config = new \Zend_Config($arrayConfig);
        $writer = new \Zend_Config_Writer_Xml(array(
            "config" => $config,
            "filename" => $this->getConfigFile()
        ));
        $writer->write();

        // clear cache tags
        Cache::clearTags(array("tagmanagement","output"));

        return true;
    }

    /**
     * @return void
     */
    public function load () {

        $configXml = new \Zend_Config_Xml($this->getConfigFile());
        $configArray = $configXml->toArray();

        if(array_key_exists("items",$configArray) && is_array($configArray["items"]["item"])) {
            // if code is in it, that means that there's only one item it it
            if(array_key_exists("code",$configArray["items"]["item"])) {
                $configArray["items"] = array($configArray["items"]["item"]);
            } else {
                $configArray["items"] = $configArray["items"]["item"];
            }
        } else {
            $configArray["items"] = array("item" => array());
        }

        if(array_key_exists("params",$configArray)) {
            $configArray["params"] = $configArray["params"]["param"];
        }

        foreach ($configArray as $key => $value) {
            $setter = "set" . ucfirst($key);
            if(method_exists($this, $setter)) {
                $this->$setter($value);
            }
        }

        return true;
    }

    /**
     * @return void
     */
    public function delete() {
        if(is_file($this->getConfigFile())) {
            unlink($this->getConfigFile());
        }

        // clear cache tags
        Cache::clearTags(array("tagmanagement","output"));
    }

    /**
     * @return string
     */
    protected function getConfigFile () {
        return self::getWorkingDir() . "/" . $this->getName() . ".xml";
    }

    /**
     * @param $parameters
     * @return bool
     */
    public function addItem ($parameters) {
        $this->items[] = $parameters;

        return true;
    }

    /**
     * @param $position
     * @param $parameters
     * @return bool
     */
    public function addItemAt ($position, $parameters) {

        array_splice($this->items, $position, 0, array($parameters));

        return true;
    }


    /**
     * @return void
     */
    public function resetItems () {
        $this->items = array();
    }

    /**
     * @param $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param $items
     * @return $this
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
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $httpMethod
     * @return $this
     */
    public function setHttpMethod($httpMethod)
    {
        $this->httpMethod = $httpMethod;
        return $this;
    }

    /**
     * @return string
     */
    public function getHttpMethod()
    {
        return $this->httpMethod;
    }

    /**
     * @param $urlPattern
     * @return $this
     */
    public function setUrlPattern($urlPattern)
    {
        $this->urlPattern = $urlPattern;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrlPattern()
    {
        return $this->urlPattern;
    }

    /**
     * @param int $siteId
     */
    public function setSiteId($siteId)
    {
        $this->siteId = $siteId;
    }

    /**
     * @return int
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @param $params
     * @return $this
     */
    public function setParams($params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param $textPattern
     * @return $this
     */
    public function setTextPattern($textPattern)
    {
        $this->textPattern = $textPattern;
        return $this;
    }

    /**
     * @return string
     */
    public function getTextPattern()
    {
        return $this->textPattern;
    }
}