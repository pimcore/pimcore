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
 * File containing the Klarna_Checkout_HTTP_TransportInterface interface
 *
 * PHP version 5.3
 *
 * @category   Payment
 * @package    Payment_Klarna
 * @subpackage Interfaces
 * @author     Klarna <support@klarna.com>
 * @copyright  2012 Klarna AB
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link       http://developers.klarna.com/
 */

/**
 * Interface for a Klarna HTTP Transport object
 *
 * @category   Payment
 * @package    Payment_Klarna
 * @subpackage Interfaces
 * @author     Klarna <support@klarna.com>
 * @copyright  2012 Klarna AB
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link       http://developers.klarna.com/
 */
interface Klarna_Checkout_HTTP_TransportInterface
{
    /**
     * Specifies the number of seconds before the connection times out.
     *
     * @param int $timeout number of seconds
     *
     * @throws InvalidArgumentException If the specified argument
     *                                  is not of type integer.
     * @return void
     */
    public function setTimeout($timeout);

    /**
     * Gets the number of seconds before the connection times out.
     *
     * @return int timeout in number of seconds
     */
    public function getTimeout();

    /**
     * Performs a HTTP request.
     *
     * @param Klarna_Checkout_HTTP_Request $request the HTTP request to send.
     *
     * @throws Klarna_Checkout_ConnectionErrorException Thrown for unspecified
     *                                                  network or hardware issues.
     * @return Klarna_Checkout_HTTP_Response
     */
    public function send(Klarna_Checkout_HTTP_Request $request);

    /**
     * Creates a HTTP request object.
     *
     * @param string $url the request URL.
     *
     * @throws InvalidArgumentException If the specified argument
     *                                  is not of type string.
     * @return Klarna_Checkout_HTTP_Request
     */
    public function createRequest($url);
}
