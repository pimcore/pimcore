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
 * File containing the Klarna_Checkout_HTTP_Request class
 *
 * PHP version 5.3
 *
 * @category   Payment
 * @package    Payment_Klarna
 * @subpackage HTTP
 * @author     Klarna <support@klarna.com>
 * @copyright  2012 Klarna AB
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link       http://developers.klarna.com/
 */

/**
 * Klarna HTTP Request class
 *
 * @category   Payment
 * @package    Payment_Klarna
 * @subpackage HTTP
 * @author     Klarna <support@klarna.com>
 * @copyright  2012 Klarna AB
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link       http://developers.klarna.com/
 */
class Klarna_Checkout_HTTP_Request
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var array
     */
    protected $headers;

    /**
     * @var string
     */
    protected $data;

    /**
     * Initializes a new instance of the HTTP request class.
     *
     * @param string $url the request URL.
     *
     * @throws InvalidArgumentException If the specified argument
     *                                  is not of type string.
     */
    public function __construct($url)
    {
        $this->url = $url;
        $this->method = 'GET';
        $this->headers = array();
        $this->data = '';
    }

    /**
     * Gets the request URL.
     *
     * @return string the request URL.
     */
    public function getURL()
    {
        return $this->url;
    }

    /**
     * Specifies the HTTP method used for the request.
     *
     * @param string $method a HTTP method.
     *
     * @throws InvalidArgumentException If the specified argument
     *                                  is not of type string.
     * @return void
     */
    public function setMethod($method)
    {
        $this->method = strtoupper($method);
    }

    /**
     * Gets the HTTP method used for the request.
     *
     * @return string a HTTP method
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Specifies a header for the request.
     *
     * @param string $name  the header name
     * @param mixed  $value the header value
     *
     * @throws InvalidArgumentException If the argument name is not of type
     *                                  string or an empty string.
     * @return void
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = strval($value);
    }

    /**
     * Gets a specific header for the request.
     *
     * @param string $name the header name
     *
     * @throws InvalidArgumentException If the specified argument
     *                                  is not of type string.
     * @return string|null the header value or null if it doesn't exist
     */
    public function getHeader($name)
    {
        if (!array_key_exists($name, $this->headers)) {
            return null;
        }

        return $this->headers[$name];
    }

    /**
     * Gets the headers specified for the request.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Sets the data (payload) for the request.
     *
     * \code
     * $request->setMethod('POST');
     * $request->setData('some data');
     * \endcode
     *
     * @param string $data the request payload
     *
     * @throws InvalidArgumentException If the specified argument
     *                                  is not of type string.
     * @return void
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Gets the data (payload) for the request.
     *
     * @return string the request payload
     */
    public function getData()
    {
        return $this->data;
    }
}
