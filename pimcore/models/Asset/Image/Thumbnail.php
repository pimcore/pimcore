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

namespace Pimcore\Model\Asset\Image;

class Thumbnail {

    /**
     * @var Pimcore\Model\Asset\Image
     */
    protected $asset;

    /**
     * @var mixed|string
     */
    protected $path;

    /**
     * @var int
     */
    protected $width;

    /**
     * @var int
     */
    protected $height;

    /**
     * @var int
     */
    protected $realWidth;

    /**
     * @var int
     */
    protected $realHeight;

    /**
     * @var string
     */
    protected $mimetype;

    /**
     * @var Thumbnail\Config
     */
    protected $config;

    /**
     * @var bool
     */
    protected $deferred = false;

    /**
     * @var bool
     */
    protected static $pictureElementInUse = false;

    /**
     * @param $asset
     * @param null $config
     * @param bool $deferred
     */
    public function __construct($asset, $config = null, $deferred = false) {

        $this->asset = $asset;
        $this->deferred = $deferred;
        $this->config = $this->createConfig($config);
    }

    /**
     *
     */
    public function generate($deferredAllowed = false) {
        if(!$this->path) {
            // if no correct thumbnail config is given use the original image as thumbnail
            if(!$this->config) {
                $fsPath = $this->asset->getFileSystemPath();
                $this->path = str_replace(PIMCORE_DOCUMENT_ROOT, "", $fsPath);
            } else {
                try {
                    $deferred = ($deferredAllowed && $this->deferred) ? true : false;
                    $this->path = Thumbnail\Processor::process($this->asset, $this->config, null, $deferred);
                } catch (\Exception $e) {
                    $this->path = '/pimcore/static/img/filetype-not-supported.png';
                    \Logger::error("Couldn't create thumbnail of image " . $this->asset->getFullPath());
                    \Logger::error($e);
                }
            }
        }
    }

    /**
     *
     */
    public function reset() {
        $this->path = null;
        $this->width = null;
        $this->height = null;
        $this->realHeight = null;
        $this->realWidth = null;
    }

    /**
     * Get the public path to the thumbnail image.
     * This method is here for backwards compatility.
     * Up to Pimcore 1.4.8 a thumbnail was returned as a path to an image.
     * @return string Public path to thumbnail image.
    */
    public function __toString() {
        return $this->getPath(true);
    }

    /**
     * Get the public path to the thumbnail image.
     * @return string Public path to thumbnail image.
    */
    public function getPath($deferredAllowed = false) {
        $this->generate($deferredAllowed);
        return $this->path;
    }

    /**
     * @return int Width of the generated thumbnail image.
    */
    public function getWidth() {
        if(!$this->width) {
            $this->applyFileInfo();
        }
        return $this->width;
    }

    /**
     * Get the width of the generated thumbnail image in pixels.
     * @return int Height of the generated thumbnail image.
    */
    public function getHeight() {
        if(!$this->height) {
            $this->applyFileInfo();
        }
        return $this->height;
    }

    /**
     * @return int real Width of the generated thumbnail image. (when using high resolution option)
    */
    public function getRealWidth() {
        if(!$this->realWidth) {
            $this->applyFileInfo();
        }
        return $this->realWidth;
    }

    /**
     * Get the real width of the generated thumbnail image in pixels. (when using high resolution option)
     * @return int Height of the generated thumbnail image.
    */
    public function getRealHeight() {
        if(!$this->realHeight) {
            $this->applyFileInfo();
        }
        return $this->realHeight;
    }

    /**
     * Get the height of the generated thumbnail image in pixels.
     * @return string HTTP Mime Type of the generated thumbnail image.
    */
    public function getMimeType() {
        if(!$this->mimetype) {
            $this->applyFileInfo();
        }
        return $this->mimetype;
    }

