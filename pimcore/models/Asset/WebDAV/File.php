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

namespace Pimcore\Model\Asset\WebDAV;

use Sabre\DAV;
use Pimcore\Tool\Admin as AdminTool;
use Pimcore\Model\Asset;

class File extends DAV\File {

    /**
     * @var Asset
     */
    private $asset;

    /**
     * @param $asset
     */
    function __construct($asset) {
        $this->asset = $asset;
    }

    /**
     * @return string
     */
    function getName() {
        return $this->asset->getFilename();
    }

    /**
     * @param string $name
     * @return $this|void
     * @throws DAV\Exception\Forbidden
     * @throws \Exception
     */
    function setName($name) {

        if($this->asset->isAllowed("rename")) {
            $user = AdminTool::getCurrentUser();
            $this->asset->setUserModification($user->getId());

            $this->asset->setFilename(\Pimcore\File::getValidFilename($name));
            $this->asset->save();
        } else {
            throw new DAV\Exception\Forbidden();
        }

        return $this;
    }

    /**
     * @throws DAV\Exception\Forbidden
     * @throws \Exception
     */
    function delete() {

        if($this->asset->isAllowed("delete")) {
            Asset\Service::loadAllFields($this->asset);
            $this->asset->delete();

            // add the asset to the delete history, this is used so come over problems with programs like photoshop (delete, create instead of replace => move)
            // for details see Asset\WebDAV\Tree::move()
            $log = Asset\WebDAV\Service::getDeleteLog();

            $this->asset->_fulldump = true;
            $log[$this->asset->getFullpath()] = array(
                "id" => $this->asset->getId(),
                "timestamp" => time(),
                "data" => \Pimcore\Tool\Serialize::serialize($this->asset)
            );

            unset($this->asset->_fulldump);

            Asset\WebDAV\Service::saveDeleteLog($log);
        } else {
            throw new DAV\Exception\Forbidden();
        }
    }

    /**
     * @return integer
     */
    function getLastModified() {
        return $this->asset->getModificationDate();
    }

    /**
     * @param resource $data
     * @throws DAV\Exception\Forbidden
     * @throws \Exception
     */
    function put($data) {

        if($this->asset->isAllowed("publish")) {
            // read from resource -> default for SabreDAV
            $data = stream_get_contents($data);

            $user = AdminTool::getCurrentUser();
            $this->asset->setUserModification($user->getId());

            $this->asset->setData($data);
            $this->asset->save();
        } else {
            throw new DAV\Exception\Forbidden();
        }
    }

    /**
     * @return mixed|void
     * @throws DAV\Exception\Forbidden
     */
    function get() {
        if($this->asset->isAllowed("view")) {
            return fopen($this->asset->getFileSystemPath(), "r");
        } else {
            throw new DAV\Exception\Forbidden();
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
