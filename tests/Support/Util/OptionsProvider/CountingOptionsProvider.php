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

namespace Pimcore\Tests\Support\Util\OptionsProvider;

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;

/**
 * Options provider that offers a static list and counts how often it was asked for it. The core
 * providers read a row per answer, so the count is what a statement count of the real thing would
 * measure.
 */
class CountingOptionsProvider implements SelectOptionsProviderInterface
{
    public static int $calls = 0;

    public static function reset(): void
    {
        self::$calls = 0;
    }

    public function getOptions(array $context, Data $fieldDefinition): array
    {
        self::$calls++;

        return [
            ['key' => 'Option A', 'value' => 'a'],
            ['key' => 'Option B', 'value' => 'b'],
            ['key' => 'Option C', 'value' => 'c'],
        ];
    }

    public function hasStaticOptions(array $context, Data $fieldDefinition): bool
    {
        return true;
    }

    public function getDefaultValue(array $context, Data $fieldDefinition): string|array|null
    {
        return null;
    }
}
