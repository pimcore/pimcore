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

        if($this->asset->isAllowed("rename")) {
            $user = Pimcore_Tool_Admin::getCurrentUser();
            $this->asset->setUserModification($user->getId());

            $this->asset->setFilename(Pimcore_File::getValidFilename($name));
            $this->asset->save();
        } else {
            throw new Sabre_DAV_Exception_Forbidden();
        }

        return $this;
    }

    /**
     * @return void
     */
    function delete() {

        if($this->asset->isAllowed("delete")) {
            Asset_Service::loadAllFields($this->asset);
            $this->asset->delete();

            // add the asset to the delete history, this is used so come over problems with programs like photoshop (delete, create instead of replace => move)
            // for details see Asset_WebDAV_Tree::move()
            $log = Asset_WebDAV_Service::getDeleteLog();

            $this->asset->_fulldump = true;
            $log[$this->asset->getFullpath()] = array(
                "id" => $this->asset->getId(),
                "timestamp" => time(),
                "data" => Pimcore_Tool_Serialize::serialize($this->asset)
            );

            unset($this->asset->_fulldump);

            Asset_WebDAV_Service::saveDeleteLog($log);
        } else {
            throw new Sabre_DAV_Exception_Forbidden();
        }
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

        if($this->asset->isAllowed("publish")) {
            // read from resource -> default for SabreDAV
            $data = stream_get_contents($data);

            $user = Pimcore_Tool_Admin::getCurrentUser();
            $this->asset->setUserModification($user->getId());

            $this->asset->setData($data);
            $this->asset->save();
        } else {
            throw new Sabre_DAV_Exception_Forbidden();
        }
    }

    /**
     * get a file-handle of the file
     *
     * @return mixed
     */
    function get() {
        if($this->asset->isAllowed("view")) {
            return fopen($this->asset->getFileSystemPath(), "r");
        } else {
            throw new Sabre_DAV_Exception_Forbidden();
        }
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
