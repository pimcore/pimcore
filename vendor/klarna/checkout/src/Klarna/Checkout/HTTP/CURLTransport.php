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
 * File containing the Klarna_Checkout_HTTP_CURLTransport class
 *
 * PHP version 5.3
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
 * Klarna HTTP transport implementation for cURL
 *
 * @category   Payment
 * @package    Payment_Klarna
 * @subpackage HTTP
 * @author     Klarna <support@klarna.com>
 * @copyright  2012 Klarna AB
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link       http://developers.klarna.com/
 */
class Klarna_Checkout_HTTP_CURLTransport
    implements Klarna_Checkout_HTTP_TransportInterface
{
    const DEFAULT_TIMEOUT = 10;

    /**
     * @var Klarna_Checkout_HTTP_CURLFactory
     */
    protected $curl;

    /**
     * Number of seconds before the connection times out.
     *
     * @var int
     */
    protected $timeout;

    /**
     * cURL Options
     *
     * @var array
     */
    protected $options;

    /**
     * Initializes a new instance of the HTTP cURL class.
     *
     * @param Klarna_Checkout_HTTP_CURLFactory $curl factory to for curl handles
     */
    public function __construct(Klarna_Checkout_HTTP_CURLFactory $curl)
    {
        $this->curl = $curl;
        $this->timeout = self::DEFAULT_TIMEOUT;
        $this->options = array();
    }

    /**
     * Set specific cURL options.
     *
     * @param int   $option cURL option constant
     * @param mixed $value  cURL option value
     *
     * @return void
     */
    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
    }

    /**
     * Sets the number of seconds until a connection times out.
     *
     * @param int $timeout number of seconds
     *
     * @return void
     */
    public function setTimeout($timeout)
    {
        $this->timeout = intval($timeout);
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
     * @throws RuntimeException                Thrown if a cURL handle cannot
     *                                         be initialized.
     * @throws Klarna_Checkout_ConnectionErrorException Thrown for unspecified
     *                                                  network or hardware issues.
     * @return Klarna_Checkout_HTTP_Response
     */
    public function send(Klarna_Checkout_HTTP_Request $request)
    {
        $curl = $this->curl->handle();
        if ($curl === false) {
            throw new RuntimeException(
                'Failed to initialize a HTTP handle.'
            );
        }

        $url = $request->getURL();
        $curl->setOption(CURLOPT_URL, $url);

        $method = $request->getMethod();
        if ($method === 'POST') {
            $curl->setOption(CURLOPT_POST, true);
            $curl->setOption(CURLOPT_POSTFIELDS, $request->getData());
        }

        // Convert headers to cURL format.
        $requestHeaders = array();
        foreach ($request->getHeaders() as $key => $value) {
            $requestHeaders[] = $key . ': ' . $value;
        }

        $curl->setOption(CURLOPT_HTTPHEADER, $requestHeaders);

        $curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, $this->timeout);
        $curl->setOption(CURLOPT_TIMEOUT, $this->timeout);

        $curlHeaders = new Klarna_Checkout_HTTP_CURLHeaders();
        $curl->setOption(
            CURLOPT_HEADERFUNCTION,
            array(&$curlHeaders, 'processHeader')
        );

        $curl->setOption(CURLOPT_SSL_VERIFYHOST, 2);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, true);

        // Override specific set options
        foreach ($this->options as $option => $value) {
            $curl->setOption($option, $value);
        }

        $payload = $curl->execute();
        $info = $curl->getInfo();
        $error = $curl->getError();

        $curl->close();

        /*
         * A failure occurred if:
         * payload is false (e.g. HTTP timeout?).
         * info is false, then it has no HTTP status code.
         */
        if ($payload === false || $info === false) {
            throw new Klarna_Checkout_ConnectionErrorException(
                "Connection to '{$url}' failed: {$error}"
            );
        }

        $headers = $curlHeaders->getHeaders();

        // Convert Content-Type into a normal header
        $headers['Content-Type'] = $info['content_type'];

        $response = new Klarna_Checkout_HTTP_Response(
            $request, $headers, intval($info['http_code']), strval($payload)
        );

        return $response;
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
        return new Klarna_Checkout_HTTP_Request($url);
    }
}
