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
 * File containing the Klarna_HTTP_Response class
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
 * Klarna HTTP Response class
 *
 * @category   Payment
 * @package    Payment_Klarna
 * @subpackage HTTP
 * @author     Klarna <support@klarna.com>
 * @copyright  2012 Klarna AB
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link       http://developers.klarna.com/
 */
class Klarna_Checkout_HTTP_Response
{
    /**
     * @var int
     */
    protected $status;

    /**
     * @var Klarna_Checkout_HTTP_Request
     */
    protected $request;

    /**
     * @var array
     */
    protected $headers;

    /**
     * @var string
     */
    protected $data;

    /**
     * Initializes a new instance of the HTTP response class.
     *
     * @param Klarna_Checkout_HTTP_Request $request the origin request.
     * @param array                        $headers the response HTTP headers.
     * @param int                          $status  the HTTP status code.
     * @param string                       $data    the response payload.
     */
    public function __construct(
        Klarna_Checkout_HTTP_Request $request, array $headers, $status, $data
    ) {
        $this->request = $request;
        $this->headers = array();
        foreach ($headers as $key => $value) {
            $this->headers[strtolower($key)] = $value;
        }
        $this->status = $status;
        $this->data = $data;
    }

    /**
     * Gets the HTTP status code.
     *
     * @return int HTTP status code.
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Gets the HTTP request this response originated from.
     *
     * @return Klarna_Checkout_HTTP_Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Gets specified HTTP header.
     *
     * @param string $name the header name.
     *
     * @throws InvalidArgumentException If the specified argument
     *                                  is not of type string.
     * @return string|null Null if header doesn't exist, else header value.
     */
    public function getHeader($name)
    {
        $name = strtolower($name);
        if (!array_key_exists($name, $this->headers)) {
            return null;
        }

        return $this->headers[$name];
    }

    /**
     * Gets the headers specified for the response.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Gets the data (payload) for the response.
     *
     * @return string the response payload.
     */
    public function getData()
    {
        return $this->data;
    }
}
