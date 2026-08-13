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

use Pimcore\Model\Asset;
use Pimcore\Tool\Serialize;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
class Service
{
    public static function getDeleteLogFile(): string
    {
        return PIMCORE_SYSTEM_TEMP_DIRECTORY . '/webdav-delete.dat';
    }

    /**
     * Rebuilds the asset that File::delete() stored in a delete-log entry, so Tree::move() can turn
     * a third-party "delete then move" back into an overwrite of the existing asset.
     *
     * Object deserialization has to stay enabled here: the payload is a whole serialized Asset.
     * Forbidding it yielded an __PHP_Incomplete_Class, which is truthy, so the caller's setData()
     * call became fatal - see pimcore/platform-version#298 for the same defect in document
     * editables.
     *
     * An allowlist is not viable either. The asset is serialized in dump state, where
     * Asset::getBlockedVars() keeps `properties` and `children`, so the graph also holds Property
     * objects (a `date` property carries a Carbon), further Asset objects, and the free-form
     * `customSettings` array that bundles write into. Scoping this is tracked with the other
     * permissive readers in pimcore/internal-improvements#24.
     *
     * Returns null when the payload does not rebuild into an Asset, so the caller can fall back
     * instead of operating on an unusable value.
     */
    public static function restoreDeletedAsset(string $payload): ?Asset
    {
        $asset = Serialize::unserialize($payload, true);

        return $asset instanceof Asset ? $asset : null;
    }

    public static function getDeleteLog(): array
    {
        $log = [];
        if (file_exists(self::getDeleteLogFile())) {
            $raw = file_get_contents(self::getDeleteLogFile());
            if (is_string($raw)) {
                // The log itself is a plain array of scalars: each entry holds an id, a timestamp
                // and the already-serialized asset as a string. The asset object is reconstructed
                // one level down, in Tree::move(), against getDeleteLogAllowedClasses().
                $log = unserialize($raw, ['allowed_classes' => false]);
            }

            if (!is_array($log)) {
                $log = [];
            } else {
                // cleanup old entries
                $tmpLog = [];
                foreach ($log as $path => $data) {
                    if ($data['timestamp'] > (time() - 30)) { // remove 30 seconds old entries
                        $tmpLog[$path] = $data;
                    }
                }

                $log = $tmpLog;
            }
        }

        return $log;
    }

    public static function saveDeleteLog(array $log): void
    {
        // cleanup old entries
        $tmpLog = [];
        foreach ($log as $path => $data) {
            if ($data['timestamp'] > (time() - 30)) { // remove 30 seconds old entries
                $tmpLog[$path] = $data;
            }
        }

        $filesystem = new Filesystem();
        $filesystem->dumpFile(Asset\WebDAV\Service::getDeleteLogFile(), serialize($tmpLog));
    }
}
