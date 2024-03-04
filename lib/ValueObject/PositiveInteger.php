<?php

declare(strict_types = 1);

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


namespace Pimcore\ValueObject;

use Pimcore\Exception\InvalidValueObjectException;

final class PositiveInteger
{

    /**
     * @throws InvalidValueObjectException
     */
    public function __construct(private readonly int $value)
    {
        $this->validate();
    }

    private function validate(): void
    {
        if ($this->value < 0) {
            throw new InvalidValueObjectException(
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
}
