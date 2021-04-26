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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Controller;

use Symfony\Component\HttpKernel\Event\ControllerEvent;

interface KernelControllerEventInterface
{
    /**
     * @param ControllerEvent $event
     */
    public function onKernelControllerEvent(ControllerEvent $event);
}
