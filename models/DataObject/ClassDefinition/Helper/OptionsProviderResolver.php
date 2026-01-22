<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
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

    public static function resolveProvider(?string $providerClass, int $mode, bool $showError = false): ?object
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
        }, $showError);
    }
}
