<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Event\HttpKernel;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class_exists(ResponseEvent::class);

if (true) {
    /**
     * @inheritdoc
     */
    class ResponseEventProxy extends ResponseEvent
    {
    }
} else {
    /**
     * @inheritdoc
     */
    class ResponseEventProxy extends FilterResponseEvent
    {
    }
}
