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

namespace Pimcore\Model\Element;

/**
 * @internal
 */
trait ChildsCompatibilityTrait
{
    /**
     * @deprecated
     *
     * @throws \Exception
     *
     * @return array
     */
    public function getChilds()
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '4.4',
            sprintf('%s is deprecated, please use getChildren() instead. It will be removed in Pimcore 11.', __METHOD__)
        );

        if (method_exists($this, 'getChildren')) {
            return $this->getChildren(...func_get_args());
        }

        throw new \Exception('Method getChildren was not found');
    }

    /**
     * @deprecated
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function setChilds()
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '4.4',
            sprintf('%s is deprecated, please use setChildren() instead. It will be removed in Pimcore 11.', __METHOD__)
        );

        if (method_exists($this, 'setChildren')) {
            return $this->setChildren(...func_get_args());
        }

        throw new \Exception('Method setChildren was not found');
    }

    /**
     * @deprecated
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function hasChilds()
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '4.4',
            sprintf('%s is deprecated, please use hasChildren() instead. It will be removed in Pimcore 11.', __METHOD__)
        );

        if (method_exists($this, 'hasChildren')) {
            return $this->hasChildren(...func_get_args());
        }

        throw new \Exception('Method hasChildren was not found');
    }
}
