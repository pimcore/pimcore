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

namespace Pimcore\Bundle\AdminBundle\EventListener\Traits;

use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * @internal
 */
trait ControllerTypeTrait
{
    /**
     * Get controller of specified type
     *
     * @param ControllerEvent $event
     * @param string $type
     *
     * @return mixed
     */
    protected function getControllerType(ControllerEvent $event, $type)
    {
        $callable = $event->getController();

        if (!is_array($callable) || count($callable) === 0) {
            return null;
        }

        $controller = $callable[0];
        if ($controller instanceof $type) {
            return $controller;
        }
    }

    /**
     * Test if event controller is of the given type
     *
     * @param ControllerEvent $event
     * @param string $type
     *
     * @return bool
     */
    protected function isControllerType(ControllerEvent $event, $type)
    {
        $controller = $this->getControllerType($event, $type);

        return $controller && $controller instanceof $type;
    }
}
