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

namespace Pimcore\Controller;

use Pimcore\Event\HttpKernel\ControllerEventProxy;
use Pimcore\Event\HttpKernel\ResponseEventProxy;

interface EventControllerInterface
{
    /**
     * @param ControllerEventProxy $event
     */
    public function onKernelController(ControllerEventProxy $event);

    /**
     * @param ResponseEventProxy $event
     */
    public function onKernelResponse(ResponseEventProxy $event);
}
