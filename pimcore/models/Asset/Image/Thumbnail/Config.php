<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Asset
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Asset\Image\Thumbnail;

use Pimcore\Tool\Serialize;
use Pimcore\Model;

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
                \Logger::error("requested thumbnail " . $config . " is not defined");
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
            $thumbnail = \Zend_Registry::get($cacheKey);
            $thumbnail->setName($name);
            if (!$thumbnail) {
                throw new \Exception("Thumbnail in registry is null");
            }
        } catch (\Exception $e) {
            try {
                $thumbnail = new self();
                $thumbnail->setName($name);
                $thumbnail->getDao()->getByName();

                \Zend_Registry::set($cacheKey, $thumbnail);
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

    public static function getPreviewConfig()
    {
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
     * @param  $name
     * @param  $parameters
     * @return bool
     */
    public function addItem($name, $parameters, $media = null)
    {
        $item = array(
            "method" => $name,
            "arguments" => $parameters
        );

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
     * @param  $name
     * @param  $parameters
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

        array_splice($itemContainer, $position, 0, array(array(
            "method" => $name,
            "arguments" => $parameters
        )));

        return true;
    }


    /**
     * @return void
     */
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
            $pipe->addItem("cover", array(
                "width" => $config["width"],
                "height" => $config["height"],
                "positioning" => "center"
            ));
        } elseif (isset($config["contain"])) {
            $pipe->addItem("contain", array(
                "width" => $config["width"],
                "height" => $config["height"]
            ));
        } elseif (isset($config["frame"])) {
            $pipe->addItem("frame", array(
                "width" => $config["width"],
                "height" => $config["height"]
            ));
        } elseif (isset($config["aspectratio"]) && $config["aspectratio"]) {
            if (isset($config["height"]) && isset($config["width"]) && $config["height"] > 0 && $config["width"] > 0) {
                $pipe->addItem("contain", array(
                    "width" => $config["width"],
                    "height" => $config["height"]
                ));
            } elseif (isset($config["height"]) && $config["height"] > 0) {
                $pipe->addItem("scaleByHeight", array(
                    "height" => $config["height"]
                ));
            } else {
                $pipe->addItem("scaleByWidth", array(
                    "width" => $config["width"]
                ));
            }
        } else {
            if (!isset($config["width"]) && isset($config["height"])) {
                $pipe->addItem("scaleByHeight", array(
                    "height" => $config["height"]
                ));
            } elseif (isset($config["width"]) && !isset($config["height"])) {
                $pipe->addItem("scaleByWidth", array(
                    "width" => $config["width"]
                ));
            } elseif (isset($config["width"]) && isset($config["height"])) {
                $pipe->addItem("resize", array(
                    "width" => $config["width"],
                    "height" => $config["height"]
                ));
            }
        }

        if (isset($config["highResolution"])) {
            $pipe->setHighResolution($config["highResolution"]);
        }

        $hash = md5(Serialize::serialize($pipe));
        $pipe->setName("auto_" . $hash);

        return $pipe;
    }


    public function getEstimatedDimensions($originalWidth = null, $originalHeight = null)
    {
        $dimensions = array();
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
                            if ($arg["width"] <= $dimensions["width"]) {
                                $dimensions["height"] = round(($arg["width"] / $dimensions["width"]) * $dimensions["height"], 0);
                                $dimensions["width"] = $arg["width"];
                            }
                        } elseif ($transformation["method"] == "scaleByHeight") {
                            if ($arg["height"] < $dimensions["height"]) {
                                $dimensions["width"] = round(($arg["height"] / $dimensions["height"]) * $dimensions["width"], 0);
                                $dimensions["height"] = $arg["height"];
                            }
                        } elseif ($transformation["method"] == "contain") {
                            $x = $dimensions["width"] / $arg["width"];
                            $y = $dimensions["height"] / $arg["height"];
                            if ($x <= 1 && $y <= 1) {
                                continue;
                            } elseif ($x > $y) {
                                $dimensions["height"] = round(($arg["width"] / $dimensions["width"]) * $dimensions["height"], 0);
                                $dimensions["width"] = $arg["width"];
                            } else {
                                $dimensions["width"] = round(($arg["height"] / $dimensions["height"]) * $dimensions["width"], 0);
                                $dimensions["height"] = $arg["height"];
                            }
                        } elseif ($transformation["method"] == "cropPercent") {
                            $dimensions["width"] = ceil($dimensions["width"] * ($arg["width"] / 100));
                            $dimensions["height"] = ceil($dimensions["height"] * ($arg["height"] / 100));
                        }
                    }
                }
            } else {
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
}
