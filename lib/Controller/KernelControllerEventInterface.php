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

/**
 * Alias to support Symfony 3.4 where ControllerEvent is not available and required for this interface
 *
 * @TODO remove in Pimcore 7
 */

namespace {

    use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

    if (!class_exists('Symfony\Component\HttpKernel\Event\ControllerEvent')) {
        class_alias(FilterControllerEvent::class, 'Symfony\Component\HttpKernel\Event\ControllerEvent');
    }
}

namespace Pimcore\Controller {

    use Symfony\Component\HttpKernel\Event\ControllerEvent;

    interface KernelControllerEventInterface
    {
        /**
         * @param ControllerEvent $event
         */
        public function onKernelControllerEvent(ControllerEvent $event);
    }
}
