<?php

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

use Pimcore\Model;

/**
 * @internal
 */
class MarkerHotspotItem implements \ArrayAccess
{
    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $type = '';

    /**
     * @var mixed
     */
    public $value;

    /**
     * @param array $data
     */
    public function __construct($data)
    {
        foreach ($data as $key => $value) {
            $setter = 'set' . $key;
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            }
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return property_exists($this, $offset);
    }

    /**
     * @param string $offset
     *
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        if ($this->offsetExists($offset)) {
            if ($offset === 'value' && in_array($this->type, ['object', 'asset', 'document'])) {
                return Model\Element\Service::getElementById($this->type, $this->value);
            }

            return $this->$offset;
        }

        return null;
    }

    /**
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
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
