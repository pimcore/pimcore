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
use Pimcore\Model\Property;
use Pimcore\Tool\Admin;
use Sabre\DAV;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;

/**
 * @internal
 */
class Tree extends DAV\Tree
{
    /**
     * Moves a file/directory.
     *
     * Within the same directory this handles three cases:
     *  1. the destination still exists -> overwrite it in place (keeps its id/history);
     *  2. the destination was just deleted and is still in the delete log -> re-create it from
     *     the source content while reusing the deleted id (see Asset\WebDAV\File::delete());
     *  3. neither -> a plain rename of the source.
     * Across directories it is a plain move of the source into the destination folder.
     *
     * The delete-log branch exists to support clients (e.g. Photoshop) that replace a file via
     * delete + create + move instead of an overwrite. That log is a best-effort, ~30s-lived
     * workaround, which is why the entries are read defensively (missing keys are tolerated).
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
                    if (!$sourceAsset) {
                        throw new NotFound('Source asset not found');
                    }
                    $asset->setData($sourceAsset->getData());

                }

                // see: Asset\WebDAV\File::delete() why this is necessary
                $log = Asset\WebDAV\Service::getDeleteLog();
                if (!$asset && array_key_exists('/' . $destinationPath, $log)) {
                    $sourceAsset = Asset::getByPath('/' . $sourcePath);
                    if (!$sourceAsset) {
                        throw new NotFound('Source asset not found');
                    }

                    // The destination was already deleted (e.g. Photoshop replaces a file via
                    // delete + create + move). Re-create it from the source content while reusing
                    // the deleted asset's id, so hardcoded references to that id stay valid.
                    // save() re-inserts the row via upsert; the source asset is removed below.
                    $logEntry = $log['/' . $destinationPath];
                    $restoredId = $logEntry['id'] ?? null;
                    if ($restoredId !== null) {
                        // no 'type' here: Asset::create() ignores a passed type when 'data' is
                        // present and derives the concrete class from the detected mime type
                        $asset = Asset::create($sourceAsset->getParentId(), [
                            'filename' => basename($destinationPath),
                            'data' => $sourceAsset->getData(),
                        ], false);
                        $asset->setId((int) $restoredId);
                        // destination lives in the same folder as the source; set the path now
                        // so the permission check below sees the correct workspace location
                        $asset->setPath((string) $sourceAsset->getRealPath());

                        // restore ownership and creation date from the snapshot, so the rebuilt
                        // destination keeps them like the id (the mover only becomes the modifier)
                        if (isset($logEntry['userOwner'])) {
                            $asset->setUserOwner((int) $logEntry['userOwner']);
                        }
                        if (isset($logEntry['creationDate'])) {
                            $asset->setCreationDate((int) $logEntry['creationDate']);
                        }

                        // restore the deleted destination's own properties and metadata from the
                        // scalar snapshot, so they survive the delete + create + move round-trip
                        $properties = $logEntry['properties'] ?? [];
                        $this->restoreProperties($asset, is_array($properties) ? $properties : []);

                        // the raw assets_metadata.data column IS the internal metadata form:
                        // element types (asset/document/object) only override getDataForResource(),
                        // not getDataFromResource(), so references stay ids (scalars). Feeding the
                        // stored rows back via setMetadataRaw() therefore round-trips on save().
                        $metadata = $logEntry['metadata'] ?? [];
                        if (is_array($metadata) && $metadata !== []) {
                            $asset->setMetadataRaw($metadata);
                        }
                    }
                }

                if (!$asset) {
                    $asset = Asset::getByPath('/' . $sourcePath);
                    if (!$asset) {
                        throw new NotFound('Source asset not found');
                    }
                }

                // Only require the "rename" permission when the filename actually changes. On the
                // overwrite paths above $asset is resolved from the destination, so setFilename()
                // below is a no-op there and the MOVE is not a rename - gating it would break the
                // safe-save flow of third party software described above.
                if ($asset->getFilename() !== basename($destinationPath) && !$asset->isAllowed('rename', $user)) {
                    throw new Forbidden('Missing "rename" permission');
                }

                $asset->setFilename(basename($destinationPath));
            } else {
                $asset = Asset::getByPath('/' . $sourcePath);
                $parent = Asset::getByPath('/' . dirname($destinationPath));

                if (!$asset || !$parent) {
                    throw new NotFound('Source asset or destination folder not found');
                }

                $asset->setPath($parent->getRealFullPath() . '/');
                $asset->setParentId($parent->getId());
            }

            if (isset($parent)) {
                if (!$parent->isAllowed('create', $user)) {
                    throw new Forbidden('No create permission on destination folder');
                }
            }

            if (!$asset->isAllowed('publish', $user)) {
                throw new Forbidden('No publish permission on target asset');
            }

            if (isset($sourceAsset) && !$sourceAsset->isAllowed('delete', $user)) {
                throw new Forbidden('No delete permission on source');
            }

            $asset->setUserModification($user->getId());
            $asset->save();

            if (isset($sourceAsset)) {
                $sourceAsset->delete();
            }
        } catch (Forbidden $e) {
            throw $e;
        } catch (Exception $e) {
            Logger::error((string) $e);

            throw $e;
        }
    }

    /**
     * Rebuilds an asset's own properties from the scalar rows captured in the delete log
     * (see Asset\WebDAV\File::delete()). Mirrors how Asset\Dao::getProperties() hydrates
     * properties from the database, so setDataFromResource() receives the raw scalar value.
     * For date-type properties that hydration is not instantiation-free: their `data` column
     * holds a serialized datetime string which setDataFromResource() unserializes - the same
     * standard path normal property loading uses.
     *
     * cid/cpath are intentionally not set here: Asset::update() assigns them from the target
     * asset when the properties are persisted on save().
     *
     * @param array<mixed> $rows raw `properties` rows (name, type, data, inheritable) from the delete log
     */
    private function restoreProperties(Asset $asset, array $rows): void
    {
        $properties = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $data = $row['data'] ?? null;
            if ($data !== null && !is_string($data)) {
                // properties.data is a string column; anything else is a malformed log entry
                continue;
            }

            $name = (string) ($row['name'] ?? '');

            $property = new Property();
            $property->setType((string) ($row['type'] ?? ''));
            $property->setName($name);
            $property->setCtype('asset');
            $property->setDataFromResource($data);
            $property->setInherited(false);
            $property->setInheritable((bool) ($row['inheritable'] ?? false));

            $properties[$name] = $property;
        }

        if ($properties) {
            $asset->setProperties($properties);
        }
    }
}
