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
