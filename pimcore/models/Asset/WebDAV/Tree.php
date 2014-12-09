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

use Pimcore\File;
use Sabre\DAV;
use Pimcore\Model\Asset;

class Tree extends DAV\ObjectTree {

    /**
     * Moves a file/directory
     *
     * @param string $sourcePath
     * @param string $destinationPath
     * @return void
     */
    public function move($sourcePath, $destinationPath) {

        $nameParts = explode("/",$sourcePath);
        $nameParts[count($nameParts)-1] = File::getValidFilename($nameParts[count($nameParts)-1]);
        $sourcePath = implode("/",$nameParts);
        
        $nameParts = explode("/",$destinationPath);
        $nameParts[count($nameParts)-1] = File::getValidFilename($nameParts[count($nameParts)-1]);
        $destinationPath = implode("/",$nameParts);
  
        try {
            if (dirname($sourcePath) == dirname($destinationPath)) {

                $asset = null;

                if($asset = Asset::getByPath("/" . $destinationPath)) {
                    // If we got here, this means the destination exists, and needs to be overwritten
                    $sourceAsset = Asset::getByPath("/" . $sourcePath);
                    $asset->setData($sourceAsset->getData());
                    $sourceAsset->delete();
                }

                // see: Asset\WebDAV\File::delete() why this is necessary
                $log = Asset\WebDAV\Service::getDeleteLog();
                if(!$asset && array_key_exists("/" .$destinationPath, $log)) {
                    $asset = \Pimcore\Tool\Serialize::unserialize($log["/" .$destinationPath]["data"]);
                    if($asset) {
                        $sourceAsset = Asset::getByPath("/" . $sourcePath);
                        $asset->setData($sourceAsset->getData());
                        $sourceAsset->delete();
                    }
                }

                if(!$asset) {
                    $asset = Asset::getByPath("/" . $sourcePath);
                }
                $asset->setFilename(basename($destinationPath));

            } else {

                $asset = Asset::getByPath("/" . $sourcePath);
                $parent = Asset::getByPath("/" . dirname($destinationPath));

                $asset->setPath($parent->getFullPath() . "/");
                $asset->setParentId($parent->getId());
            }

            $user = \Pimcore\Tool\Admin::getCurrentUser();
            $asset->setUserModification($user->getId());
            $asset->save();

        } catch (\Exception $e) {
            \Logger::error($e);
        }
    }

}
