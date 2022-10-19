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

/**
 * @internal
 */
final class Check implements \ArrayAccess
{
    const STATE_OK = 1;

    const STATE_WARNING = 2;

    const STATE_ERROR = 3;

    public string $name;

    public string $link;

    public string $state;

    public string $message;

    /**
     * Check constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function setLink(string $link)
    {
        $this->link = $link;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state)
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

    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    /**
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->$offset);
    }

    /**
     * @param string $offset
     *
     * @return string
     */
    public function offsetGet($offset): string
    {
        return $this->{'get'.$offset}();
    }

    /**
     * @param string $offset
     * @param string $value
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
