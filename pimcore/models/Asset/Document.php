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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Asset_Document extends Asset {

    /**
     * @var string
     */
    public $type = "document";

    protected function update() {

        $this->clearThumbnails();

        if($this->getDataChanged()) {
            $tmpFile = $this->getTemporaryFile(true);

            try {
                $pageCount = $this->readPageCount($tmpFile);
                if($pageCount !== null && $pageCount > 0) {
                    $this->setProperty("document_page_count", "text", $pageCount);
                }
            } catch (\Exception $e) {

            }
        }

        parent::update();
    }

    protected function readPageCount($path = null)  {
        $pageCount = null;
        if(!$path) {
            $path = $this->getFileSystemPath();
        }

        if(!Pimcore_Document::isAvailable()) {
            Logger::error("Couldn't create image-thumbnail of document " . $this->getFullPath() . " no document adapter is available");
            return null;
        }

        try {
            $converter = Pimcore_Document::getInstance();
            $converter->load($path);

            // read from blob here, because in $this->update() (see above) $this->getFileSystemPath() contains the old data
            $pageCount = $converter->getPageCount();
            return $pageCount;
        } catch (\Exception $e) {
            Logger::error($e);
        }

        return $pageCount;
    }

    public function getPageCount() {
        if(!$pageCount = $this->getProperty("document_page_count")) {
            $pageCount = $this->readPageCount();
        }
        return $pageCount;
    }

    /**
     * @param $thumbnailName
     * @param int $page
     * @param bool $deferred $deferred deferred means that the image will be generated on-the-fly (details see below)
     * @return mixed|string
     */
    public function getImageThumbnail($thumbnailName, $page = 1, $deferred = false) {

        // just 4 testing
        //$this->clearThumbnails(true);

        if(!Pimcore_Document::isAvailable()) {
            Logger::error("Couldn't create image-thumbnail of document " . $this->getFullPath() . " no document adapter is available");
            return "/pimcore/static/img/filetype-not-supported.png";
        }

        $thumbnail = Asset_Image_Thumbnail_Config::getByAutoDetect($thumbnailName);
        $thumbnail->setName("document_" . $thumbnail->getName()."-".$page);

        try {
            if(!$deferred) {
                $converter = Pimcore_Document::getInstance();
                $converter->load($this->getFileSystemPath());
                $path = PIMCORE_TEMPORARY_DIRECTORY . "/document-image-cache/document_" . $this->getId() . "__thumbnail_" .  $page . ".png";
                if(!is_dir(dirname($path))) {
                    Pimcore_File::mkdir(dirname($path));
                }

                if(!is_file($path)) {
                    $converter->saveImage($path, $page);
                }
            }

            if($thumbnail) {
                $path = Asset_Image_Thumbnail_Processor::process($this, $thumbnail, $path, $deferred);
            }

            return preg_replace("@^" . preg_quote(PIMCORE_DOCUMENT_ROOT) . "@", "", $path);
        } catch (Exception $e) {
            Logger::error("Couldn't create image-thumbnail of document " . $this->getFullPath());
            Logger::error($e);
        }

        return "/pimcore/static/img/filetype-not-supported.png";
    }

    public function getText($page = null) {
        if(Pimcore_Document::isAvailable() && Pimcore_Document::isFileTypeSupported($this->getFilename())) {
            $document = Pimcore_Document::getInstance();
            return $document->getText($page, $this->getFileSystemPath());
        } else {
            Logger::error("Couldn't get text out of document " . $this->getFullPath() . " no document adapter is available");
        }

        return null;
    }

    /**
     * @return void
     */
    public function clearThumbnails($force = false) {

        if($this->_dataChanged || $force) {
            // video thumbnails and image previews
            $files = glob(PIMCORE_TEMPORARY_DIRECTORY . "/document-image-cache/document_" . $this->getId() . "__*");
            if(is_array($files)) {
                foreach ($files as $file) {
                    unlink($file);
                }
            }

            recursiveDelete($this->getImageThumbnailSavePath());
        }
    }
}
