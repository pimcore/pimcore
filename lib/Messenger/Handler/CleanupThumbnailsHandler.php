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

use Pimcore\Messenger\CleanupThumbnailsMessage;
use Pimcore\Model\Asset;

/**
 * @internal
 */
class CleanupThumbnailsHandler
{
    public function __invoke(CleanupThumbnailsMessage $message)
    {
        $configClass = 'Pimcore\Model\Asset\\' . ucfirst($message->getType()) . '\Thumbnail\Config';
        /** @var Asset\Image\Thumbnail\Config|Asset\Video\Thumbnail\Config|null $thumbConfig */
        $thumbConfig = new $configClass();
        $thumbConfig->setName($message->getName());
        $thumbConfig->clearTempFiles();
    }
}
