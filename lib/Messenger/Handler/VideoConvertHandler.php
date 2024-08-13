<?php
declare(strict_types=1);

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

use Pimcore\Messenger\VideoConvertMessage;
use Pimcore\Model\Asset\Video\Thumbnail\Processor;

/**
 * @internal
 */
class VideoConvertHandler
{
    public function __invoke(VideoConvertMessage $message): void
    {
        Processor::execute($message->getProcessId());
    }
}
