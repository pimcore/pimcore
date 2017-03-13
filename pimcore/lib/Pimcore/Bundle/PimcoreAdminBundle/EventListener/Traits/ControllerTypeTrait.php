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

namespace Pimcore\Bundle\PimcoreAdminBundle\EventListener\Traits;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

trait ControllerTypeTrait
{
    /**
     * Get controller of specified type
     *
     * @param FilterControllerEvent $event
     * @param $type
     * @return mixed
     */
    protected function getControllerType(FilterControllerEvent $event, $type)
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
     * @param FilterControllerEvent $event
     * @param $type
     * @return bool
     */
    protected function isControllerType(FilterControllerEvent $event, $type)
    {
        $controller = $this->getControllerType($event, $type);

        return $controller && $controller instanceof $type;
    }
}
