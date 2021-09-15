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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Messenger\Handler;

use Pimcore\Messenger\ClearThumbnailsMessage;
use Pimcore\Tool\Console;

class ClearThumbnailsHandler
{
    public function __invoke(ClearThumbnailsMessage $clearThumbnailsMessage)
    {
        $arguments = [
            'pimcore:thumbnails:clear',
            '--type=' . $clearThumbnailsMessage->getType(),
            '--name='.$clearThumbnailsMessage->getName(),
        ];

        Console::runPhpScript(realpath(PIMCORE_PROJECT_ROOT.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'console'), $arguments);
    }
}
