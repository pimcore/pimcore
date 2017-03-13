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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset\WebDAV;

use Sabre\DAV;
use Pimcore\Model\Asset;
use Pimcore\Model\Element;
use Pimcore\Logger;

class Tree extends DAV\Tree
{

    /**
     * Moves a file/directory
     *
     * @param string $sourcePath
     * @param string $destinationPath
     * @return null
     */
    public function move($sourcePath, $destinationPath)
    {
        $nameParts = explode("/", $sourcePath);
        $nameParts[count($nameParts)-1] = Element\Service::getValidKey($nameParts[count($nameParts)-1], "asset");
        $sourcePath = implode("/", $nameParts);

        $nameParts = explode("/", $destinationPath);
        $nameParts[count($nameParts)-1] = Element\Service::getValidKey($nameParts[count($nameParts)-1], "asset");
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

                $asset->setPath($parent->getRealFullPath() . "/");
                $asset->setParentId($parent->getId());
            }

            $user = \Pimcore\Tool\Admin::getCurrentUser();
            $asset->setUserModification($user->getId());
            $asset->save();
        } catch (\Exception $e) {
            Logger::error($e);
        }
    }
}
