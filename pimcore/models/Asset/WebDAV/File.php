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

class Asset_WebDAV_File extends Sabre_DAV_File {

    /**
     * @var Asset
     */
    private $asset;

    /**
     * @param Asset $asset
     * @return void
     */
    function __construct($asset) {
        $this->asset = $asset;
        //$this->asset->loadData();
    }

    /**
     * @return string
     */
    function getName() {
        return $this->asset->getFilename();
    }

    /**
     * @param string $name
     * @return string
     */
    function setName($name) {
        $this->asset->setFilename(Pimcore_File::getValidFilename($name));
        $this->asset->save();
    }

    /**
     * @return void
     */
    function delete() {
        $this->asset->delete();
    }

    /**
     * @return integer
     */
    function getLastModified() {
        return $this->asset->getModificationDate();
    }

    /**
     * Update data of the asset
     *
     * @param mixed $data
     * @return void
     */
    function put($data) {

        $tmpFile = PIMCORE_WEBDAV_TEMP . "/" . md5($this->asset->getId() . microtime());
        file_put_contents($tmpFile, $data);
        $data = file_get_contents($tmpFile);
        unlink($tmpFile);

        $this->asset->setData($data);
        $this->asset->save();
    }

    /**
     * get a file-handle of the file
     *
     * @return mixed
     */
    function get() {
        return fopen($this->asset->getFileSystemPath(), "r");
    }

    /**
     * Get a hash of the file for an unique identifier
     *
     * @return string
     */
    function getETag() {
        return md5_file($this->asset->getFileSystemPath());
    }

    /**
     * Returns the mimetype of the asset
     *
     * @return string
     */
    function getContentType() {
        return $this->asset->getMimetype();
    }

    /**
     * Get size of file in bytes
     *
     * @return integer
     */
    function getSize() {
        return filesize($this->asset->getFileSystemPath());
    }

}
