<?php
declare(strict_types=1);

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

namespace Pimcore\Tool\Requirements;

use ArrayAccess;

/**
 * @internal
 */
final class Check implements ArrayAccess
{
    const STATE_OK = 1;

    const STATE_WARNING = 2;

    const STATE_ERROR = 3;

    public string $name;

    public ?string $link = null;

    public int $state;

    public ?string $message = null;

    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(string $link): void
    {
        $this->link = $link;
    }

    public function getState(): int
    {
        return $this->state;
    }

    public function setState(int $state): void
    {
        $this->state = $state;
    }

    public function getMessage(): string
    {
        if (empty($this->message)) {
            return $this->getName() . ' is required.';
        }

        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @param string $offset
     *
     */
    public function offsetExists($offset): bool
    {
        return isset($this->$offset);
    }

    /**
     * @param string $offset
     *
     */
    public function offsetGet($offset): string|int|null
    {
        return $this->{'get'.$offset}();
    }

    /**
     * @param string $offset
     * @param string|int $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->{'set'.$offset}($value);
    }

    /**
     * @param string $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->$offset);
    }
}
