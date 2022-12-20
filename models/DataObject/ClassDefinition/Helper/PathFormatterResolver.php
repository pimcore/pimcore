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
