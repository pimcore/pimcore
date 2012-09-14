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
 * @package    Asset
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */
 
class Asset_Video_Thumbnail_Config {

    /**
     * format of array:
     * array(
        array(
            "method" => "myName",
            "arguments" =>
                array(
                    "width" => 345,
                    "height" => 200
                )
        )
     * )
     *
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
    public $videoBitrate;

    /**
     * @var int
     */
    public $audioBitrate;

    /**
     * @static
     * @param  $name
     * @return Asset_Image_Thumbnail_Config
     */
    public static function getByName ($name) {
        $pipe = new self();
        $pipe->setName($name);
        if(!is_readable($pipe->getConfigFile()) || !$pipe->load()) {
            throw new Exception("video thumbnail definition : " . $name . " does not exist");
        }

        return $pipe;
    }

    /**
     * @static
     * @return string
     */
    public static function getWorkingDir () {
        $dir = PIMCORE_CONFIGURATION_DIRECTORY . "/videopipelines";
        if(!is_dir($dir)) {
            mkdir($dir);
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
        
        $config = new Zend_Config($arrayConfig);
        $writer = new Zend_Config_Writer_Xml(array(
            "config" => $config,
            "filename" => $this->getConfigFile()
        ));
        $writer->write();

        return true;
    }

    /**
     * @return void
     */
    public function load () {

        $configXml = new Zend_Config_Xml($this->getConfigFile());
        $configArray = $configXml->toArray();

        if(array_key_exists("items",$configArray) && is_array($configArray["items"]["item"])) {
            if(array_key_exists("method",$configArray["items"]["item"])) {
                $configArray["items"] = array($configArray["items"]["item"]);
            } else {
                $configArray["items"] = $configArray["items"]["item"];
            }
        } else {
            $configArray["items"] = array("item" => array());
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
    }

    /**
     * @return string
     */
    protected function getConfigFile () {
        return self::getWorkingDir() . "/" . $this->getName() . ".xml";
    }

    /**
     * @param  $name
     * @param  $parameters
     * @return bool
     */
    public function addItem ($name, $parameters) {
        $this->items[] = array(
            "method" => $name,
            "arguments" => $parameters
        );

        return true;
    }

    /**
     * @param  $name
     * @param  $parameters
     * @return bool
     */
    public function addItemAt ($position, $name, $parameters) {

        array_splice($this->items, $position, 0, array(array(
            "method" => $name,
            "arguments" => $parameters
        )));

        return true;
    }


    /**
     * @return void
     */
    public function resetItems () {
        $this->items = array();
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param array $items
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
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param int $audioBitrate
     */
    public function setAudioBitrate($audioBitrate)
    {
        $this->audioBitrate = (int) $audioBitrate;
    }

    /**
     * @return int
     */
    public function getAudioBitrate()
    {
        return $this->audioBitrate;
    }

    /**
     * @param int $videoBitrate
     */
    public function setVideoBitrate($videoBitrate)
    {
        $this->videoBitrate = (int) $videoBitrate;
    }

    /**
     * @return int
     */
    public function getVideoBitrate()
    {
        return $this->videoBitrate;
    }
}
