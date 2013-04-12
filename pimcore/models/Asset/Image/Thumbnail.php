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
 * @license    http://www.pimcore.org/license     New BSD License
 */
class Asset_Image_Thumbnail {

    protected $asset; //original image
    protected $path;
    protected $width; 
    protected $height;
    protected $mimetype;
    protected $config;

    /**
     * Generate a thumbnail image.
     * @param Image_Asset Original image
     * @param mixed $selector Name, array or object with the thumbnail configuration.
    */
    public function __construct($asset, $config = null) {

        $this->asset = $asset;
        $this->config = $this->createConfig($config);

        // if no correct thumbnail config is given use the original image as thumbnail
        if(!$this->config) {
            $fsPath = $asset->getFileSystemPath();
            $this->path = str_replace(PIMCORE_DOCUMENT_ROOT, "", $fsPath);
        } else {
            try {
                $this->path = Asset_Image_Thumbnail_Processor::process($this->asset, $this->config);
            } catch (Exception $e) {
                $this->path = '/pimcore/static/img/filetype-not-supported.png';
                Logger::error("Couldn't create thumbnail of image " . $this->asset->getFullPath());
                Logger::error($e);
            }
        }
    }
    
    /**
     * Get the public path to the thumbnail image.
     * This method is here for backwards compatility.
     * Up to Pimcore 1.4.8 a thumbnail was returned as a path to an image.
     * @return string Public path to thumbnail image.
    */
    public function __toString() {
        return $this->getPath();
    }

    /**
     * Get the public path to the thumbnail image.
     * @return string Public path to thumbnail image.
    */
    public function getPath() {
        return $this->path;
    }

    /**
     * @return int Width of the generated thumbnail image.
    */
    public function getWidth() {
        if(!$this->width) {
            $this->getFileInfo();
        }
        return $this->width;
    }

    /**
     * Get the width of the generated thumbnail image in pixels.
     * @return int Height of the generated thumbnail image.
    */
    public function getHeight() {
        if(!$this->height) {
            $this->getFileInfo();
        }
        return $this->height;
    }

    /**
     * Get the height of the generated thumbnail image in pixels.
     * @return string HTTP Mime Type of the generated thumbnail image.
    */
    public function getMimeType() {
        if(!$this->mimetype) {
            $this->getFileInfo();
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

        $attr = array();

        $attr['width'] = 'width="'.$this->getWidth().'"';
        $attr['height'] = 'height="'.$this->getHeight().'"';

        foreach($attributes as $key => $value) {
            //only include attributes with characters a-z and dashes in their name.
            if(preg_match("/^[a-z-]+$/i", $key)) {
                $attr[$key] = $key.'="'.htmlspecialchars($value).'"';
            }
        }

        $attr['src'] = 'src="'.$this->getPath().'"';

        //ALT-attribute is required in XHTML
        if(!isset($attr['alt'])) {
            $attr['alt'] = 'alt=""';
        }

        return '<img '.implode(' ', $attr).' />';
    }

    /**
     * @return Asset_Image The original image from which this thumbnail is generated.
    */
    public function getAsset() {
        return $this->asset;
    }

    /**
     * Get thumbnail image configuration.
     * @param string $config
     * @return Asset_Image_Thumbnail_Config
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * Get a thumbnail image configuration.
     * @param mixed $selector Name, array or object describing a thumbnail configuration.
     * @return Asset_Image_Thumbnail_Config
    */
    protected function createConfig($selector) {

        if (is_string($selector)) {

            try {
                $config = Asset_Image_Thumbnail_Config::getByName($selector);
            }
            catch (Exception $e) {
                Logger::error("requested thumbnail " . $selector . " is not defined");
                return false;
            }
        }
        else if (is_array($selector)) {
            // check if it is a legacy config or a new one
            if(array_key_exists("items", $selector)) {
                $config = Asset_Image_Thumbnail_Config::getByArrayConfig($selector);
            } else {
                $config = Asset_Image_Thumbnail_Config::getByLegacyConfig($selector);
            }
        }
        else if ($selector instanceof Asset_Image_Thumbnail_Config) {
            $config = $selector;
        }
        
        return $config;

    }

    /**
     * Get metadata from thumbnail image file and load it into class variables.
     * Some of the data is ignored.
    */
    protected function getFileInfo() {
        list($this->width, $this->height, $type, $attr, $this->mimetype) = getimagesize(PIMCORE_DOCUMENT_ROOT.$this->path);
    }
}