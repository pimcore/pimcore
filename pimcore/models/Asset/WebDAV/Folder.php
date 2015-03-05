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
use Pimcore\File; 
use Pimcore\Tool\Admin as AdminTool;
use Pimcore\Model\Asset;

class Folder extends DAV\Collection {

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
     * Returns the children of the asset if the asset is a folder
     *
     * @return array
     */
    function getChildren() {

        $children = array();

        if ($this->asset->hasChilds()) {
            foreach ($this->asset->getChilds() as $child) {
                if($child->isAllowed("view")) {
                    try {
                        if ($child = $this->getChild($child)) {
                            $children[] = $child;
                        }
                    }
                    catch (\Exception $e) {
                        \Logger::warning($e);
                    }
                }
            }
        }
        return $children;
    }

    /**
     * @param string $name
     * @return DAV\INode|void
     * @throws DAV\Exception\NotFound
     */
    function getChild($name) {
        
        $nameParts = explode("/",$name);
        $name = File::getValidFilename($nameParts[count($nameParts)-1]);
        
        //$name = implode("/",$nameParts);
        
        if (is_string($name)) {
            $parentPath = $this->asset->getFullPath();
            if ($parentPath == "/") {
                $parentPath = "";
            }

            if (!$asset = Asset::getByPath($parentPath . "/" . $name)) {
                throw new DAV\Exception\NotFound('File not found: ' . $name);
            }
        }
        else if ($name instanceof Asset) {
            $asset = $name;
        }

        if ($asset instanceof Asset) {
            if ($asset->getType() == "folder") {
                return new Asset\WebDAV\Folder($asset);
            }
            else {
                return new Asset\WebDAV\File($asset);
            }
        }
        throw new DAV\Exception\NotFound('File not found: ' . $name);
    }

    /**
     * @return string
     */
    function getName() {
        return $this->asset->getFilename();
    }

    /**
     * @param string $name
     * @param null $data
     * @return null|string|void
     * @throws DAV\Exception\Forbidden
     */
    function createFile($name, $data = null) {

        $data = stream_get_contents($data);
        $user = AdminTool::getCurrentUser();

        if($this->asset->isAllowed("create")) {
            $asset = Asset::create($this->asset->getId(), array(
                "filename" => File::getValidFilename($name),
                "data" => $data,
                "userModification" => $user->getId(),
                "userOwner" => $user->getId()
            ));
        } else {
            throw new DAV\Exception\Forbidden();
        }
    }

    /**
     * @param string $name
     * @throws DAV\Exception\Forbidden
     */
    function createDirectory($name) {
        $user = AdminTool::getCurrentUser();

        if($this->asset->isAllowed("create")) {
            $asset = Asset::create($this->asset->getId(), array(
                "filename" => File::getValidFilename($name),
                "type" => "folder",
                "userModification" => $user->getId(),
                "userOwner" => $user->getId()
            ));
        } else {
            throw new DAV\Exception\Forbidden();
        }
    }

    /**
     * @throws DAV\Exception\Forbidden
     * @throws \Exception
     */
    function delete() {
        if($this->asset->isAllowed("delete")) {
            $this->asset->delete();
        } else {
            throw new DAV\Exception\Forbidden();
        }
    }

    /**
     * @param string $name
     * @return $this|void
     * @throws DAV\Exception\Forbidden
     * @throws \Exception
     */
    function setName($name) {

        if($this->asset->isAllowed("rename")) {
            $this->asset->setFilename(File::getValidFilename($name));
            $this->asset->save();
        } else {
            throw new DAV\Exception\Forbidden();
        }

        return $this;
    }

    /**
     * @return integer
     */
    function getLastModified() {
        return $this->asset->getModificationDate();
    }
}
