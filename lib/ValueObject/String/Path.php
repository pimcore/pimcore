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

namespace Pimcore\ValueObject\String;

use ValueError;

final class Path
{
    /**
     * @throws ValueError
     */
    public function __construct(private readonly string $path)
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
        if (!str_starts_with($this->path, '/')) {
            throw new ValueError('Path must start with a slash.');
        }

        if (str_contains($this->path, '//')) {
            throw new ValueError('Path must not contain consecutive slashes.');
        }
    }

    public function getValue(): string
    {
        return $this->path;
    }

    public function equals(Path $path): bool
    {
        return $this->path === $path->getValue();
    }
}
