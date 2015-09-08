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
 * File containing the PHPUnit Klarna_HTTP_CURLTest test case
 *
 * PHP version 5.2
 *
 * @category   Payment
 * @package    Payment_Klarna
 * @subpackage Unit_Tests
 * @author     Klarna <support@klarna.com>
 * @copyright  2012 Klarna AB
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link       http://developers.klarna.com/
 */

/**
 * PHPUnit test case for the HTTP CURL wrapper.
 *
 * @category   Payment
 * @package    Payment_Klarna
 * @subpackage Unit_Tests
 * @author     Klarna <support@klarna.com>
 * @copyright  2012 Klarna AB
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link       http://developers.klarna.com/
 */
class Klarna_Checkout_HTTP_CURLTransportTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Klarna_Checkout_HTTP_CURLTransport
     */
    protected $http;

    /**
     * Set up resources used for each test.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->factory = $this->getMock(
            'Klarna_Checkout_HTTP_CURLFactory',
            array('handle')
        );
        $this->http = new Klarna_Checkout_HTTP_CURLTransport($this->factory);
    }

    /**
     * Clears the resources used between tests.
     *
     * @return void
     */
    protected function tearDown()
    {
        $this->http = null;
    }

    /**
     * Make sure that the correct interface(s) are implemented.
     *
     * @return void
     */
    public function testInterface()
    {
        $this->assertInstanceOf(
            'Klarna_Checkout_HTTP_TransportInterface',
            $this->http
        );
    }

    /**
     * Make sure that the initial state is correct.
     *
     * @return void
     */
    public function testInit()
    {
        $this->assertEquals(
            Klarna_Checkout_HTTP_CURLTransport::DEFAULT_TIMEOUT,
            $this->http->getTimeout()
        );
    }

    /**
     * Test that the timout can be set
     *
     * @return void
     */
    public function testSetTimeout()
    {
        $timeout = 10;
        $this->http->setTimeout($timeout);
        $this->assertEquals($timeout, $this->http->getTimeout());
    }

    /**
     * Make sure that createRequest returns a usuable request object.
     *
     * @return void
     */
    public function testCreateRequest()
    {
        $request = $this->http->createRequest('url');
        $this->assertInstanceOf('Klarna_Checkout_HTTP_Request', $request);
        $this->assertEquals('url', $request->getURL());
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('', $request->getData());
        $this->assertEquals(0, count($request->getHeaders()));
    }

    /**
     * Make sure that if no curl handle could be created a exception is thrown
     *
     * @return void
     */
    public function testCurlFailureThrowsException()
    {
        $this->setExpectedException('RuntimeException');
        $this->factory->expects($this->once())
            ->method('handle')
            ->will($this->returnValue(false));
        $request = $this->http->createRequest('url');
        $this->http->send($request);
    }

    /**
     * Make sure that the correct cURL options is set
     *
     * @return void
     */
    public function testSendSetsOptions()
    {
        $url = 'maybe-localhost';
        $handle = new Klarna_Checkout_HTTP_CURLHandleStub;
        $handle->expectedURL = $url;
        $this->factory->expects($this->once())
            ->method('handle')
            ->will($this->returnValue($handle));
        $request = $this->http->createRequest($url);
        $this->http->send($request);
        $this->assertEquals($url, $handle->options[CURLOPT_URL]);
        $this->assertFalse(
            array_key_exists(CURLOPT_POST, $handle->options) &&
            $handle->options[CURLOPT_POST]
        );
        $this->assertTrue($handle->options[CURLOPT_RETURNTRANSFER]);
        $this->assertSame(
            $this->http->getTimeout(),
            $handle->options[CURLOPT_CONNECTTIMEOUT]
        );
        $this->assertSame(
            $this->http->getTimeout(),
            $handle->options[CURLOPT_TIMEOUT]
        );
    }

    /**
     * Make sure that the correct additional options for POST request are set
     *
     * @return void
     */
    public function testSendPostOptions()
    {
        $url = 'maybe-localhost';
        $handle = new Klarna_Checkout_HTTP_CURLHandleStub;
        $handle->expectedURL = $url;
        $this->factory->expects($this->once())
            ->method('handle')
            ->will($this->returnValue($handle));
        $request = $this->http->createRequest($url);
        $request->setMethod('POST');
        $this->http->send($request);
        $this->assertTrue(
            array_key_exists(CURLOPT_POST, $handle->options) &&
            $handle->options[CURLOPT_POST]
        );
        $this->assertSame(
            $this->http->getTimeout(),
            $handle->options[CURLOPT_CONNECTTIMEOUT]
        );
        $this->assertSame(
            $this->http->getTimeout(),
            $handle->options[CURLOPT_TIMEOUT]
        );
    }

    /**
     * Make sure that headers are sent in
     *
     * @return void
     */
    public function testSendHeaders()
    {
        $url = 'maybe-localhost';
        $headers = array(
            'Content-Type' => 'text/json'
        );
        $expectedHeaders = array(
            'Content-Type: text/json'
        );
        $handle = new Klarna_Checkout_HTTP_CURLHandleStub;
        $handle->expectedURL = $url;
        $this->factory->expects($this->once())
            ->method('handle')
            ->will($this->returnValue($handle));
        $request = $this->http->createRequest($url);
        foreach ($headers as $header => $value) {
            $request->setHeader($header, $value);
        }
        $this->http->send($request);
        $this->assertEquals(
            $expectedHeaders,
            $handle->options[CURLOPT_HTTPHEADER]
        );
    }

    /**
     * Make sure that a exception is thrown on cURL internal errors
     *
     * @return void
     */
    public function testExceptionOnFailure()
    {
        $this->setExpectedException('Klarna_Checkout_ConnectionErrorException');
        $url = 'maybe-localhost';
        $handle = new Klarna_Checkout_HTTP_CURLHandleStub;
        $handle->expectedURL = $url;
        $this->factory->expects($this->once())
            ->method('handle')
            ->will($this->returnValue($handle));
        $request = $this->http->createRequest($url);
        $handle->response = false;
        $this->http->send($request);
    }

    /**
     * Ensure that the error message from the cURL handle is picked up
     *
     * @return void
     */
    public function testExceptionMessageFromCURL()
    {
        $error = 'CURL_ERROR';

        $this->setExpectedException(
            'Klarna_Checkout_ConnectionErrorException', $error
        );

        $url = 'maybe-localhost';
        $handle = new Klarna_Checkout_HTTP_CURLHandleStub;
        $handle->error = $error;
        $handle->expectedURL = $url;
        $this->factory->expects($this->once())
            ->method('handle')
            ->will($this->returnValue($handle));
        $request = $this->http->createRequest($url);
        $handle->response = false;

        $this->http->send($request);
    }

    /**
     * Ensure that settings specific cURL options is possible
     *
     * @return void
     */
    public function testSetOption()
    {
        $url = 'maybe-localhost';
        $handle = new Klarna_Checkout_HTTP_CURLHandleStub;
        $handle->expectedURL = $url;
        $this->factory->expects($this->once())
            ->method('handle')
            ->will($this->returnValue($handle));
        $request = $this->http->createRequest($url);
        $request->setMethod('POST');

        $this->http->setOption(CURLOPT_VERBOSE, true);
        $this->http->setOption(CURLOPT_SSL_VERIFYPEER, false);

        $this->http->send($request);

        $this->assertTrue(
            $handle->options[CURLOPT_VERBOSE],
            'Verbosity should be enabled'
        );

        $this->assertFalse(
            $handle->options[CURLOPT_SSL_VERIFYPEER],
            'SSL verify peer should be disabled'
        );
    }
}
