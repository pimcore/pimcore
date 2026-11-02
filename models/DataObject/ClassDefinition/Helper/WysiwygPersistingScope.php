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

/**
 * Marks code that reads field values in order to store them (saving, copying, rewriting ids). Inside it, wysiwyg
 * values keep their links to unpublished elements, whichever request or process runs it (Studio batch edit, copy,
 * scheduled publishing, import), so storing the value does not lose them.
 *
 * @internal
 */
final class WysiwygPersistingScope
{
    private static bool $active = false;

    public static function isActive(): bool
    {
        return self::$active;
    }

    public static function run(callable $fn): mixed
    {
        $backup = self::$active;
        self::$active = true;

        try {
            return $fn();
        } finally {
            self::$active = $backup;
        }
    }
}
