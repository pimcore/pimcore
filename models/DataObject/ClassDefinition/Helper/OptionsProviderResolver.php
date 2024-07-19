<?php
declare(strict_types=1);

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

    public static array $providerCache = [];

    public static function resolveProvider(?string $providerClass, int $mode): ?object
    {
        return self::resolve($providerClass, function ($provider) use ($mode) {

            if ($provider instanceof MultiSelectOptionsProviderInterface) {
                trigger_deprecation(
                    'pimcore/pimcore',
                    '11.2',
                    'Implementing %s is deprecated, use %s instead',
                    MultiSelectOptionsProviderInterface::class,
                    SelectOptionsProviderInterface::class,
                );
            }

            return ($mode == self::MODE_SELECT && ($provider instanceof SelectOptionsProviderInterface))
                || ($mode == self::MODE_MULTISELECT && ($provider instanceof MultiSelectOptionsProviderInterface))
                || ($mode == self::MODE_MULTISELECT && ($provider instanceof SelectOptionsProviderInterface));
        });
    }
}
