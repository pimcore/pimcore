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
     * @return mixed
     */
    public function getChilds()
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '4.4',
            sprintf('%s is deprecated, please use getChildren() instead.', __METHOD__)
        );

        return call_user_func_array([$this, 'getChildren'], func_get_args());
    }

    /**
     * @deprecated
     *
     * @return mixed
     */
    public function setChilds()
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '4.4',
            sprintf('%s is deprecated, please use setChildren() instead.', __METHOD__)
        );

        return call_user_func_array([$this, 'setChildren'], func_get_args());
    }

    /**
     * @deprecated
     *
     * @return mixed
     */
    public function hasChilds()
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '4.4',
            sprintf('%s is deprecated, please use hasChildren() instead.', __METHOD__)
        );

        return call_user_func_array([$this, 'hasChildren'], func_get_args());
    }
}
