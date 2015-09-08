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
 * File containing the Klarna_Checkout_BasicConnector unittest
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
 * General UnitTest for the Basic Connector class
 *
 * @category  Payment
 * @package   Klarna_Checkout
 * @author    Rickard D. <rickard.dybeck@klarna.com>
 * @author    Christer G. <christer.gustavsson@klarna.com>
 * @copyright 2012 Klarna AB
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link      http://developers.klarna.com/
 */
class Klarna_Checkout_BasicConnectorTest extends PHPUnit_Framework_TestCase
{

    /**
     * Stubbed Order Object
     *
     * @var Klarna_Checkout_ResourceInterface
     */
    public $orderStub;

    /**
     * Set up tests
     *
     * @return void
     */
    public function setUp()
    {
        $this->httpInterface = $this->getMock(
            'Klarna_Checkout_HTTP_TransportInterface'
        );

        $this->orderStub = new Klarna_Checkout_ResourceStub;

        $this->digest = $this->getMock(
            'Klarna_Checkout_Digest', array('create')
        );
    }

    /**
     * Test invalid method throws an exception.
     *
     * @return void
     */
    public function testApplyInvalidMethod()
    {
        $this->setExpectedException('InvalidArgumentException');


        $digest = $this->getMock('Klarna_Checkout_Digest');

        $object = new Klarna_Checkout_BasicConnector(
            $this->httpInterface, $digest, 'aboogie'
        );

        $object->apply('FLURB', $this->orderStub);
    }

    /**
     * Data Provider with HTTP Error Codes.
     *
     * @return array
     */
    public function responseErrorCodes()
    {
        return array(
            array(400, "Bad Request"),
            array(401, "Unauthorized"),
            array(402, "PaymentRequired"),
            array(403, "Forbidden"),
            array(404, "Not Found"),
            array(406, "HTTP Error"),
            array(409, "HTTP Error"),
            array(412, "HTTP Error"),
            array(415, "HTTP Error"),
            array(422, "HTTP Error"),
            array(428, "HTTP Error"),
            array(429, "HTTP Error"),
            array(500, "Internal Server Error"),
            array(502, "Service temporarily overloaded"),
            array(503, "Gateway timeout")
        );
    }

    /**
     * Test apply with GET method throws an exception if status code is an
     * error.
     *
     * @param int    $code    http error code
     * @param string $message error message
     *
     * @dataProvider responseErrorCodes
     * @return void
     */
    public function testApplyGetErrorCode($code, $message)
    {
        $this->setExpectedException(
            'Klarna_Checkout_ConnectorException', $message, $code
        );

        $curl = new Klarna_Checkout_HTTP_TransportStub;

        $data = array(
            'code' => $code,
            'headers' => array(),
            'payload' => $message
        );
        $curl->addResponse($data);

        $this->digest->expects($this->once())
            ->method('create')
            ->with('aboogie')
            ->will($this->returnValue('stnaeu\eu2341aoaaoae=='));

        $object = new Klarna_Checkout_BasicConnector(
            $curl,
            $this->digest,
            'aboogie'
        );
        $result = $object->apply('GET', $this->orderStub);

        $this->assertNotNull($result, 'Response Object');
    }

    /**
     * Test apply with POST method throws an exception if status code is an
     * error.
     *
     * @param int    $code    http error code
     * @param string $message error message
     *
     * @dataProvider responseErrorCodes
     * @return void
     */
    public function testApplyPostErrorCode($code, $message)
    {
        $this->setExpectedException(
            'Klarna_Checkout_ConnectorException', $message, $code
        );

        $curl = new Klarna_Checkout_HTTP_TransportStub;

        $data = array(
            'code' => $code,
            'headers' => array(),
            'payload' => $message
        );
        $curl->addResponse($data);

        $this->digest->expects($this->once())
            ->method('create')
            ->with('[]aboogie')
            ->will($this->returnValue('stnaeu\eu2341aoaaoae=='));

        $object = new Klarna_Checkout_BasicConnector(
            $curl,
            $this->digest,
            'aboogie'
        );
        $result = $object->apply('POST', $this->orderStub);

        $this->assertNotNull($result, 'Response Object');
    }

    /**
     * Testing getTransport always returns instance of
     * Klarna_Checkout_HTTP_TransportInterface
     *
     * @return void
     */
    public function testValidTransportType()
    {
        $curl = new Klarna_Checkout_HTTP_TransportStub;
        $object = new Klarna_Checkout_BasicConnector(
            $curl,
            $this->digest,
            'aboogie'
        );

        $this->assertInstanceOf(
            'Klarna_Checkout_HTTP_TransportInterface',
            $object->getTransport()
        );
    }
}
