<?php
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

namespace Pimcore\Tool\Requirements;

class Check implements \ArrayAccess
{
    const STATE_OK = 1;
    const STATE_WARNING = 2;
    const STATE_ERROR = 3;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $link;

    /**
     * @var string
     */
    public $state;

    /**
     * @var string
     */
    public $message;

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
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param string $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        if (empty($this->message)) {
            return $this->getName() . ' is required.';
        }

        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    /**
     * @param string $offset
     *
     * @return string
     */
    public function offsetGet($offset)
    {
        return $this->{'get'.$offset}();
    }

    /**
     * @param string $offset
     * @param string $value
     */
    public function offsetSet($offset, $value)
    {
        $this->{'set'.$offset}($value);
    }

    /**
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }
}
