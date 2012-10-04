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
 
class Asset_Image_Thumbnail_Config {

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
     * @var string
     */
    public $format = "SOURCE";

    /**
     * @var mixed
     */
    public $quality = 90;

    /**
     * @static
     * @param  $name
     * @return Asset_Image_Thumbnail_Config
     */
    public static function getByName ($name) {
        $pipe = new self();
        $pipe->setName($name);
        if(!is_readable($pipe->getConfigFile()) || !$pipe->load()) {
            throw new Exception("thumbnail definition : " . $name . " does not exist");
        }

        return $pipe;
    }

    /**
     * @static
     * @return string
     */
    public static function getWorkingDir () {
        $dir = PIMCORE_CONFIGURATION_DIRECTORY . "/imagepipelines";
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
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param mixed $quality
     */
    public function setQuality($quality)
    {
        if($quality) {
            $this->quality = (int) $quality;
        }
    }

    /**
     * @return mixed
     */
    public function getQuality()
    {
        return $this->quality;
    }

    /**
     * @static
     * @param $config
     * @return Asset_Image_Thumbnail_Config
     */
    public static function getByArrayConfig ($config) {
        $pipe = new Asset_Image_Thumbnail_Config();

        if($config["format"]) {
            $pipe->setFormat($config["format"]);
        }
        if($config["quality"]) {
            $pipe->setQuality($config["quality"]);
        }
        if($config["items"]) {
            $pipe->setItems($config["items"]);
        }

        // set name
        $hash = md5(Pimcore_Tool_Serialize::serialize($config));
        $pipe->setName("auto_" . $hash);

        return $pipe;
    }

    /**
     * This is just for compatibility, this method will be removed with the next major release
     * @depricated
     * @static
     * @param $config
     * @return Asset_Image_Thumbnail_Config
     */
    public static function getByLegacyConfig ($config) {

        $pipe = new Asset_Image_Thumbnail_Config();
        $hash = md5(Pimcore_Tool_Serialize::serialize($config));
        $pipe->setName("auto_" . $hash);

        if($config["format"]) {
            $pipe->setFormat($config["format"]);
        }
        if($config["quality"]) {
            $pipe->setQuality($config["quality"]);
        }
        /*if ($config["cropPercent"]) {
            $pipe->addItem("cropPercent", array(
                "width" => $config["cropWidth"],
                "height" => $config["cropHeight"],
                "y" => $config["cropTop"],
                "x" => $config["cropLeft"]
            ));
        }*/



        if ($config["cover"]) {
            $pipe->addItem("cover", array(
                "width" => $config["width"],
                "height" => $config["height"],
                "positioning" => "center"
            ));
        }
        else if ($config["contain"]) {
            $pipe->addItem("contain", array(
                "width" => $config["width"],
                "height" => $config["height"]
            ));
        }
        else if ($config["frame"]) {
            $pipe->addItem("frame", array(
                "width" => $config["width"],
                "height" => $config["height"]
            ));
        }
        else if ($config["aspectratio"]) {

            if ($config["height"] > 0 && $config["width"] > 0) {
                $pipe->addItem("contain", array(
                    "width" => $config["width"],
                    "height" => $config["height"]
                ));
            }
            else if ($config["height"] > 0) {
                $pipe->addItem("scaleByHeight", array(
                    "height" => $config["height"]
                ));
            }
            else {
                $pipe->addItem("scaleByWidth", array(
                    "width" => $config["width"]
                ));
            }
        }
        else {
            if(empty($config["width"]) && !empty($config["height"])) {
                $pipe->addItem("scaleByHeight", array(
                    "height" => $config["height"]
                ));
            } else if (!empty($config["width"]) && empty($config["height"])) {
                $pipe->addItem("scaleByWidth", array(
                    "width" => $config["width"]
                ));
            } else {
                $pipe->addItem("resize", array(
                    "width" => $config["width"],
                    "height" => $config["height"]
                ));
            }
        }

        return $pipe;
    }
}