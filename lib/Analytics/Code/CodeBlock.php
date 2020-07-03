<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Analytics\Code;

/**
 * Represents a single template block. Parts are represented as array and concatenated
 * with newlines on render.
 */
final class CodeBlock
{
    /**
     * @var array
     */
    private $parts = [];

    public function __construct(array $parts = [])
    {
        $this->parts = $parts;
    }

    public function setParts(array $parts)
    {
        $this->parts = $parts;
    }

    public function getParts(): array
    {
        return $this->parts;
    }

    public function append($parts)
    {
        $parts = (array)$parts;

        foreach ($parts as $part) {
            $this->parts[] = $part;
        }
    }

    public function prepend($parts)
    {
        $parts = (array)$parts;
        $parts = array_reverse($parts); // prepend parts in the order they were passed

        foreach ($parts as $part) {
            array_unshift($this->parts, $part);
        }
    }

    public function asString(): string
    {
        $string = implode("\n", $this->parts);
        $string = trim($string);

        return $string;
    }

    public function __toString()
    {
        return $this->asString();
    }
}
