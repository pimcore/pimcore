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

namespace Pimcore\Contracts\ChangeControl;

use InvalidArgumentException;

/**
 * An element a relation points at, by its id.
 */
final readonly class ElementRef
{
    public function __construct(
        public ElementType $type,
        public int $id,
    ) {
        if ($id < 1) {
            throw new InvalidArgumentException(sprintf('An element has a positive id, not %d.', $id));
        }
    }

    public static function object(int $id): self
    {
        return new self(ElementType::DataObject, $id);
    }

    public static function asset(int $id): self
    {
        return new self(ElementType::Asset, $id);
    }

    public static function document(int $id): self
    {
        return new self(ElementType::Document, $id);
    }
}
