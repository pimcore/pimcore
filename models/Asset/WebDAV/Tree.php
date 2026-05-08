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
use Pimcore\Tool\Admin;
use Pimcore\Tool\Serialize;
use Sabre\DAV;
use Sabre\DAV\Exception\Forbidden;

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
        $user = Admin::getCurrentUser();
        if ($user === null) {
            throw new Forbidden('No authenticated user available');
        }

        $nameParts = explode('/', $sourcePath);
        $nameParts[count($nameParts) - 1] = Element\Service::getValidKey($nameParts[count($nameParts) - 1], 'asset');
        $sourcePath = implode('/', $nameParts);

        $nameParts = explode('/', $destinationPath);
        $nameParts[count($nameParts) - 1] = Element\Service::getValidKey($nameParts[count($nameParts) - 1], 'asset');
        $destinationPath = implode('/', $nameParts);

        try {
            if (dirname($sourcePath) === dirname($destinationPath)) {
                $asset = Asset::getByPath('/' . $destinationPath);

                if ($asset) {
                    // If we got here, this means the destination exists, and needs to be overwritten
                    // NB: due to the nature of how the WebDav might be used with third party software (like Photoshop),
                    // a move in here it has to be an overwrite in the history of destination file to keep the file
                    // history and make it seamlessly and quickly reverted within the file change history.
                    // It also helps keeping the hardcoded reference or dependencies of a specific asset ID that might
                    // be used elsewhere in the project that users/collaborators given only WebDav access have
                    // no control nor access.
                    $sourceAsset = Asset::getByPath('/' . $sourcePath);
                    $asset->setData($sourceAsset->getData());

                }

                // see: Asset\WebDAV\File::delete() why this is necessary
                $log = Asset\WebDAV\Service::getDeleteLog();
                if (!$asset && array_key_exists('/' .$destinationPath, $log)) {
                    $asset = Serialize::unserialize($log['/' . $destinationPath]['data'], false);
                    if ($asset) {
                        $sourceAsset = Asset::getByPath('/' . $sourcePath);
                        $asset->setData($sourceAsset->getData());
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

            if (isset($parent)) {
                if (!$parent->isAllowed('create', $user)) {
                    throw new Forbidden('No create permission on destination folder');
                }
            }

            if (!$asset->isAllowed('save', $user)) {
                throw new Forbidden('No save permission on asset');
            }
            
            if (isset($sourceAsset)) {
                if (!$sourceAsset->isAllowed('delete', $user)) {
                    throw new Forbidden('No delete permission on source');
                }
                $sourceAsset->delete();
            }
            
            $asset->setUserModification($user->getId());
            $asset->save();
        } catch (Forbidden $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error((string) $e);
            throw $e;
        }
    }
}
