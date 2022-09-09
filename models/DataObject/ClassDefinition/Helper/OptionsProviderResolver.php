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

namespace Pimcore\Model\DataObject\ClassDefinition\Helper;

use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\MultiSelectOptionsProviderInterface;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;

/**
 * @internal
 */
class OptionsProviderResolver extends ClassResolver
{
    const MODE_SELECT = 1;

    const MODE_MULTISELECT = 2;

    public static $providerCache = [];

    /**
     * @param string|null $providerClass
     * @param int $mode
     *
     * @return mixed|null
     */
    public static function resolveProvider($providerClass, $mode)
    {
        return self::resolve($providerClass, function ($provider) use ($mode) {
            return ($mode == self::MODE_SELECT && ($provider instanceof SelectOptionsProviderInterface))
                || ($mode == self::MODE_MULTISELECT && ($provider instanceof MultiSelectOptionsProviderInterface));
        });
    }
}
