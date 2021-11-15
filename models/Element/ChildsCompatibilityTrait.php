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
     * @return mixed
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
     * @return mixed
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
     * @return mixed
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


    /**
     * @deprecated
     *
     * @param string $name
     */
    public function __get($name)
    {
        if ($name === 'childs' && property_exists($this, 'children')) {
            trigger_deprecation(
                'pimcore/pimcore',
                '10.3',
                'Accessing childs property is deprecated, please use getChildren() instead. It will be removed in Pimcore 11.'
            );

            return $this->children;
        }

        trigger_error(sprintf('Undefined property: %s::$%s', __CLASS__, $name), E_USER_NOTICE);

        return null;
    }

    /**
     * @deprecated
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        if ($name === 'childs' && property_exists($this, 'children')) {
            trigger_deprecation(
                'pimcore/pimcore',
                '10.3',
                'Accessing childs property is deprecated, please use setChildren() instead. It will be removed in Pimcore 11.'
            );
            $this->children = $value;
        }
    }

    /**
     * @deprecated
     *
     * @param string $name
     */
    public function __isset($name)
    {
        if ($name === 'childs' && property_exists($this, 'children')) {
            trigger_deprecation(
                'pimcore/pimcore',
                '10.3',
                'Accessing childs property is deprecated, please use getChildren() instead. It will be removed in Pimcore 11.'
            );
            return isset($this->children);
        }

        return false;
    }

    /**
     * @deprecated
     *
     * @param string $name
     */
    public function __unset($name)
    {
        if ($name === 'childs' && property_exists($this, 'children')) {
            trigger_deprecation(
                'pimcore/pimcore',
                '10.3',
                'Accessing childs property is deprecated, please use setChildren() instead. It will be removed in Pimcore 11.'
            );
            unset($this->children);
        }
    }
}
