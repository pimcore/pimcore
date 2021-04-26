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

namespace Pimcore\Model\DataObject\ClassDefinition\Helper;

use Pimcore\Model\DataObject\ClassDefinition\CalculatorClassInterface;

/**
 * @internal
 */
final class CalculatorClassResolver extends ClassResolver
{
    public static function resolveCalculatorClass($calculatorClass)
    {
        return self::resolve($calculatorClass, static function ($generator) {
            return $generator instanceof CalculatorClassInterface;
        });
    }
}
