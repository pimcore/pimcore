<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Asset
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Asset\WebDAV;

use Pimcore\File;
use Sabre\DAV;
use Pimcore\Model\Asset;

class Tree extends DAV\Tree
{

    /**
     * Moves a file/directory
     *
     * @param string $sourcePath
     * @param string $destinationPath
     * @return void
     */
    public function move($sourcePath, $destinationPath)
    {
        $nameParts = explode("/", $sourcePath);
        $nameParts[count($nameParts)-1] = File::getValidFilename($nameParts[count($nameParts)-1]);
        $sourcePath = implode("/", $nameParts);
        
        $nameParts = explode("/", $destinationPath);
        $nameParts[count($nameParts)-1] = File::getValidFilename($nameParts[count($nameParts)-1]);
        $destinationPath = implode("/", $nameParts);
  
        try {
            if (dirname($sourcePath) == dirname($destinationPath)) {
                $asset = null;

                if ($asset = Asset::getByPath("/" . $destinationPath)) {
                    // If we got here, this means the destination exists, and needs to be overwritten
                    $sourceAsset = Asset::getByPath("/" . $sourcePath);
                    $asset->setData($sourceAsset->getData());
                    $sourceAsset->delete();
                }

                // see: Asset\WebDAV\File::delete() why this is necessary
                $log = Asset\WebDAV\Service::getDeleteLog();
                if (!$asset && array_key_exists("/" .$destinationPath, $log)) {
                    $asset = \Pimcore\Tool\Serialize::unserialize($log["/" .$destinationPath]["data"]);
                    if ($asset) {
                        $sourceAsset = Asset::getByPath("/" . $sourcePath);
                        $asset->setData($sourceAsset->getData());
                        $sourceAsset->delete();
                    }
                }

                if (!$asset) {
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
