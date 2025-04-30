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

namespace Pimcore\ValueObject\Collection;

use ValueError;

final class ArrayOfStrings
{
    /**
     * @throws ValueError
     */
    public function __construct(private readonly array $value)
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
        foreach ($this->value as $value) {
            if (!is_string($value)) {
                throw new ValueError(
                    sprintf(
                        'Provided array must contain only string values. (%s given)',
                        gettype($value)
                    ),
                );
            }
        }
    }

    /**
     * @return string[]
     */
    public function getValue(): array
    {
        return $this->value;
    }
}
