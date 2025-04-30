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

use Pimcore\Model\DataObject\QuantityValue\QuantityValueConverterInterface;

/**
 * @internal
 */
class UnitConverterResolver extends ClassResolver
{
    public static function resolveUnitConverter(string $converterServiceName): ?QuantityValueConverterInterface
    {
        /** @var QuantityValueConverterInterface $converter */
        $converter = self::resolve('@' . $converterServiceName, static function ($converterService) {
            return $converterService instanceof QuantityValueConverterInterface;
        });

        return $converter;
    }
}
