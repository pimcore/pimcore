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

namespace Pimcore\Model\DataObject;

interface SelectOptionsInterface
{
    public function getLabel(): string;

    /**
     * @return string[]
     */
    public static function getValues(): array;

    public static function tryFromNullable(?string $value): ?static;
}
