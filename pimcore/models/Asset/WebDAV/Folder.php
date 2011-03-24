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

class Asset_WebDAV_Folder extends Sabre_DAV_Directory {

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
                try {
                    if ($child = $this->getChild($child)) {
                        $children[] = $child;
                    }
                }
                catch (Exception $e) {
                    Logger::warning($e);
                }
            }
        }
        return $children;
    }

    /**
     * Returns a children by the filename
     *
     * @param string $asset
     * @return array
     */
    function getChild($name) {
        
        $nameParts = explode("/",$name);
        $name = Pimcore_File::getValidFilename($nameParts[count($nameParts)-1]);
        
        //$name = implode("/",$nameParts);
        
        if (is_string($name)) {
            $parentPath = $this->asset->getFullPath();
            if ($parentPath == "/") {
                $parentPath = "";
            }

            if (!$asset = Asset::getByPath($parentPath . "/" . $name)) {
                throw new Sabre_DAV_Exception_FileNotFound('File not found: ' . $name);
            }
        }
        else if ($name instanceof Asset) {
            $asset = $name;
        }

        if ($asset instanceof Asset) {
            if ($asset->getType() == "folder") {
                return new Asset_WebDAV_Folder($asset);
            }
            else {
                return new Asset_WebDAV_File($asset);
            }
        }
        throw new Sabre_DAV_Exception_FileNotFound('File not found: ' . $name);
    }

    /**
     * @return string
     */
    function getName() {
        return $this->asset->getFilename();
    }

    /**
     * creates a new file in current directory
     *
     * @param string $name
     * @param mixed $data
     * @return string
     */
    function createFile($name, $data = null) {

        $tmpFile = PIMCORE_WEBDAV_TEMP . "/" . md5($this->asset->getId() . $name . microtime());
        file_put_contents($tmpFile, $data);
        $data = file_get_contents($tmpFile);
        unlink($tmpFile);

        $asset = Asset::create($this->asset->getId(), array(
            "filename" => Pimcore_File::getValidFilename($name),
            "data" => $data
        ));
    }

    /**
     * creates a new folder in current directory
     *
     * @param string $name
     * @return string
     */
    function createDirectory($name) {
        $asset = Asset::create($this->asset->getId(), array(
            "filename" => Pimcore_File::getValidFilename($name),
            "type" => "folder"
        ));
    }

    /**
     * @return void
     */
    function delete() {
        $this->asset->delete();
    }

    /**
     * @return void
     */
    function setName($name) {
        $this->asset->setFilename(Pimcore_File::getValidFilename($name));

        $this->asset->save();
    }

    /**
     * @return integer
     */
    function getLastModified() {
        return $this->asset->getModificationDate();
    }
}
