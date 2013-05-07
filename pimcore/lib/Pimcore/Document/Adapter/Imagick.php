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
 
class Pimcore_Document_Adapter_Imagick extends Pimcore_Document_Adapter {

    /**
     * @var Imagick
     */
    protected $resource;


    /**
     * @var string
     */
    protected $path;

    /**
     * @param $path
     * @return $this
     */
    public function load($path) {

        if(!preg_match("/\.pdf$/", $path)) {
            $message = "Couldn't load document " . $path . " only PDF documents are currently supported";
            Logger::error($message);
            throw new \Exception($message);
        }

        if($this->resource) {
            unset($this->resource);
            $this->resource = null;
        }

        $this->path = $path;

        return $this;
    }

    public function saveImage($path, $page = 1) {

        try {
            $this->resource = new Imagick();

            $this->resource->setResolution(300, 300);
            if(!$this->resource->readImage($this->path."[" . ($page-1) . "]")) {
                return false;
            }

            $this->resource->stripimage();
            $this->resource->setImageFormat("png");

            $this->resource->setCompressionQuality((int) 100);
            $this->resource->setImageCompressionQuality((int) 100);

            $this->resource->writeImage($path);

            return $this;
        } catch (Exception $e) {
            Logger::error($e);
            return false;
        }
    }
}
