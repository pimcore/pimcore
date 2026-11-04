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

namespace Pimcore\Tests\Support\Helper\DataType;

/**
 * A project-specific value object, as a custom block marshaller may store one into a block's
 * resource blob (see CustomValueObjectBlockMarshaller).
 */
final class CustomValueObject
{
    public function __construct(private readonly string $text)
    {
    }

    public function getText(): string
    {
        return $this->text;
    }
}
