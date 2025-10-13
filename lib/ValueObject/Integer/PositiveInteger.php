<?php

declare(strict_types = 1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\ValueObject\Integer;

use ValueError;

final class PositiveInteger
{
    /**
     * @throws ValueError
     */
    public function __construct(private readonly int $value)
    {
        $this->validate();
    }

    /**
     * @throws ValueError
     */
    public function __wakeup(): void
    {
        $this->validate();
    }

    private function validate(): void
    {
        if ($this->value <= 0) {
            throw new ValueError(
                sprintf(
                    'Provided integer must be positive. (%s given)',
                    $this->value
                ),
            );
        }
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function equals(PositiveInteger $positiveInteger): bool
    {
        return $this->value === $positiveInteger->getValue();
    }
}
