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

namespace Pimcore\Cdn\Message;

/**
 * Dispatched when an asset folder is renamed or moved: descendants are repathed via a
 * single SQL UPDATE (no per-child events), so their CDN entries under the old paths
 * must be purged by walking the subtree asynchronously.
 */
final readonly class PurgeCdnAssetTreeMessage
{
    public function __construct(
        /** Previous full path of the renamed/moved folder, e.g. "/products". */
        public string $oldPath,
        /** Current full path of the folder, e.g. "/catalog". */
        public string $newPath,
    ) {
    }
}
