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

use Pimcore\Model\Asset\Image\ThumbnailInterface as ImageThumbnailInterface;

interface ThumbnailMediaInterface
{
    public function getMedia(string $name, int $highRes = 1): ?ImageThumbnailInterface;
}
