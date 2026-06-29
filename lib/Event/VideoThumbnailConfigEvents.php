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

final class VideoThumbnailConfigEvents
{
    /**
     * @Event("Pimcore\Event\Model\Asset\Video\Thumbnail\ConfigEvent")
     *
     * @var string
     */
    public const POST_UPDATE = 'pimcore.asset.video.thumbnailConfig.postUpdate';

    /**
     * @Event("Pimcore\Event\Model\Asset\Video\Thumbnail\ConfigEvent")
     *
     * @var string
     */
    public const POST_DELETE = 'pimcore.asset.video.thumbnailConfig.postDelete';
}
