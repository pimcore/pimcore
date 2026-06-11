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

namespace Pimcore\Cdn\Message\Handler;

use Pimcore\Cdn\AssetWebPath;
use Pimcore\Cdn\CdnAssetTag;
use Pimcore\Cdn\Message\PurgeCdnAssetTreeMessage;
use Pimcore\Cdn\PurgeClientInterface;
use Pimcore\Db;
use Pimcore\Db\Helper;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 *
 * Purges the CDN entries of all descendants of a renamed/moved asset folder.
 *
 * When a folder is renamed, Dao::updateChildPaths() repaths the children via a single
 * SQL UPDATE without per-child events, so CdnPurgeListener can only cover the folder
 * itself synchronously. This handler walks the subtree in the worker:
 *  - asset-{id} tag per descendant (clears its thumbnails, cached under the old folder URLs)
 *  - asset-path-{hash} tag of each descendant's OLD path (clears the original entry)
 *  - URL purge of each old original path when CDN_BASE_URL is configured (originals
 *    served statically never received a Surrogate-Key, tag purge cannot reach them)
 */
#[AsMessageHandler]
class PurgeCdnAssetTreeMessageHandler
{
    public function __construct(
        private readonly PurgeClientInterface $purgeClient,
        private readonly CdnAssetTag $assetTag,
        private readonly AssetWebPath $assetWebPath,
        #[Autowire('%pimcore.cdn.base_url%')]
        private readonly string $cdnBaseUrl = '',
    ) {
    }

    public function __invoke(PurgeCdnAssetTreeMessage $message): void
    {
        $tags = [];
        $urls = [];
        $base = rtrim($this->cdnBaseUrl, '/');

        foreach ($this->loadDescendants($message->newPath) as $descendant) {
            // The DB already holds the NEW paths; the old path is the old folder
            // prefix plus the descendant's path relative to the new folder path.
            $oldChildPath = $message->oldPath . substr($descendant['fullPath'], strlen($message->newPath));

            $tags[] = $this->assetTag->forAsset((int) $descendant['id']);
            $tags[] = $this->assetTag->forPath($this->assetWebPath->forFullPath($oldChildPath));

            if ($base !== '') {
                $urls[] = $base . $this->assetWebPath->encode($this->assetWebPath->forFullPath($oldChildPath));
            }
        }

        if ($tags !== []) {
            // purgeByTags() chunks to the provider's per-request key limit.
            $this->purgeClient->purgeByTags(array_values(array_unique($tags)));
        }

        foreach ($urls as $url) {
            $this->purgeClient->purgeByUrl($url);
        }
    }

    /**
     * Plain SQL instead of Asset\Listing: only id and full path are needed, hydrating
     * full Asset models for a potentially large subtree would be wasted work. Folders
     * are skipped — they have no cacheable binary of their own.
     *
     * Extracted as a seam so unit tests can stub the lookup without a database.
     *
     * @return iterable<array{id: int|string, fullPath: string}>
     */
    protected function loadDescendants(string $folderPath): iterable
    {
        return Db::get()->fetchAllAssociative(
            "SELECT id, CONCAT(`path`, filename) AS fullPath FROM assets WHERE `path` LIKE ? AND `type` != 'folder'",
            [Helper::escapeLike($folderPath) . '/%'],
        );
    }
}
