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
 
class Pimcore_Image_Adapter_GD extends Pimcore_Image_Adapter {

    /**
     * @var Imagick
     */
    protected $resource;

    public function load ($imagePath) {

        $this->resource = new Imagick();
        $this->resource->readImage($imagePath);

        // set dimensions
        $this->setWidth($this->resource->getImageWidth());
        $this->setHeight($this->resource->getImageHeight());

        return $this;
    }

    /**
     * @param  $path
     * @return void
     */
    public function save ($path, $quality = null) {

        if($quality) {
            $this->resource->setCompressionQuality($quality);
            $this->resource->setImageCompressionQuality($quality);
        }

        $this->resource->writeImage($path);

        return $this;
    }


    /**
     * @param  $format
     * @return void
     */
    public function setFormat($format) {
        $this->resource->setImageFormat($format);

        return $this;
    }

}

