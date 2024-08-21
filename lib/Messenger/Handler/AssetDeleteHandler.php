<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Messenger\Handler;

use Exception;
use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Messenger\AssetDeleteMessage;
use Pimcore\Tool\Storage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use function sprintf;

/**
 * @internal
 */
#[AsMessageHandler]
class AssetDeleteHandler
{
    public function __invoke(AssetDeleteMessage $message): void
    {
        $this->deletePhysicalFile($message);
    }

    private function deletePhysicalFile(AssetDeleteMessage $message): void
    {
        $storage = Storage::get('asset');

        try {
            if ($message->isFolder()) {
                $storage->deleteDirectory($message->getFullPath());
            } else {
                $storage->delete($message->getFullPath());
            }
        } catch (Exception $e) {
            Logger::err(sprintf('Problem deleting the asset physical file with ID: %s and fullpath: %s, reason: %s',
                $message->getId(),
                $message->getFullPath(),
                $e->getMessage()));
        }

        // Clear thumbnails and asset cache
        foreach (['thumbnail', 'asset_cache'] as $storageName) {
            $storage = Storage::get($storageName);
            $storage->deleteDirectory($message->getFullPath());
        }

        $db = Db::get();
        $db->delete('assets_image_thumbnail_cache', ['cid' => $message->getId()]);

    }
}
