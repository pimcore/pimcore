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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Messenger\Handler;

use Pimcore\Logger;
use Pimcore\Messenger\AssetPreviewImageMessage;
use Pimcore\Model\Asset;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class AssetPreviewImageHandler
{
    public function __construct(protected LoggerInterface $logger)
    {
    }

    public function __invoke(AssetPreviewImageMessage $message)
    {
        $asset = Asset::getById($message->getId());

        if ($asset instanceof Asset\Image) {
            $asset->getThumbnail(Asset\Image\Thumbnail\Config::getPreviewConfig())->generate(false);
        } elseif ($asset instanceof Asset\Document) {
            $asset->getImageThumbnail(Asset\Image\Thumbnail\Config::getPreviewConfig())->generate(false);
        } elseif ($asset instanceof Asset\Video) {
            $asset->getImageThumbnail(Asset\Image\Thumbnail\Config::getPreviewConfig())->generate(false);
        }
    }
}
