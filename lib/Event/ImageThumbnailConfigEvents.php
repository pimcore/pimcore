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

namespace Pimcore\Event;

final class ImageThumbnailConfigEvents
{
    /**
     * @Event("Pimcore\Event\Model\Asset\Image\Thumbnail\ConfigEvent")
     *
     * @var string
     */
    public const POST_UPDATE = 'pimcore.asset.image.thumbnailConfig.postUpdate';

    /**
     * @Event("Pimcore\Event\Model\Asset\Image\Thumbnail\ConfigEvent")
     *
     * @var string
     */
    public const POST_DELETE = 'pimcore.asset.image.thumbnailConfig.postDelete';
}