    /**
    * Get generated HTML for displaying the thumbnail image in a HTML document. (XHTML compatible).
    * Attributes can be added as a parameter. Attributes containing illigal characters are ignored.
    * Width and Height attribute can be overridden. SRC-attribute not.
    * Values of attributes are escaped.
    * @param array $attributes Listof key-value pairs of HTML attributes.
    * @return string IMG-element with at least the attributes src, width, height, alt.
    */
    public function getHTML($attributes = array()) {

        $image = $this->getAsset();
        $attr = array();
        $pictureAttribs = []; // this is used for the html5 <picture> element

        if(!$this->deferred) {
            if($this->getWidth()) {
                $attr['width'] = 'width="'.$this->getWidth().'"';
            }
            if($this->getHeight()) {
                $attr['height'] = 'height="'.$this->getHeight().'"';
            }
        }

        $altText = "";
        $titleText = "";
        if(isset($attributes["alt"])) {
            $altText = $attributes["alt"];
        }
        if(isset($attributes["title"])) {
            $titleText = $attributes["title"];
        }

        if(empty($titleText)) {
            if($image->getMetadata("title")) {
                $titleText = $image->getMetadata("title");
            }
        }

        if(empty($altText)) {
            if($image->getMetadata("alt")) {
                $altText = $image->getMetadata("alt");
            } else {
                $altText = $titleText;
            }
        }

        // get copyright from asset
        if($image->getMetadata("copyright")) {
            if(!empty($altText)) {
                $altText .= " | ";
            }
            if(!empty($titleText)) {
                $titleText .= " | ";
            }
            $altText .= ("© " . $image->getMetadata("copyright"));
            $titleText .= ("© " . $image->getMetadata("copyright"));
        }

        $attributes["alt"] = $altText;
        if(!empty($titleText)) {
            $attributes["title"] = $titleText;
        }

        foreach($attributes as $key => $value) {
            //only include attributes with characters a-z and dashes in their name.
            if(preg_match("/^[a-z-]+$/i", $key)) {
                $attr[$key] = $key . '="' . htmlspecialchars($value) . '"';

                // do not include all attributes
                if(!in_array($key, ["width","height"])) {
                    $pictureAttribs[$key] = $key . '="' . htmlspecialchars($value) . '"';

                    // some attributes need to be added also as data- attribute, this is specific to picturePolyfill
                    if(in_array($key, ["alt"])) {
                        $pictureAttribs["data-" . $key] = "data-" . $key . '="' . htmlspecialchars($value) . '"';
                    }
                }
            }
        }

        $path = $this->getPath(true);
        $attr['src'] = 'src="'. $path .'"';

        $thumbConfig = $this->getConfig();

        if($this->getConfig() && !$this->getConfig()->hasMedias()) {
            // generate the srcset
            $srcSetValues = [];
            foreach ([1,2] as $highRes) {
                $thumbConfigRes = clone $thumbConfig;
                $thumbConfigRes->setHighResolution($highRes);
                $srcsetEntry = $image->getThumbnail($thumbConfigRes, true) . " " . $highRes . "x";
                $srcSetValues[] = $srcsetEntry;
            }
            $attr['srcset'] = 'srcset="'. implode(", ", $srcSetValues) .'"';
        }

        // build html tag
        $htmlImgTag = '<img '.implode(' ', $attr).' />';

        $attrCleanedForPicture = $attr;
        unset($attrCleanedForPicture["width"]);
        unset($attrCleanedForPicture["height"]);
        $htmlImgTagForpicture = '<img '.implode(' ', $attrCleanedForPicture).' />';

        // $this->getConfig() can be empty, the original image is returned
        if(!$this->getConfig() || !$this->getConfig()->hasMedias()) {
            return $htmlImgTag;
        } else {
            // output the <picture> - element

            // set this variable so that Pimcore_Controller_Plugin_Thumbnail::dispatchLoopShutdown() knows that
            // the picture polyfill script needs to be included
            self::$pictureElementInUse = true;

            $html = '<picture ' . implode(" ", $pictureAttribs) . ' data-default-src="' . $path . '">' . "\n";
                $mediaConfigs = $thumbConfig->getMedias();

                // currently only max-width is supported, the key of the media is WIDTHw (eg. 400w) according to the srcset specification
                ksort($mediaConfigs, SORT_NUMERIC);
                array_push($mediaConfigs, $thumbConfig->getItems()); //add the default config at the end - picturePolyfill v4

                foreach ($mediaConfigs as $mediaQuery => $config) {
                    $srcSetValues = [];
                    foreach ([1,2] as $highRes) {
                        $thumbConfigRes = clone $thumbConfig;
                        $thumbConfigRes->selectMedia($mediaQuery);
                        $thumbConfigRes->setHighResolution($highRes);
                        $thumb = $image->getThumbnail($thumbConfigRes, true);
                        $srcSetValues[] = $thumb . " " . $highRes . "x";
                    }

                    $html .= "\t" . '<source srcset="' . implode(", ", $srcSetValues) .'"';
                    if($mediaQuery) {
                        // currently only max-width is supported, so we replace the width indicator (400w) out of the name
                        $maxWidth = str_replace("w","",$mediaQuery);
                        $html .= ' media="(max-width: ' . $maxWidth . 'px)"';
                        $thumb->reset();
                    }
                    $html .= ' />' . "\n";
                }

                //$html .= "\t" . '<noscript>' . "\n\t\t" . $htmlImgTag . "\n\t" . '</noscript>' . "\n";
                $html .= $htmlImgTagForpicture . "\n";
            $html .= '</picture>' . "\n";

            return $html;
        }
    }

