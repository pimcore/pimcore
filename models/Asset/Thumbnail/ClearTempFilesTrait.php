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

namespace Pimcore\Model\Asset\Thumbnail;

use League\Flysystem\StorageAttributes;
use Pimcore\Tool\Storage;

/**
 * @internal
 */
trait ClearTempFilesTrait
{
    public function clearTempFiles(): void
    {
        $storage = Storage::get('thumbnail');
        $contents = $storage->listContents('/', true)->filter(function (StorageAttributes $item) {
            return $item->isDir() && preg_match('@(image|video|pdf)-thumb__[\d]+__'.preg_quote($this->getName(), '@').'(?:_auto_.+)?$@', $item->path());
        })->map(fn (StorageAttributes $attributes) => $attributes->path())->toArray();

        foreach ($contents as $item) {
            $storage->deleteDirectory($item);
        }
    }
}
