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

namespace Pimcore\Messenger\Handler;

use Pimcore\Messenger\VideoConvertMessage;
use Pimcore\Model\Asset\Video\Thumbnail\Processor;

/**
 * @internal
 */
class VideoConvertHandler
{
    public function __invoke(VideoConvertMessage $message): void
    {
        Processor::execute($message->getProcessId(), $message->getAssetId());
    }
}
