<?php

/**
 * Copyright 2012 Klarna AB
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * File containing the Klarna_Checkout_Order class
 *
 * PHP version 5.3
 *
 * @category  Payment
 * @package   Klarna_Checkout
 * @author    Klarna <support@klarna.com>
 * @copyright 2012 Klarna AB
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link      http://developers.klarna.com/
 */

/**
 * Implementation of the order resource
 *
 * @category  Payment
 * @package   Klarna_Checkout
 * @author    Majid G. <majid.garmaroudi@klarna.com>
 * @author    David K. <david.keijser@klarna.com>
 * @copyright 2012 Klarna AB
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link      http://developers.klarna.com/
 */
class Klarna_Checkout_Order
    implements Klarna_Checkout_ResourceInterface, ArrayAccess
{
    /**
     * Base URI that is used to create order resources
     *
     * @var string
     */
    public static $baseUri = null;

    /**
     * Content Type to use
     *
     * @var string
     */
    public static $contentType = null;

    /**
     * URI of remote resource
     *
     * @var string
     */
    private $_location;

    /**
     * Order data
     *
     * @var array
     */
    private $_data = array();

    /**
     * Connector
     *
     * @var Klarna_Checkout_ConnectorInterface
     */
    protected $connector;

    /**
     * Create a new Order object
     *
     * @param Klarna_Checkout_ConnectorInterface $connector connector to use
     * @param string                             $uri       uri of resource
     *
     * @return void
     */
    public function __construct(
        Klarna_Checkout_ConnectorInterface $connector,
        $uri = null
    ) {
        $this->connector = $connector;
        if ($uri !== null) {
            $this->setLocation($uri);
        }
    }

    /**
     * Get the URL of the resource
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->_location;
    }

    /**
     * Set the URL of the resource
     *
     * @param string $location URL of the resource
     *
     * @return void
     */
    public function setLocation($location)
    {
        $this->_location = strval($location);
    }

    /**
     * Return content type of the resource
     *
     * @return string Content type
     */
    public function getContentType()
    {
        return self::$contentType;
    }

    /**
     * Replace resource data
     *
     * @param array $data data
     *
     * @return void
     */
    public function parse(array $data)
    {
        $this->_data = $data;
    }

    /**
     * Basic representation of the object
     *
     * @return array Data
     */
    public function marshal()
    {
        return $this->_data;
    }

    /**
     * Create a new order
     *
     * @param array $data data to initialise order resource with
     *
     * @return void
     */
    public function create(array $data)
    {
        $options = array(
            'url' => self::$baseUri,
            'data' => $data
        );

        $this->connector->apply('POST', $this, $options);
    }

    /**
     * Fetch order data
     *
     * @return void
     */
    public function fetch()
    {
        $options = array(
            'url' => $this->_location
        );
        $this->connector->apply('GET', $this, $options);
    }

    /**
     * Update order data
     *
     * @param array $data data to update order resource with
     *
     * @return void
     */
    public function update(
        array $data
    ) {
        $options = array(
            'url' => $this->_location,
            'data' => $data
        );
        $this->connector->apply('POST', $this, $options);
    }

    /**
     * Get value of a key
     *
     * @param string $key Key
     *
     * @return mixed data
     */
    public function offsetGet($key)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException("Key must be string");
        }

        return $this->_data[$key];
    }

    /**
     * Set value of a key
     *
     * @param string $key   Key
     * @param mixed  $value Value of the key
     *
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException("Key must be string");
        }

        $value = print_r($value, true);
        throw new RuntimeException(
            "Use update function to change values. trying to set $key to $value"
        );
    }

    /**
     * Check if a key exists in the resource
     *
     * @param string $key key
     *
     * @return boolean
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->_data);
    }

    /**
     * Unset the value of a key
     *
     * @param string $key key
     *
     * @return void
     */
    public function offsetUnset($key)
    {
        throw new RuntimeException(
            "unset of fields not supported. trying to unset $key"
        );
    }
}
