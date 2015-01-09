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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Asset\Video\Thumbnail;

class Config {

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
     * @param $name
     * @return Config
     * @throws \Exception
     */
    public static function getByName ($name) {
        $pipe = new self();
        $pipe->setName($name);
        if(!is_readable($pipe->getConfigFile()) || !$pipe->load()) {
            throw new \Exception("video thumbnail definition : " . $name . " does not exist");
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
            \Pimcore\File::mkdir($dir);
        }

        return $dir;
    }

    /**
     * @return Config
     */
    public static function getPreviewConfig () {
        $config = new self();
        $config->setName("pimcore-system-treepreview");
        $config->setAudioBitrate(128);
        $config->setVideoBitrate(700);

        $config->setItems(array(
            array(
                "method" => "scaleByWidth",
                "arguments" =>
                array(
                    "width" => 500
                )
            )
        ));

        return $config;
    }

    /**
     * @return void
     */
    public function save () {

        $arrayConfig = object2array($this);
        $items = $arrayConfig["items"];
        $arrayConfig["items"] = array("item" => $items);
        
        $config = new \Zend_Config($arrayConfig);
        $writer = new \Zend_Config_Writer_Xml(array(
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

        $configXml = new \Zend_Config_Xml($this->getConfigFile());
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
     * @param $audioBitrate
     * @return $this
     */
    public function setAudioBitrate($audioBitrate)
    {
        $this->audioBitrate = (int) $audioBitrate;
        return $this;
    }

    /**
     * @return int
     */
    public function getAudioBitrate()
    {
        return $this->audioBitrate;
    }

    /**
     * @param $videoBitrate
     * @return $this
     */
    public function setVideoBitrate($videoBitrate)
    {
        $this->videoBitrate = (int) $videoBitrate;
        return $this;
    }

    /**
     * @return int
     */
    public function getVideoBitrate()
    {
        return $this->videoBitrate;
    }

    /**
     * @return array
     */
    public function getEstimatedDimensions() {

        $dimensions = array();
        $transformations = $this->getItems();
        if(is_array($transformations) && count($transformations) > 0) {
            foreach ($transformations as $transformation) {
                if(!empty($transformation)) {
                    if(is_array($transformation["arguments"])) {
                        foreach ($transformation["arguments"] as $key => $value) {
                            if($key == "width" || $key == "height") {
                                $dimensions[$key] = $value;
                            }
                        }
                    }
                }
            }
        }

        return $dimensions;
    }
}
