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
use Pimcore\Loader\ImplementationLoader\Exception\UnsupportedException;
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
                    // The destination was already deleted (e.g. Photoshop replaces a file via
                    // delete + create + move). Re-create it from the source content while reusing
                    // the deleted asset's id, so hardcoded references to that id stay valid.
                    // save() re-inserts the row via upsert; the source asset is removed below.
                    $logEntry = $log['/' . $destinationPath];
                    $restoredId = $logEntry['id'] ?? null;

                    // Only an entry carrying a usable id can drive a restore. Anything else must
                    // fall through to the plain rename below WITHOUT touching $sourceAsset:
                    // assigning it here would alias $asset to the same asset via the fallback,
                    // and the post-save source cleanup would then delete the just-moved file.
                    if (is_numeric($restoredId) && (int) $restoredId > 0) {
                        $sourceAsset = Asset::getByPath('/' . $sourcePath);
                        if (!$sourceAsset) {
                            throw new NotFound('Source asset not found');
                        }

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

                        // restore the lock state: Asset\Dao::update() only recreates the
                        // tree_locks row when getLocked() is set, so without this the restore
                        // would silently unlock an asset that an in-place overwrite leaves locked
                        $locked = $logEntry['locked'] ?? null;
                        if (is_string($locked) && $locked !== '') {
                            $asset->setLocked($locked);
                        }

                        // restore the deleted destination's own properties and metadata from the
                        // scalar snapshot, so they survive the delete + create + move round-trip
                        $properties = $logEntry['properties'] ?? [];
                        $this->restoreProperties($asset, is_array($properties) ? $properties : []);

                        // restore user-set custom settings (e.g. focal point) from the raw JSON
                        // snapshot - setCustomSettings() decodes the JSON string itself; settings
                        // derived from the binary are recomputed on save for the new data
                        $customSettings = $logEntry['customSettings'] ?? null;
                        if (is_string($customSettings) && $customSettings !== '') {
                            $asset->setCustomSettings($customSettings);
                        }

                        // workspace rows (explicit per-asset grants/denies) can only be
                        // re-inserted after save() has re-created the assets row (FK on cid),
                        // so they are stashed here and applied below
                        $workspaces = $logEntry['workspaces'] ?? null;
                        if (is_array($workspaces) && $workspaces !== []) {
                            $restoredWorkspaces = $workspaces;
                        }

                        // hydrate the metadata snapshot through the configured metadata types
                        // (mirroring Asset\Dao::getById()): a bundle-defined type may transform
                        // its stored value in getDataFromResource(), and save() converts back
                        // via getDataForResource() - feeding the raw rows to setMetadataRaw()
                        // directly would double-convert such types (core types are pass-through)
                        $metadata = $logEntry['metadata'] ?? [];
                        if (is_array($metadata) && $metadata !== []) {
                            $this->restoreMetadata($asset, $metadata);
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

            if (isset($restoredWorkspaces)) {
                $this->restoreWorkspaces($asset, $restoredWorkspaces);
            }

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
     * properties from the database, so setDataFromResource() receives the raw scalar value -
     * except for date rows, whose serialized datetime is re-hydrated here with an explicit
     * DateTime/Carbon allowlist so log content never reaches a permissive unserializer.
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
            $type = (string) ($row['type'] ?? '');

            $property = new Property();
            $property->setType($type);
            $property->setName($name);
            $property->setCtype('asset');

            if ($type === 'date' && $data !== null) {
                // a date row holds a serialized DateTimeInterface; setDataFromResource() would
                // unserialize it with allowed_classes: true, so hydrate it here with an explicit
                // allowlist instead - nothing read from the delete log may instantiate
                // arbitrary classes. Unexpected payloads are skipped defensively.
                $date = \Pimcore\Tool\Serialize::unserialize($data, [
                    \Carbon\Carbon::class,
                    \Carbon\CarbonImmutable::class,
                    \DateTime::class,
                    \DateTimeImmutable::class,
                ]);
                if (!$date instanceof \DateTimeInterface) {
                    continue;
                }
                $property->setData($date);
            } else {
                $property->setDataFromResource($data);
            }

            $property->setInherited(false);
            $property->setInheritable((bool) ($row['inheritable'] ?? false));

            $properties[$name] = $property;
        }

        if ($properties) {
            $asset->setProperties($properties);
        }
    }

    /**
     * Hydrates raw `assets_metadata` rows from the delete log into the model-level metadata
     * representation, mirroring Asset\Dao::getById(): each row's data runs through the configured
     * metadata type's getDataFromResource(), so a bundle-defined type that transforms its stored
     * value round-trips correctly when save() converts back with getDataForResource(). Core
     * types are pass-through, so their behavior is unchanged.
     *
     * @param array<mixed> $rows raw `assets_metadata` rows (name, type, data, language) from the delete log
     */
    private function restoreMetadata(Asset $asset, array $rows): void
    {
        $loader = \Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');

        $metadata = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $type = (string) ($row['type'] ?? '');
            if ($type === '') {
                $type = 'input';
            }

            $item = [
                'name' => (string) ($row['name'] ?? ''),
                'type' => $type,
                'data' => $row['data'] ?? null,
                'language' => (string) ($row['language'] ?? ''),
                // the Dao load path hands the full row - including cid - to
                // getDataFromResource(); the snapshot drops cid, but it always equals the
                // restored id, so provide it for types that read $params['cid']
                'cid' => $asset->getId(),
            ];

            try {
                /** @var \Pimcore\Model\Asset\MetaData\ClassDefinition\Data\Data $instance */
                $instance = $loader->build($item['type']);
                $item['data'] = $instance->getDataFromResource($item['data'], $item);
            } catch (UnsupportedException $e) {
                // unknown type: keep the raw value, same as the Dao load path
            }

            // like the Dao load path: cid is not part of the model-level representation
            unset($item['cid']);
            $metadata[] = $item;
        }

        if ($metadata) {
            $asset->setMetadataRaw($metadata);
        }
    }

    /**
     * Re-inserts the destination's per-asset workspace rows (explicit permission grants/denies)
     * captured in the delete log. They were removed together with the asset by the
     * ON DELETE CASCADE on users_workspaces_asset.cid, and nothing else recreates them - without
     * this a restored asset would silently fall back to purely inherited permissions.
     * cid/cpath are rewritten to the restored asset; all other columns are scalar permission
     * flags and are re-inserted as captured.
     *
     * @param array<mixed> $rows raw `users_workspaces_asset` rows from the delete log
     */
    private function restoreWorkspaces(Asset $asset, array $rows): void
    {
        $db = \Pimcore\Db::get();

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            foreach ($row as $value) {
                if ($value !== null && !is_scalar($value)) {
                    // workspace rows hold only scalar columns; anything else is malformed
                    continue 2;
                }
            }

            $row['cid'] = $asset->getId();
            $row['cpath'] = $asset->getRealFullPath();

            // quoting is required: the table has MySQL-reserved column names
            // (`delete`, `rename`, `create`) - same as User\Workspace\Dao::save()
            $db->insert('users_workspaces_asset', \Pimcore\Db\Helper::quoteDataIdentifiers($db, $row));
        }
    }
}
