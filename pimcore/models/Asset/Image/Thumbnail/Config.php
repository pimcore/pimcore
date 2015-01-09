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

namespace Pimcore\Model\Asset\Image\Thumbnail;

use Pimcore\Tool\Serialize;

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
     * @var array
     */
    public $medias = array();

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
     * @var float
     */
    public $highResolution;

    /**
     * @var string
     */
    public $filenameSuffix;

    /**
     * @param $config
     * @return self|bool
     */
    public static function getByAutoDetect ($config) {

        $thumbnail = null;

        if (is_string($config)) {
            try {
                $thumbnail = self::getByName($config);
            }
            catch (\Exception $e) {
                \Logger::error("requested thumbnail " . $config . " is not defined");
                return false;
            }
        }
        else if (is_array($config)) {
            // check if it is a legacy config or a new one
            if(array_key_exists("items", $config)) {
                $thumbnail = self::getByArrayConfig($config);
            } else {
                $thumbnail = self::getByLegacyConfig($config);
            }
        }
        else if ($config instanceof self) {
            $thumbnail = $config;
        }

        return $thumbnail;
    }

    /**
     * @static
     * @param  $name
     * @return self
     */
    public static function getByName ($name) {

        $cacheKey = "imagethumb_" . crc32($name);

        if(\Zend_Registry::isRegistered($cacheKey)) {
            $pipe = \Zend_Registry::get($cacheKey);
            $pipe->setName($name); // set the name again because in documents there's an automated prefixing logic
        } else {
            $pipe = new self();
            $pipe->setName($name);
            if(!is_readable($pipe->getConfigFile()) || !$pipe->load()) {
                throw new \Exception("thumbnail definition : " . $name . " does not exist");
            }

            \Zend_Registry::set($cacheKey, $pipe);
        }

        // only return clones of configs, this is necessary since we cache the configs in the registry (see above)
        // sometimes, e.g. when using the cropping tools, the thumbnail configuration is modified on-the-fly, since
        // pass-by-reference this modifications would then go to the cache/registry (singleton), by cloning the config
        // we can bypass this problem in an elegant way without parsing the XML config again and again
        $clone = clone $pipe;

        return $clone;
    }

    /**
     * @static
     * @return string
     */
    public static function getWorkingDir () {
        $dir = PIMCORE_CONFIGURATION_DIRECTORY . "/imagepipelines";
        if(!is_dir($dir)) {
            \Pimcore\File::mkdir($dir);
        }

        return $dir;
    }

    public static function getPreviewConfig () {
        $thumbnail = new self();
        $thumbnail->setName("pimcore-system-treepreview");
        $thumbnail->addItem("scaleByWidth", array(
            "width" => 400
        ));
        $thumbnail->addItem("setBackgroundColor", array(
            "color" => "#323232"
        ));
        $thumbnail->setQuality(60);
        $thumbnail->setFormat("PJPEG");

        return $thumbnail;
    }

    /**
     * Returns thumbnail config for webservice export.
     */
    public function getForWebserviceExport() {
        $arrayConfig = object2array($this);
        $items = $arrayConfig["items"];
        $arrayConfig["items"] = $items;
        return $arrayConfig;
    }


    /**
     * @return void
     */
    public function save () {

        $arrayConfig = object2array($this);

        $items = $arrayConfig["items"];
        $arrayConfig["items"] = array("item" => $items);

        if(!empty($this->medias)) {
            $medias = [];
            foreach ($arrayConfig["medias"] as $name => $items) {
                $medias[] = array(
                    "name" => $name,
                    "items" => array("item" => $items)
                );
            }
            $arrayConfig["medias"] = array("media" => $medias);
        } else {
            // do not include the medias node if empty
            unset($arrayConfig["medias"]);
        }

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

        $medias = [];
        if(array_key_exists("medias", $configArray) && !empty($configArray["medias"]) && is_array($configArray["medias"]["media"])) {

            if(array_key_exists("name", $configArray["medias"]["media"])) {
                $configArray["medias"]["media"] = array($configArray["medias"]["media"]);
            }

            foreach ($configArray["medias"]["media"] as $media) {
                if(array_key_exists("items",$media) && is_array($media["items"]["item"])) {
                    if(array_key_exists("method",$media["items"]["item"])) {
                        $medias[$media["name"]] = array($media["items"]["item"]);
                    } else {
                        $medias[$media["name"]] = $media["items"]["item"];
                    }
                } else {
                    $medias[$media["name"]] = array("item" => array());
                }
            }
        }

        $configArray["medias"] = $medias;

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
     * @param string $name
     */
    protected function createMediaIfNotExists($name) {
        if(!array_key_exists($name, $this->medias)) {
            $this->medias[$name] = [];
        }
    }

    /**
     * @param  $name
     * @param  $parameters
     * @return bool
     */
    public function addItem ($name, $parameters, $media = null) {

        $item = array(
            "method" => $name,
            "arguments" => $parameters
        );

        // default is added to $this->items for compatibility reasons
        if(!$media || $media == "default") {
            $this->items[] = $item;
        } else {
            $this->createMediaIfNotExists($media);
            $this->medias[$media][] = $item;
        }

        return true;
    }

    /**
     * @param  $name
     * @param  $parameters
     * @return bool
     */
    public function addItemAt ($position, $name, $parameters, $media = null) {

        if(!$media || $media == "default") {
            $itemContainer = &$this->items;
        } else {
            $this->createMediaIfNotExists($media);
            $itemContainer = &$this->medias[$media];
        }

        array_splice($itemContainer, $position, 0, array(array(
            "method" => $name,
            "arguments" => $parameters
        )));

        return true;
    }


    /**
     * @return void
     */
    public function resetItems () {
        $this->items = [];
        $this->medias = [];
    }

    /**
     * @param $name
     * @return bool
     */
    public function selectMedia($name) {
        if(array_key_exists($name, $this->medias)) {
            $this->setItems($this->medias[$name]);

            $suffix = strtolower($name);
            $suffix = preg_replace("/[^a-z\-0-9]/", "-", $suffix);
            $suffix = trim($suffix, "-");
            $suffix = preg_replace("/[\-]+/", "-", $suffix);

            $this->setFilenameSuffix($suffix);
            return true;
        }
        return false;
    }

    /**
     * @param string $description
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
     * @param array $items
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
     * @param string $name
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
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
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
        return $this;
    }

    /**
     * @return mixed
     */
    public function getQuality()
    {
        return $this->quality;
    }

    /**
     * @param float $highResolution
     */
    public function setHighResolution($highResolution)
    {
        $this->highResolution = (float) $highResolution;
    }

    /**
     * @return float
     */
    public function getHighResolution()
    {
        return $this->highResolution;
    }

    /**
     * @param array $medias
     */
    public function setMedias($medias)
    {
        $this->medias = $medias;
    }

    /**
     * @return array
     */
    public function getMedias()
    {
        return $this->medias;
    }

    /**
     * @return bool
     */
    public function hasMedias() {
        return !empty($this->medias);
    }

    /**
     * @param string $filenameSuffix
     */
    public function setFilenameSuffix($filenameSuffix)
    {
        $this->filenameSuffix = $filenameSuffix;
    }

    /**
     * @return string
     */
    public function getFilenameSuffix()
    {
        return $this->filenameSuffix;
    }

    /**
     * @static
     * @param $config
     * @return self
     */
    public static function getByArrayConfig ($config) {
        $pipe = new self();

        if(isset($config["format"]) && $config["format"]) {
            $pipe->setFormat($config["format"]);
        }
        if(isset($config["quality"]) && $config["quality"]) {
            $pipe->setQuality($config["quality"]);
        }
        if(isset($config["items"]) && $config["items"]) {
            $pipe->setItems($config["items"]);
        }

        if(isset($config["highResolution"]) && $config["highResolution"]) {
            $pipe->setHighResolution($config["highResolution"]);
        }

        // set name
        $hash = md5(Serialize::serialize($config));
        $pipe->setName("auto_" . $hash);

        return $pipe;
    }

    /**
     * This is just for compatibility, this method will be removed with the next major release
     * @depricated
     * @static
     * @param $config
     * @return self
     */
    public static function getByLegacyConfig ($config) {

        $pipe = new self();
        $hash = md5(Serialize::serialize($config));
        $pipe->setName("auto_" . $hash);

        if(isset($config["format"])) {
            $pipe->setFormat($config["format"]);
        }
        if(isset($config["quality"])) {
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



        if (isset($config["cover"])) {
            $pipe->addItem("cover", array(
                "width" => $config["width"],
                "height" => $config["height"],
                "positioning" => "center"
            ));
        }
        else if (isset($config["contain"])) {
            $pipe->addItem("contain", array(
                "width" => $config["width"],
                "height" => $config["height"]
            ));
        }
        else if (isset($config["frame"])) {
            $pipe->addItem("frame", array(
                "width" => $config["width"],
                "height" => $config["height"]
            ));
        }
        else if (isset($config["aspectratio"]) && $config["aspectratio"]) {

            if (isset($config["height"]) && isset($config["width"]) && $config["height"] > 0 && $config["width"] > 0) {
                $pipe->addItem("contain", array(
                    "width" => $config["width"],
                    "height" => $config["height"]
                ));
            }
            else if (isset($config["height"]) && $config["height"] > 0) {
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
            if(!isset($config["width"]) && isset($config["height"])) {
                $pipe->addItem("scaleByHeight", array(
                    "height" => $config["height"]
                ));
            } else if (isset($config["width"]) && !isset($config["height"])) {
                $pipe->addItem("scaleByWidth", array(
                    "width" => $config["width"]
                ));
            } else if (isset($config["width"]) && isset($config["height"])) {
                $pipe->addItem("resize", array(
                    "width" => $config["width"],
                    "height" => $config["height"]
                ));
            }
        }

        if(isset($config["highResolution"])) {
            $pipe->setHighResolution($config["highResolution"]);
        }

        return $pipe;
    }


    public function getEstimatedDimensions($originalWidth = null, $originalHeight = null) {


        $dimensions = array();
        $transformations = $this->getItems();
        if(is_array($transformations) && count($transformations) > 0) {
            if($originalWidth && $originalHeight) {
                // this is the more accurate method than the other below
                $dimensions["width"] = $originalWidth;
                $dimensions["height"] = $originalHeight;

                foreach ($transformations as $transformation) {
                    if(!empty($transformation)) {
                        $arg = $transformation["arguments"];
                        if(in_array($transformation["method"], ["resize","cover","frame", "crop"])) {
                            $dimensions["width"] = $arg["width"];
                            $dimensions["height"] = $arg["height"];
                        } else if ($transformation["method"] == "scaleByWidth") {
                            if($arg["width"] <= $dimensions["width"]) {
                                $dimensions["height"] = round(($arg["width"] / $dimensions["width"]) * $dimensions["height"], 0);
                                $dimensions["width"] = $arg["width"];
                            }
                        } else if ($transformation["method"] == "scaleByHeight") {
                            if($arg["height"] < $dimensions["height"]) {
                                $dimensions["width"] = round(($arg["height"] / $dimensions["height"]) * $dimensions["width"], 0);
                                $dimensions["height"] = $arg["height"];
                            }
                        } else if ($transformation["method"] == "contain") {
                            $x = $dimensions["width"] / $arg["width"];
                            $y = $dimensions["height"] / $arg["height"];
                            if ($x <= 1 && $y <= 1) {
                                continue;
                            } else if ($x > $y) {
                                $dimensions["height"] = round(($arg["width"] / $dimensions["width"]) * $dimensions["height"], 0);
                                $dimensions["width"] = $arg["width"];
                            } else {
                                $dimensions["width"] = round(($arg["height"] / $dimensions["height"]) * $dimensions["width"], 0);
                                $dimensions["height"] = $arg["height"];
                            }
                        } else if ($transformation["method"] == "cropPercent") {
                            $dimensions["width"] = ceil($dimensions["width"] * ($arg["width"] / 100));
                            $dimensions["height"] = ceil($dimensions["height"] * ($arg["height"] / 100));
                        }
                    }
                }
            } else {
                foreach ($transformations as $transformation) {
                    if(!empty($transformation)) {
                        if(is_array($transformation["arguments"]) && in_array($transformation["method"], ["resize","scaleByWidth","scaleByHeight","cover","frame"]) ) {
                            foreach ($transformation["arguments"] as $key => $value) {
                                if($key == "width" || $key == "height") {
                                    $dimensions[$key] = $value;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $dimensions;
    }


    /**
     * @param string $colorspace
     */
    public function setColorspace($colorspace)
    {
        // no functionality, just for compatibility reasons
    }

    /**
     * @return string
     */
    public function getColorspace()
    {
        // no functionality, just for compatibility reasons
    }
}