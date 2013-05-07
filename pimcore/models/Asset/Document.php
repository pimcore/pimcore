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

class Asset_Document extends Asset {

    /**
     * @var string
     */
    public $type = "document";

    /**
     * @param $thumbnailName
     * @param int $page
     * @return mixed|string
     */
    public function getImageThumbnail($thumbnailName, $page = 1) {

        // just 4 testing
        //$this->clearThumbnails(true);

        if(!Pimcore_Document::isAvailable()) {
            Logger::error("Couldn't create image-thumbnail of document " . $this->getFullPath() . " no document adapter is available");
            return "/pimcore/static/img/filetype-not-supported.png";
        }

        $thumbnail = Asset_Image_Thumbnail_Config::getByAutoDetect($thumbnailName);
        $thumbnail->setName($thumbnail->getName()."-".$page);

        try {
            $converter = Pimcore_Document::getInstance();
            $converter->load($this->getFileSystemPath());
            $path = PIMCORE_TEMPORARY_DIRECTORY . "/document_" . $this->getId() . "__thumbnail_" .  $page . ".png";

            if(!is_file($path)) {
                $converter->saveImage($path, $page);
            }

            if($thumbnail) {
                $path = Asset_Image_Thumbnail_Processor::process($this, $thumbnail, $path);
                return $path;
            }
        } catch (Exception $e) {
            Logger::error("Couldn't create image-thumbnail of document " . $this->getFullPath());
            Logger::error($e);
        }

        return "/pimcore/static/img/filetype-not-supported.png";
    }

    /**
     * @return void
     */
    public function clearThumbnails($force = false) {

        if($this->_dataChanged || $force) {
            // video thumbnails and image previews
            $files = glob(PIMCORE_TEMPORARY_DIRECTORY . "/document_" . $this->getId() . "__*");
            if(is_array($files)) {
                foreach ($files as $file) {
                    unlink($file);
                }
            }
        }
    }
}
