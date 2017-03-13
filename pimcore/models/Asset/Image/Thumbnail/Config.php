<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Asset
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset\Image\Thumbnail;

use Pimcore\Tool\Serialize;
use Pimcore\Model;
use Pimcore\Logger;

/**
 * @method \Pimcore\Model\Asset\Image\Thumbnail\Config\Dao getDao()
 */
class Config extends Model\AbstractModel
{

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
    public $items = [];

    /**
     * @var array
     */
    public $medias = [];

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
     * @var bool
     */
    public $preserveColor = false;

    /**
     * @var bool
     */
    public $preserveMetaData = false;

    /**
     * @var int
     */
    public $modificationDate;

    /**
     * @var int
     */
    public $creationDate;

    /**
     * @var string
     */
    public $filenameSuffix;

    /**
     * @param $config
     * @return self|bool
     */
    public static function getByAutoDetect($config)
    {
        $thumbnail = null;

        if (is_string($config)) {
            try {
                $thumbnail = self::getByName($config);
            } catch (\Exception $e) {
                Logger::error("requested thumbnail " . $config . " is not defined");

                return false;
            }
        } elseif (is_array($config)) {
            // check if it is a legacy config or a new one
            if (array_key_exists("items", $config)) {
                $thumbnail = self::getByArrayConfig($config);
            } else {
                $thumbnail = self::getByLegacyConfig($config);
            }
        } elseif ($config instanceof self) {
            $thumbnail = $config;
        }

        return $thumbnail;
    }

    /**
     * @param $name
     * @return null|Config
     */
    public static function getByName($name)
    {
        $cacheKey = "imagethumb_" . crc32($name);

        try {
            $thumbnail = \Pimcore\Cache\Runtime::get($cacheKey);
            $thumbnail->setName($name);
            if (!$thumbnail) {
                throw new \Exception("Thumbnail in registry is null");
            }
        } catch (\Exception $e) {
            try {
                $thumbnail = new self();
                $thumbnail->setName($name);
                $thumbnail->getDao()->getByName();

                \Pimcore\Cache\Runtime::set($cacheKey, $thumbnail);
            } catch (\Exception $e) {
                return null;
            }
        }

        // only return clones of configs, this is necessary since we cache the configs in the registry (see above)
        // sometimes, e.g. when using the cropping tools, the thumbnail configuration is modified on-the-fly, since
        // pass-by-reference this modifications would then go to the cache/registry (singleton), by cloning the config
        // we can bypass this problem in an elegant way without parsing the XML config again and again
        $clone = clone $thumbnail;

        return $clone;
    }

    /**
     * @return Config
     */
    public static function getPreviewConfig()
    {
        $thumbnail = new self();
        $thumbnail->setName("pimcore-system-treepreview");
        $thumbnail->addItem("scaleByWidth", [
            "width" => 400
        ]);
        $thumbnail->addItem("setBackgroundImage", [
            "path" => "/pimcore/static6/img/tree-preview-transparent-background.png",
            "mode" => "cropTopLeft"
        ]);
        $thumbnail->setQuality(60);
        $thumbnail->setFormat("PJPEG");

        return $thumbnail;
    }

    /**
     * Returns thumbnail config for webservice export.
     */
    public function getForWebserviceExport()
    {
        $arrayConfig = object2array($this);
        $items = $arrayConfig["items"];
        $arrayConfig["items"] = $items;

        return $arrayConfig;
    }

    /**
     * @param string $name
     */
    protected function createMediaIfNotExists($name)
    {
        if (!array_key_exists($name, $this->medias)) {
            $this->medias[$name] = [];
        }
    }

    /**
     * @param $name
     * @param $parameters
     * @param $media
     * @return bool
     */
    public function addItem($name, $parameters, $media = null)
    {
        $item = [
            "method" => $name,
            "arguments" => $parameters
        ];

        // default is added to $this->items for compatibility reasons
        if (!$media || $media == "default") {
            $this->items[] = $item;
        } else {
            $this->createMediaIfNotExists($media);
            $this->medias[$media][] = $item;
        }

        return true;
    }