    /**
     * @param string $name
     * @param int $highRes
     * @return Thumbnail
     * @throws \Exception
     */
    public function getMedia($name, $highRes = 1) {
        $thumbConfig = $this->getConfig();
        $mediaConfigs = $thumbConfig->getMedias();

        if(array_key_exists($name, $mediaConfigs)) {
            $thumbConfigRes = clone $thumbConfig;
            $thumbConfigRes->selectMedia($name);
            $thumbConfigRes->setHighResolution($highRes);
            $thumbConfigRes->setMedias([]);
            $thumb = $this->getAsset()->getThumbnail($thumbConfigRes);

            return $thumb;
        } else {
            throw new \Exception("Media query '" . $name . "' doesn't exist in thumbnail configuration: " . $thumbConfig->getName());
        }
    }

    /**
     * @return Pimcore\Model\Asset\Image The original image from which this thumbnail is generated.
    */
    public function getAsset() {
        return $this->asset;
    }

    /**
     * Get thumbnail image configuration.
     * @return Thumbnail\Config
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * @param string $type
     * @return null|string
     * @throws \Exception
     */
    public function getChecksum($type = "md5") {
        $file = $this->getFileSystemPath();
        if(is_file($file)) {
            if($type == "md5") {
                return md5_file($file);
            } else if ($type = "sha1") {
                return sha1_file($file);
            } else {
                throw new \Exception("hashing algorithm '" . $type . "' isn't supported");
            }
        }

        return null;
    }

    /**
     * @return string
     */
    public function getFileSystemPath() {
        return PIMCORE_DOCUMENT_ROOT . $this->getPath();
    }

    /**
     * Get a thumbnail image configuration.
     * @param mixed $selector Name, array or object describing a thumbnail configuration.
     * @return Thumbnail\Config
    */
    protected function createConfig($selector) {
        return Thumbnail\Config::getByAutoDetect($selector);
    }

    /**
     * Get metadata from thumbnail image file and load it into class variables.
     * Some of the data is ignored.
    */
    protected function applyFileInfo() {
        $info = @getimagesize($this->getFileSystemPath());
        if($info) {
            list($this->width, $this->height) = $info;

            if(array_key_exists("mime", $info)) {
                $this->mimetype = $info["mime"];
            }

            $this->realHeight = $this->height;
            $this->realWidth = $this->width;

            if($this->config && $this->config->getHighResolution() && $this->config->getHighResolution() > 1) {
                $this->width = floor($this->width / $this->config->getHighResolution());
                $this->height = floor($this->height / $this->config->getHighResolution());
            }
        }
    }

    /**
     * @return bool
     */
    public static function isPictureElementInUse() {
        return self::$pictureElementInUse;
    }

    /**
     * Enables, when set to true, dispatchLoopShutdown of Pimcore_Controller_Plugin_Thumbnail
     * @param bool $flag
     * @return void
     */
    public static function setPictureElementInUse($flag) {
    	self::$pictureElementInUse = (bool) $flag;
    }
}
