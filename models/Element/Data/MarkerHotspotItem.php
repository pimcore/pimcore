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

namespace Pimcore\Model\Element\Data;

use ArrayAccess;
use Pimcore\Model;

/**
 * @internal
 */
class MarkerHotspotItem implements ArrayAccess
{
    public string $name = '';

    public string $type = '';

    public mixed $value = null;

    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $setter = 'set' . $key;
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            }
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    /**
     * @param string $offset
     *
     */
    public function offsetExists($offset): bool
    {
        return property_exists($this, $offset);
    }

    /**
     * @param string $offset
     *
     */
    public function offsetGet($offset): mixed
    {
        if ($this->offsetExists($offset)) {
            if ($offset === 'value' && in_array($this->type, ['object', 'asset', 'document']) && $this->value) {
                return Model\Element\Service::getElementById($this->type, $this->value);
            }

            return $this->$offset;
        }

        return null;
    }

    /**
     * @param string $offset
     */
    public function offsetSet($offset, mixed $value): void
    {
        if ($this->offsetExists($offset)) {
            if ($value instanceof Model\Element\ElementInterface) {
                $value = $value->getId();
            }

            $this->$offset = $value;
        }
    }

    /**
     * @param string $offset
     */
    public function offsetUnset($offset): void
    {
        if ($this->offsetExists($offset)) {
            $this->$offset = null;
        }
    }
}