    /**
     * @param $position
     * @param $name
     * @param $parameters
     * @param $media
     * @return bool
     */
    public function addItemAt($position, $name, $parameters, $media = null)
    {
        if (!$media || $media == "default") {
            $itemContainer = &$this->items;
        } else {
            $this->createMediaIfNotExists($media);
            $itemContainer = &$this->medias[$media];
        }

        array_splice($itemContainer, $position, 0, [[
            "method" => $name,
            "arguments" => $parameters
        ]]);

        return true;
    }

    public function resetItems()
    {
        $this->items = [];
        $this->medias = [];
    }

    /**
     * @param $name
     * @return bool
     */
    public function selectMedia($name)
    {
        if (array_key_exists($name, $this->medias)) {
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
        if ($quality) {
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
    public function hasMedias()
    {
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
    public static function getByArrayConfig($config)
    {
        $pipe = new self();

        if (isset($config["format"]) && $config["format"]) {
            $pipe->setFormat($config["format"]);
        }
        if (isset($config["quality"]) && $config["quality"]) {
            $pipe->setQuality($config["quality"]);
        }
        if (isset($config["items"]) && $config["items"]) {
            $pipe->setItems($config["items"]);
        }

        if (isset($config["highResolution"]) && $config["highResolution"]) {
            $pipe->setHighResolution($config["highResolution"]);
        }

        // set name
        $hash = md5(Serialize::serialize($pipe));
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
    public static function getByLegacyConfig($config)
    {
        $pipe = new self();

        if (isset($config["format"])) {
            $pipe->setFormat($config["format"]);
        }

        if (isset($config["quality"])) {
            $pipe->setQuality($config["quality"]);
        }

        if (isset($config["cover"])) {
            $pipe->addItem("cover", [
                "width" => $config["width"],
                "height" => $config["height"],
                "positioning" => ((isset($config["positioning"]) && !empty($config["positioning"])) ? (string)$config["positioning"] : "center"),
                "forceResize" => (isset($config["forceResize"]) ? (bool)$config["forceResize"] : false)
            ]);
        } elseif (isset($config["contain"])) {
            $pipe->addItem("contain", [
                "width" => $config["width"],
                "height" => $config["height"],
                "forceResize" => (isset($config["forceResize"]) ? (bool)$config["forceResize"] : false)
            ]);
        } elseif (isset($config["frame"])) {
            $pipe->addItem("frame", [
                "width" => $config["width"],
                "height" => $config["height"],
                "forceResize" => (isset($config["forceResize"]) ? (bool)$config["forceResize"] : false)
            ]);
        } elseif (isset($config["aspectratio"]) && $config["aspectratio"]) {
            if (isset($config["height"]) && isset($config["width"]) && $config["height"] > 0 && $config["width"] > 0) {
                $pipe->addItem("contain", [
                    "width" => $config["width"],
                    "height" => $config["height"],
                    "forceResize" => (isset($config["forceResize"]) ? (bool)$config["forceResize"] : false)
                ]);
            } elseif (isset($config["height"]) && $config["height"] > 0) {
                $pipe->addItem("scaleByHeight", [
                    "height" => $config["height"],
                    "forceResize" => (isset($config["forceResize"]) ? (bool)$config["forceResize"] : false)
                ]);
            } else {
                $pipe->addItem("scaleByWidth", [
                    "width" => $config["width"],
                    "forceResize" => (isset($config["forceResize"]) ? (bool)$config["forceResize"] : false)
                ]);
            }
        } else {
            if (!isset($config["width"]) && isset($config["height"])) {
                $pipe->addItem("scaleByHeight", [
                    "height" => $config["height"],
                    "forceResize" => (isset($config["forceResize"]) ? (bool)$config["forceResize"] : false)
                ]);
            } elseif (isset($config["width"]) && !isset($config["height"])) {
                $pipe->addItem("scaleByWidth", [
                    "width" => $config["width"],
                    "forceResize" => (isset($config["forceResize"]) ? (bool)$config["forceResize"] : false)
                ]);
            } elseif (isset($config["width"]) && isset($config["height"])) {
                $pipe->addItem("resize", [
                    "width" => $config["width"],
                    "height" => $config["height"]
                ]);
            }
        }

        if (isset($config["highResolution"])) {
            $pipe->setHighResolution($config["highResolution"]);
        }

        $hash = md5(Serialize::serialize($pipe));
        $pipe->setName("auto_" . $hash);

        return $pipe;
    }


    /**
     * @param $asset
     * @return array
     */
    public function getEstimatedDimensions($asset)
    {
        $originalWidth = $asset->getWidth();
        $originalHeight = $asset->getHeight();

        $dimensions = [];
        $transformations = $this->getItems();
        if (is_array($transformations) && count($transformations) > 0) {
            if ($originalWidth && $originalHeight) {
                // this is the more accurate method than the other below
                $dimensions["width"] = $originalWidth;
                $dimensions["height"] = $originalHeight;

                foreach ($transformations as $transformation) {
                    if (!empty($transformation)) {
                        $arg = $transformation["arguments"];
                        if (in_array($transformation["method"], ["resize", "cover", "frame", "crop"])) {
                            $dimensions["width"] = $arg["width"];
                            $dimensions["height"] = $arg["height"];
                        } elseif ($transformation["method"] == "scaleByWidth") {
                            if ($arg["width"] <= $dimensions["width"] || $asset->isVectorGraphic()) {
                                $dimensions["height"] = round(($arg["width"] / $dimensions["width"]) * $dimensions["height"], 0);
                                $dimensions["width"] = $arg["width"];
                            }
                        } elseif ($transformation["method"] == "scaleByHeight") {
                            if ($arg["height"] < $dimensions["height"] || $asset->isVectorGraphic()) {
                                $dimensions["width"] = round(($arg["height"] / $dimensions["height"]) * $dimensions["width"], 0);
                                $dimensions["height"] = $arg["height"];
                            }
                        } elseif ($transformation["method"] == "contain") {
                            $x = $dimensions["width"] / $arg["width"];
                            $y = $dimensions["height"] / $arg["height"];

                            if ($x <= 1 && $y <= 1 && !$asset->isVectorGraphic()) {
                                continue;
                            }

                            if ($x > $y) {
                                $dimensions["height"] = round(($arg["width"] / $dimensions["width"]) * $dimensions["height"], 0);
                                $dimensions["width"] = $arg["width"];
                            } else {
                                $dimensions["width"] = round(($arg["height"] / $dimensions["height"]) * $dimensions["width"], 0);
                                $dimensions["height"] = $arg["height"];
                            }
                        } elseif ($transformation["method"] == "cropPercent") {
                            $dimensions["width"] = ceil($dimensions["width"] * ($arg["width"] / 100));
                            $dimensions["height"] = ceil($dimensions["height"] * ($arg["height"] / 100));
                        } elseif (in_array($transformation["method"], ["rotate", "trim"])) {
                            // unable to calculate dimensions -> return empty
                            return [];
                        }
                    }
                }
            } else {
                // this method is only if we don't have the source dimensions
                // this doesn't necessarily return both with & height
                // and is only a very rough estimate, you should avoid falling back to this functionality
                foreach ($transformations as $transformation) {
                    if (!empty($transformation)) {
                        if (is_array($transformation["arguments"]) && in_array($transformation["method"], ["resize", "scaleByWidth", "scaleByHeight", "cover", "frame"])) {
                            foreach ($transformation["arguments"] as $key => $value) {
                                if ($key == "width" || $key == "height") {
                                    $dimensions[$key] = $value;
                                }
                            }
                        }
                    }
                }
            }
        }

        // ensure we return int's, sometimes $arg[...] contain strings
        $dimensions["width"] = (int) $dimensions["width"];
        $dimensions["height"] = (int) $dimensions["height"];

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

    /**
     * @return int
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param int $modificationDate
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;
    }

    /**
     * @return int
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param int $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
    }

    /**
     * @return boolean
     */
    public function isPreserveColor()
    {
        return $this->preserveColor;
    }

    /**
     * @param boolean $preserveColor
     */
    public function setPreserveColor($preserveColor)
    {
        $this->preserveColor = $preserveColor;
    }

    /**
     * @return boolean
     */
    public function isPreserveMetaData()
    {
        return $this->preserveMetaData;
    }

    /**
     * @param boolean $preserveMetaData
     */
    public function setPreserveMetaData($preserveMetaData)
    {
        $this->preserveMetaData = $preserveMetaData;
    }
}
