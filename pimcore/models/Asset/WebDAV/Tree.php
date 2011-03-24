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

class Asset_WebDAV_Tree extends Sabre_DAV_ObjectTree {

    /**
     * Moves a file/directory
     *
     * @param string $sourcePath
     * @param string $destinationPath
     * @return void
     */
    public function move($sourcePath, $destinationPath) {
        
        $nameParts = explode("/",$sourcePath);
        $nameParts[count($nameParts)-1] = Pimcore_File::getValidFilename($nameParts[count($nameParts)-1]);
        $sourcePath = implode("/",$nameParts);
        
        $nameParts = explode("/",$destinationPath);
        $nameParts[count($nameParts)-1] = Pimcore_File::getValidFilename($nameParts[count($nameParts)-1]);
        $destinationPath = implode("/",$nameParts);
  
        try {
            if (dirname($sourcePath) == dirname($destinationPath)) {
                try {
                    $destinationNode = $this->getNodeForPath($destinationPath);
                    // If we got here, it means the destination exists, and needs to be overwritten
                    $destinationNode->delete();

                } catch (Sabre_DAV_Exception_FileNotFound $e) {
                    // If we got here, it means the destination node does not yet exist
                    Logger::warning("Sabre_DAV_Exception_FileNotFound");
                }

                $asset = Asset::getByPath("/" . $sourcePath);
                $asset->setFilename(basename($destinationPath));

            } else {

                $asset = Asset::getByPath("/" . $sourcePath);
                $parent = Asset::getByPath("/" . dirname($destinationPath));

                $asset->setPath($parent->getFullPath() . "/");
                $asset->setParentId($parent->getId());
            }
        }
        catch (Exception $e) {
            Logger::error($e);
        }
        $asset->save();
    }

}
