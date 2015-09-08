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
 * File containing the Klarna_Checkout_HTTP_TransportStub class
 *
 * PHP version 5.2
 *
 * @category   Payment
 * @package    Payment_Klarna
 * @subpackage HTTP
 * @author     Klarna <support@klarna.com>
 * @copyright  2012 Klarna AB AB
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link       http://developers.klarna.com/
 */

/**
 * Stub implemenation of the Klarna HTTP Transport
 *
 * @category   Payment
 * @package    Payment_Klarna
 * @subpackage HTTP
 * @author     Klarna <support@klarna.com>
 * @copyright  2012 Klarna AB
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link       http://developers.klarna.com/
 */
class Klarna_Checkout_HTTP_TransportStub
    implements Klarna_Checkout_HTTP_TransportInterface
{
    public $code;
    public $headers;
    public $payload;
    public $request;
    public $response = array();

    /**
     * Set the response code
     *
     * @param int $code Response code
     *
     * @return void
     */
    public function setResponseCode($code)
    {
        $this->code = intval($code);
    }

    /**
     * Set the headers to simulate
     *
     * @param array $headers array of header data
     *
     * @return void
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * Set the simulated payload
     *
     * @param string $payload a JSON in string format
     *
     * @return void
     */
    public function setPayload($payload)
    {
        $this->payload = strval($payload);
    }

    /**
     * Insert a response to the top of the response array.
     *
     * @param array $response response array to add
     *
     * @return void
     */
    public function addResponse($response)
    {
        array_unshift($this->response, $response);
    }

    /**
     * Specifies the number of seconds before the connection times out.
     *
     * @param int $timeout number of seconds
     *
     * @throws InvalidArgumentException If the specified argument
     *                                  is not of type integer.
     * @return void
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * Gets the number of seconds before the connection times out.
     *
     * @return int timeout in number of seconds
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Performs a HTTP request.
     *
     * @param Klarna_Checkout_HTTP_Request $request the HTTP request to send.
     *
     * @throws Klarna_Checkout_ConnectionErrorException Thrown for unspecified
     *                                                  network or hardware issues.
     * @return Klarna_Checkout_HTTP_Response
     */
    public function send(Klarna_Checkout_HTTP_Request $request)
    {
        $data = $this->response[0];

        if (count($this->response) > 1) {
            $data = array_shift($this->response);
        }

        return new Klarna_Checkout_HTTP_Response(
            $request, $data['headers'], $data['code'], $data['payload']
        );
    }

    /**
     * Creates a HTTP request object.
     *
     * @param string $url the request URL.
     *
     * @throws InvalidArgumentException If the specified argument
     *                                  is not of type string.
     * @return Klarna_Checkout_HTTP_Request
     */
    public function createRequest($url)
    {
        $this->request = new Klarna_Checkout_HTTP_Request($url);
        return $this->request;
    }
}
