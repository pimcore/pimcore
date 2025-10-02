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

use Pimcore\Model\DataObject\ClassDefinition\DefaultValueGeneratorInterface;

/**
 * @internal
 */
class DefaultValueGeneratorResolver extends ClassResolver
{
    public static function resolveGenerator(string $generatorClass): ?object
    {
        return self::resolve($generatorClass, static function ($generator) {
            return $generator instanceof DefaultValueGeneratorInterface;
        });
    }
}
