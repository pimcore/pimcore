<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\Asset\WebDAV;

use Exception;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\Element;
use Sabre\DAV;

/**
 * @internal
 */
class Tree extends DAV\Tree
{
    /**
     * Moves a file/directory
     *
     * @param string $sourcePath
     * @param string $destinationPath
     */
    public function move($sourcePath, $destinationPath): void
    {
        $nameParts = explode('/', $sourcePath);
        $nameParts[count($nameParts) - 1] = Element\Service::getValidKey($nameParts[count($nameParts) - 1], 'asset');
        $sourcePath = implode('/', $nameParts);

        $nameParts = explode('/', $destinationPath);
        $nameParts[count($nameParts) - 1] = Element\Service::getValidKey($nameParts[count($nameParts) - 1], 'asset');
        $destinationPath = implode('/', $nameParts);

        try {
            if (dirname($sourcePath) == dirname($destinationPath)) {
                $asset = null;

                if ($asset = Asset::getByPath('/' . $destinationPath)) {
                    // If we got here, this means the destination exists, and needs to be overwritten
                    $sourceAsset = Asset::getByPath('/' . $sourcePath);
                    $asset->setData($sourceAsset->getData());
                    $sourceAsset->delete();
                }

                // see: Asset\WebDAV\File::delete() why this is necessary
                $log = Asset\WebDAV\Service::getDeleteLog();
                if (!$asset && array_key_exists('/' .$destinationPath, $log)) {
                    $asset = \Pimcore\Tool\Serialize::unserialize($log['/' .$destinationPath]['data']);
                    if ($asset) {
                        $sourceAsset = Asset::getByPath('/' . $sourcePath);
                        $asset->setData($sourceAsset->getData());
                        $sourceAsset->delete();
                    }
                }

                if (!$asset) {
                    $asset = Asset::getByPath('/' . $sourcePath);
                }
                $asset->setFilename(basename($destinationPath));
            } else {
                $asset = Asset::getByPath('/' . $sourcePath);
                $parent = Asset::getByPath('/' . dirname($destinationPath));

                $asset->setPath($parent->getRealFullPath() . '/');
                $asset->setParentId($parent->getId());
            }

            $user = \Pimcore\Tool\Admin::getCurrentUser();
            $asset->setUserModification($user->getId());
            $asset->save();
        } catch (Exception $e) {
            Logger::error((string) $e);
        }
    }
}
