<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset\Thumbnail;

use League\Flysystem\StorageAttributes;
use Pimcore\Tool\Storage;

/**
 * @internal
 */
trait ClearTempFilesTrait
{
    public function clearTempFiles()
    {
        $storage = Storage::get('thumbnail');
        $contents = $storage->listContents('/', true)->filter(function (StorageAttributes $item) {
            return $item->isDir() && preg_match('@(image|video|pdf)\-thumb__[\d]+__' . preg_quote($this->getName(), '@') . '(?:_auto_.+)?$@', $item->path());
        })->map(fn (StorageAttributes $attributes) => $attributes->path())->toArray();

        foreach ($contents as $item) {
            $storage->deleteDirectory($item);
        }
    }
}
