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
 * File containing the Klarna_Checkout_BasicConnector class
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
 * Basic implementation of the connector interface
 *
 * @category  Payment
 * @package   Klarna_Checkout
 * @author    Rickard D. <rickard.dybeck@klarna.com>
 * @author    Christer G. <christer.gustavsson@klarna.com>
 * @author    David K. <david.keijser@klarna.com>
 * @copyright 2012 Klarna AB
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link      http://developers.klarna.com/
 */
class Klarna_Checkout_BasicConnector implements Klarna_Checkout_ConnectorInterface
{

    /**
     * Klarna_Checkout_HTTP_TransportInterface Implementation
     *
     * @var Klarna_Checkout_HTTP_TransportInterface
     */
    protected $http;

    /**
     * Digester class
     *
     * @var Klarna_Checkout_Digest
     */
    protected $digester;

    /**
     * Shared Secret used to sign requests
     *
     * @var string
     */
    private $_secret;

    /**
     * Create a new Checkout Connector
     *
     * @param Klarna_Checkout_HTTP_TransportInterface $http     transport
     * @param Klarna_Checkout_Digest                  $digester Digest Generator
     * @param string                                  $secret   shared secret
     */
    public function __construct(
        Klarna_Checkout_HTTP_TransportInterface $http,
        Klarna_Checkout_Digest $digester,
        $secret
    ) {
        $this->http = $http;
        $this->digester = $digester;
        $this->_secret = $secret;
    }

    /**
     * Create the user agent identifier to use
     *
     * @return Klarna_Checkout_UserAgent
     */
    protected function userAgent()
    {
        return new Klarna_Checkout_UserAgent();
    }

    /**
     * Applying the method on the specific resource
     *
     * @param string                            $method   Http methods
     * @param Klarna_Checkout_ResourceInterface $resource resource
     * @param array                             $options  Options
     *
     * @return mixed
     */
    public function apply(
        $method,
        Klarna_Checkout_ResourceInterface $resource,
        array $options = null
    ) {
        switch ($method) {
        case 'GET':
        case 'POST':
            return $this->handle($method, $resource, $options, array());
        default:
            throw new InvalidArgumentException(
                "{$method} is not a valid HTTP method"
            );
        }
    }

    /**
     * Gets the underlying transport object
     *
     * @return Klarna_Checkout_HTTP_TransportInterface Transport object
     */
    public function getTransport()
    {
        return $this->http;
    }

    /**
     * Set content (headers, payload) on a request
     *
     * @param Klarna_Checkout_ResourceInterface $resource Klarna Checkout Resource
     * @param string                            $method   HTTP Method
     * @param string                            $payload  Payload to send with the
     *                                                    request
     * @param string                            $url      URL for request
     *
     * @return Klarna_Checkout_HTTP_Request
     */
    protected function createRequest(
        Klarna_Checkout_ResourceInterface $resource,
        $method,
        $payload,
        $url
    ) {
        // Generate the digest string
        $digest = $this->digester->create($payload . $this->_secret);

        $request = $this->http->createRequest($url);

        $request->setMethod($method);

        // Set HTTP Headers
        $request->setHeader('User-Agent', (string)$this->userAgent());
        $request->setHeader('Authorization', "Klarna {$digest}");
        $request->setHeader('Accept', $resource->getContentType());
        if (strlen($payload) > 0) {
            $request->setHeader('Content-Type', $resource->getContentType());
            $request->setData($payload);
        }

        return $request;
    }

    /**
     * Get the url to use
     *
     * @param Klarna_Checkout_ResourceInterface $resource resource
     * @param array                             $options  Options
     *
     * @return string Url to use for HTTP requests
     */
    protected function getUrl(
        Klarna_Checkout_ResourceInterface $resource, array $options
    ) {
        if (array_key_exists('url', $options)) {
            return $options['url'];
        }

        return $resource->getLocation();
    }

    /**
     * Get the data to use
     *
     * @param Klarna_Checkout_ResourceInterface $resource resource
     * @param array                             $options  Options
     *
     * @return array data to use for HTTP requests
     */
    protected function getData(
        Klarna_Checkout_ResourceInterface $resource, array $options
    ) {
        if (array_key_exists('data', $options)) {
            return $options['data'];
        }

        return $resource->marshal();
    }

    /**
     * Throw an exception if the server responds with an error code.
     *
     * @param Klarna_Checkout_HTTP_Response $result HTTP Response object
     *
     * @throws Klarna_Checkout_HTTP_Status_Exception
     * @return void
     */
    protected function verifyResponse(Klarna_Checkout_HTTP_Response $result)
    {
        // Error Status Code recieved. Throw an exception.
        if ($result->getStatus() >= 400 && $result->getStatus() <= 599) {
            throw new Klarna_Checkout_ConnectorException(
                $result->getData(), $result->getStatus()
            );
        }
    }

    /**
     * Act upon the status of a response
     *
     * @param Klarna_Checkout_HTTP_Response     $result   response from server
     * @param Klarna_Checkout_ResourceInterface $resource associated resource
     * @param array                             $visited  list of visited locations
     *
     * @return Klarna_Checkout_HTTP_Response
     */
    protected function handleResponse(
        Klarna_Checkout_HTTP_Response $result,
        Klarna_Checkout_ResourceInterface $resource,
        array $visited = array()
    ) {
        // Check if we got an Error status code back
        $this->verifyResponse($result);

        $url = $result->getHeader('Location');
        switch ($result->getStatus()) {
        case 301:
            // Update location and fallthrough
            $resource->setLocation($url);
        case 302:
            // Don't fallthrough for other than GET
            if ($result->getRequest()->getMethod() !== 'GET') {
                break;
            }
        case 303:
            // Detect eternal loops
            if (in_array($url, $visited)) {
                throw new Klarna_Checkout_ConnectorException(
                    'Infinite redirect loop detected.',
                    -1
                );
            }
            $visited[] = $url;
            // Follow redirect
            return $this->handle(
                'GET',
                $resource,
                array('url' => $url),
                $visited
            );
        case 201:
            // Update Location
            $resource->setLocation($url);
            break;
        case 200:
            // Update Data on resource
            $json = json_decode($result->getData(), true);
            if ($json === null) {
                throw new Klarna_Checkout_ConnectorException(
                    'Bad format on response content.',
                    -2
                );
            }
            $resource->parse($json);
        }

        return $result;
    }

    /**
     * Perform a HTTP Call on the supplied resource using the wanted method.
     *
     * @param string                            $method   HTTP Method
     * @param Klarna_Checkout_ResourceInterface $resource Klarna Order
     * @param array                             $options  Options
     * @param array                             $visited  list of visited locations
     *
     * @throws Klarna_Checkout_Exception if 4xx or 5xx response code.
     * @return Result object containing status code and payload
     */
    protected function handle(
        $method,
        Klarna_Checkout_ResourceInterface $resource,
        array $options = null,
        array $visited = array()
    ) {
        if ($options === null) {
            $options = array();
        }

        // Define the target URL
        $url = $this->getUrl($resource, $options);

        // Set a payload if it is a POST call.
        $payload = '';
        if ($method === 'POST') {
            $payload = json_encode($this->getData($resource, $options));
        }

        // Create a HTTP Request object
        $request = $this->createRequest($resource, $method, $payload, $url);

        // Execute the HTTP Request
        $result = $this->http->send($request);

        // Handle statuses appropriately.
        return $this->handleResponse($result, $resource, $visited);
    }
}
