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

use Pimcore\Model\DataObject\ClassDefinition\PathFormatterInterface;

/**
 * @internal
 */
class PathFormatterResolver extends ClassResolver
{
    public static array $formatterCache = [];

    public static function resolvePathFormatter(string $formatterClass): ?PathFormatterInterface
    {
        /** @var PathFormatterInterface $formatter */
        $formatter = self::resolve($formatterClass, static function ($formatter) {
            return $formatter instanceof PathFormatterInterface;
        });

        return $formatter;
    }
}
