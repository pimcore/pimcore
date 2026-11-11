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

use PDO;
use Pimcore;
use Pimcore\Db;
use Pimcore\Model\Asset;
use Pimcore\Tool\Serialize;
use Sabre\DAV\Browser\Plugin as BrowserPlugin;
use Sabre\DAV\Locks\Backend\PDO as LocksPDO;
use Sabre\DAV\Locks\Plugin as LocksPlugin;
use Sabre\DAV\Server;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
class Service
{
    /**
     * Builds the Sabre server behind the WebDAV endpoint, rooted at the asset home folder.
     */
    public static function createServer(string $baseUri): Server
    {
        $server = new Server(new Tree(new Folder(Asset::getById(1))));
        $server->setBaseUri($baseUri);

        // guards every method, including those that never resolve a node through the tree (UNLOCK)
        $server->addPlugin(new AuthenticationPlugin());

        /** @var PDO $pdo */
        $pdo = Db::get()->getNativeConnection();
        $lockBackend = new LocksPDO($pdo);
        $lockBackend->tableName = 'webdav_locks';
        $server->addPlugin(new LocksPlugin($lockBackend));

        $server->addPlugin(new BrowserPlugin());

        return $server;
    }

    public static function getDeleteLogFile(): string
    {
        return PIMCORE_SYSTEM_TEMP_DIRECTORY . '/webdav-delete.dat';
    }

    /**
     * Builds the delete-log entry File::delete() records for an asset, so Tree::move() can turn a
     * third-party "delete then move" back into an overwrite of the existing asset.
     *
     * The payload is the whole asset, serialized in dump state, and it is signed together with the
     * asset's path and the entry's timestamp (see restoreDeletedAsset() for why).
     *
     * @return array{id: int|null, timestamp: int, data: string, signature: string|null}
     */
    public static function createDeleteLogEntry(Asset $asset): array
    {
        $asset->setInDumpState(true);

        try {
            $payload = Serialize::serialize($asset);
        } finally {
            $asset->setInDumpState(false);
        }

        $timestamp = time();

        return [
            'id' => $asset->getId(),
            'timestamp' => $timestamp,
            'data' => $payload,
            'signature' => self::signDeleteLogPayload($payload, $asset->getRealFullPath(), $timestamp),
        ];
    }

    /**
     * Rebuilds the asset of a delete-log entry written by createDeleteLogEntry().
     *
     * Object deserialization has to stay enabled here: the payload is a whole serialized Asset.
     * Forbidding it yielded an __PHP_Incomplete_Class, which is truthy, so the caller's setData()
     * call became fatal - see pimcore/platform-version#298 for the same defect in document
     * editables. An allowlist is not viable either. The asset is serialized in dump state, where
     * Asset::getBlockedVars() keeps `properties` and `children`, so the graph also holds Property
     * objects (a `date` property carries a Carbon), further Asset objects, and the free-form
     * `customSettings` array that bundles write into.
     *
     * The delete log is a file anyone with write access to the system temp directory can forge, so
     * the payload is authenticated instead: only a payload whose signature matches the path and the
     * timestamp it was logged with is unserialized - the timestamp bounds the entry's lifetime in
     * getDeleteLog(), so it must not be extendable either. Unsigned entries (e.g. written by an
     * earlier version), tampered or moved ones are ignored.
     *
     * Returns null in those cases, and when the payload does not rebuild into an Asset, so the
     * caller can fall back instead of operating on an unusable value.
     */
    public static function restoreDeletedAsset(string $path, array $entry): ?Asset
    {
        $payload = $entry['data'] ?? null;
        $signature = $entry['signature'] ?? null;
        $timestamp = $entry['timestamp'] ?? null;
        if (!is_string($payload) || !is_string($signature) || !is_int($timestamp)) {
            return null;
        }

        $expectedSignature = self::signDeleteLogPayload($payload, $path, $timestamp);
        if ($expectedSignature === null || !hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $asset = Serialize::unserialize($payload, true);

        return $asset instanceof Asset ? $asset : null;
    }

    /**
     * Returns null when the application secret is not available. The entry is then logged
     * unsigned, so a later move falls back to a plain move instead of restoring the asset.
     */
    private static function signDeleteLogPayload(string $payload, string $path, int $timestamp): ?string
    {
        $secret = Pimcore::getContainer()->getParameter('secret');
        if ($secret === '') {
            return null;
        }

        // length-prefixed path and a ':'-terminated timestamp, so the parts cannot be shifted against
        // each other
        $message = strlen($path) . ':' . $path . $timestamp . ':' . $payload;

        return hash_hmac('sha256', $message, hash_hmac('sha256', 'pimcore.webdav_delete_log', $secret, true));
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
