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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset;

use Pimcore\Cache;
use Pimcore\Model;
use Pimcore\Tool;
use Pimcore\Logger;

/**
 * @method \Pimcore\Model\Asset\Dao getDao()
 */
class Document extends Model\Asset
{

    /**
     * @var string
     */
    public $type = "document";

    /**
     *
     */
    protected function update()
    {
        $this->clearThumbnails();

        if ($this->getDataChanged()) {
            $tmpFile = $this->getTemporaryFile();

            try {
                $pageCount = $this->readPageCount($tmpFile);
                if ($pageCount !== null && $pageCount > 0) {
                    $this->setCustomSetting("document_page_count", $pageCount);
                }
            } catch (\Exception $e) {
            }

            unlink($tmpFile);
        }

        parent::update();
    }

    /**
     * @param null $path
     * @return null
     */
    protected function readPageCount($path = null)
    {
        $pageCount = null;
        if (!$path) {
            $path = $this->getFileSystemPath();
        }

        if (!\Pimcore\Document::isAvailable()) {
            Logger::error("Couldn't create image-thumbnail of document " . $this->getRealFullPath() . " no document adapter is available");

            return null;
        }

        try {
            $converter = \Pimcore\Document::getInstance();
            $converter->load($path);

            // read from blob here, because in $this->update() (see above) $this->getFileSystemPath() contains the old data
            $pageCount = $converter->getPageCount();

            return $pageCount;
        } catch (\Exception $e) {
            Logger::error($e);
        }

        return $pageCount;
    }

    /**
     * @return null
     */
    public function getPageCount()
    {
        if (!$pageCount = $this->getCustomSetting("document_page_count")) {
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
    public function getImageThumbnail($thumbnailName, $page = 1, $deferred = false)
    {
        if (!\Pimcore\Document::isAvailable()) {
            Logger::error("Couldn't create image-thumbnail of document " . $this->getRealFullPath() . " no document adapter is available");

            return new Document\ImageThumbnail(null);
        }

        return new Document\ImageThumbnail($this, $thumbnailName, $page, $deferred);
    }

    /**
     * @param null $page
     * @return mixed|null
     */
    public function getText($page = null)
    {
        if (\Pimcore\Document::isAvailable() && \Pimcore\Document::isFileTypeSupported($this->getFilename())) {
            $cacheKey = "asset_document_text_" . $this->getId() . "_" . ($page ? $page : "all");
            if (!$text = Cache::load($cacheKey)) {
                $document = \Pimcore\Document::getInstance();
                $text = $document->getText($page, $this->getFileSystemPath());
                Cache::save($text, $cacheKey, $this->getCacheTags(), null, 99, true); // force cache write
            }

            return $text;
        } else {
            Logger::error("Couldn't get text out of document " . $this->getRealFullPath() . " no document adapter is available");
        }

        return null;
    }

    /**
     * @param bool $force
     */
    public function clearThumbnails($force = false)
    {
        if ($this->_dataChanged || $force) {
            // video thumbnails and image previews
            $files = glob(PIMCORE_TEMPORARY_DIRECTORY . "/document-image-cache/document_" . $this->getId() . "__*");
            if (is_array($files)) {
                foreach ($files as $file) {
                    unlink($file);
                }
            }

            recursiveDelete($this->getImageThumbnailSavePath());
        }
    }
}
