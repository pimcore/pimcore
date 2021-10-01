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

use Pimcore\Image\ImageOptimizerInterface;
use Pimcore\Messenger\OptimizeImageMessage;
use Pimcore\Tool\Storage;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class OptimizeImageHandler
{
    public function __construct(protected ImageOptimizerInterface $optimizer, protected LoggerInterface $logger)
    {
    }

    public function __invoke(OptimizeImageMessage $message)
    {
        $storage = Storage::get('thumbnail');

        $path = $message->getPath();

        if ($storage->fileExists($path)) {
            $originalFilesize = $storage->fileSize($path);
            $this->optimizer->optimizeImage($path);

            $this->logger->debug('Optimized image: '.$path.' saved '.formatBytes($originalFilesize - $storage->fileSize($path)));
        } else {
            $this->logger->debug('Skip optimizing of '.$path." because it doesn't exist anymore");
        }
    }
}
