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
 
class Asset_Image_Thumbnail_Config {

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
     * @var string
     */
    public $format = "SOURCE";

    /**
     * @var mixed
     */
    public $quality = 100;


    /**
     * @static
     * @param  $name
     * @return Asset_Image_Thumbnail_Config
     */
    public static function getByName ($name) {
        $pipe = new self();
        $pipe->setName($name);
        $pipe->load();

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

        if(array_key_exists("method",$configArray["items"]["item"])) {
            $configArray["items"] = array($configArray["items"]["item"]);
        } else {
            $configArray["items"] = $configArray["items"]["item"];
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
        $this->quality = $quality;
    }

    /**
     * @return mixed
     */
    public function getQuality()
    {
        return $this->quality;
    }
}