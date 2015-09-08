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
 * File containing the Klarna_Checkout_BasicConnector (POST) unittest
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
 * POST UnitTests for the Basic Connector class
 *
 * @category  Payment
 * @package   Klarna_Checkout
 * @author    Rickard D. <rickard.dybeck@klarna.com>
 * @author    Christer G. <christer.gustavsson@klarna.com>
 * @copyright 2012 Klarna AB
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link      http://developers.klarna.com/
 */
class Klarna_Checkout_BasicConnectorPostTest extends PHPUnit_Framework_TestCase
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
     * Test apply POST with a 200 code
     *
     * @return void
     */
    public function testApplyPost200()
    {
        $curl = new Klarna_Checkout_HTTP_TransportStub;

        $payload = '{"flobadob":["bobcat","wookie"]}';
        $data = array(
            'code' => 200,
            'headers' => array(),
            'payload' => $payload
        );
        $curl->addResponse($data);

        $this->orderStub->parse(json_decode($payload, true));

        $expected = 'stnaeu\eu2341aoaaoae==';

        $this->digest->expects($this->once())
            ->method('create')
            ->with("{$payload}aboogie")
            ->will($this->returnValue($expected));

        $object = new Klarna_Checkout_BasicConnector(
            $curl,
            $this->digest,
            'aboogie'
        );
        $result = $object->apply('POST', $this->orderStub);

        $this->assertEquals($payload, $result->getData(), 'Response payload');
        $this->assertEquals(
            "Klarna {$expected}",
            $curl->request->getHeader('Authorization'),
            'Header'
        );

        $this->assertEquals(
            json_decode($payload, true),
            $this->orderStub->marshal(),
            'Content'
        );

        $this->assertEquals(
            $this->orderStub->getContentType(),
            $curl->request->getHeader('Accept'),
            'Accept Content Type'
        );
    }

    /**
     * Test so apply with a 200 code but an invalid json response throws an
     * exception.
     *
     * @return void
     */
    public function testApplyPost200InvalidJSON()
    {
        $this->setExpectedException('Klarna_Checkout_ConnectorException');

        $curl = new Klarna_Checkout_HTTP_TransportStub;

        $payload = '{"flobadob"}';
        $data = array(
            'code' => 200,
            'headers' => array(),
            'payload' => $payload
        );
        $curl->addResponse($data);

        $object = new Klarna_Checkout_BasicConnector(
            $curl,
            $this->digest,
            'secret'
        );
        $object->apply('POST', $this->orderStub);
    }

    /**
     * Test with url option, to ensure it gets picked up
     *
     * @return void
     */
    public function testApplyPostWithUrlInOptions()
    {
        $options = array('url' => 'localhost');

        $curl = new Klarna_Checkout_HTTP_TransportStub;

        $payload = '{"flobadob":["bobcat","wookie"]}';

        $data = array(
            'code' => 200,
            'headers' => array(),
            'payload' => $payload
        );
        $curl->addResponse($data);

        $this->orderStub->parse(json_decode($payload, true));

        $expected = 'stnaeu\eu2341aoaaoae==';

        $this->digest->expects($this->once())
            ->method('create')
            ->with("{$payload}aboogie")
            ->will($this->returnValue($expected));

        $object = new Klarna_Checkout_BasicConnector(
            $curl,
            $this->digest,
            'aboogie'
        );
        $result = $object->apply('POST', $this->orderStub, $options);

        $request = $result->getRequest();

        $this->assertEquals($options['url'], $request->getUrl(), 'Url Option');
    }

    /**
     * Test with a redirect (303) to a OK (200)
     *
     * @return void
     */
    public function testApplyPost303ConvertedToGet()
    {
        $options = array('url' => 'localhost');

        $curl = new Klarna_Checkout_HTTP_TransportStub;

        $payload = '{"flobadob":["bobcat","wookie"]}';
        $redirect = 'not localhost';
        $data = array(
            array(
                'code' => 200,
                'headers' => array(),
                'payload' => $payload
            ),
            array(
                'code' => 303,
                'headers' => array('Location' => $redirect),
                'payload' => $payload
            )
        );
        foreach ($data as $response) {
            $curl->addResponse($response);
        }

        $this->orderStub->parse(json_decode($payload, true));

        $expected = 'stnaeu\eu2341aoaaoae==';

        $this->digest->expects($this->at(0))
            ->method('create')
            ->with("{$payload}aboogie")
            ->will($this->returnValue($expected));

        $this->digest->expects($this->at(1))
            ->method('create')
            ->with("aboogie")
            ->will($this->returnValue($expected));

        $object = new Klarna_Checkout_BasicConnector(
            $curl,
            $this->digest,
            'aboogie'
        );
        $result = $object->apply('POST', $this->orderStub, $options);

        $request = $result->getRequest();

        $this->assertEquals('GET', $request->getMethod(), 'Method');
    }

    /**
     * Test with a redirect (303) to a Forbidden (503) to ensure exception is
     * thrown.
     *
     * @return void
     */
    public function testApplyPost301to503()
    {
        $this->setExpectedException(
            'Klarna_Checkout_ConnectorException', 'Forbidden', 503
        );

        $options = array('url' => 'localhost');

        $curl = new Klarna_Checkout_HTTP_TransportStub;

        $payload = 'Forbidden';
        $redirect = 'not localhost';
        $data = array(
            array(
                'code' => 503,
                'headers' => array(),
                'payload' => $payload
            ),
            array(
                'code' => 303,
                'headers' => array('Location' => $redirect),
                'payload' => ""
            )
        );
        foreach ($data as $response) {
            $curl->addResponse($response);
        }

        $object = new Klarna_Checkout_BasicConnector(
            $curl,
            $this->digest,
            'aboogie'
        );

        $result = null;
        try {
            $result = $object->apply('POST', $this->orderStub, $options);
        } catch (Exception $e) {
            $request = $curl->request;
            $this->assertEquals($redirect, $request->getUrl(), 'Url Option');
            throw $e;
        }
    }

    /**
     * Test so 201 statuscode will update the location on the resource.
     *
     * @return void
     */
    public function testApplyPost201UpdatedLocation()
    {
        $curl = new Klarna_Checkout_HTTP_TransportStub;

        $payload = '{"flobadob":["bobcat","wookie"]}';
        $location = 'not localhost';

        $data = array(
            'code' => 201,
            'headers' => array('Location' => $location),
            'payload' => $payload
        );
        $curl->addResponse($data);

        $this->orderStub->parse(json_decode($payload, true));

        $expected = 'stnaeu\eu2341aoaaoae==';

        $this->digest->expects($this->once())
            ->method('create')
            ->with("{$payload}aboogie")
            ->will($this->returnValue($expected));

        $object = new Klarna_Checkout_BasicConnector(
            $curl,
            $this->digest,
            'aboogie'
        );

        $this->assertNull($this->orderStub->getLocation(), 'Original Location');

        $result = $object->apply('POST', $this->orderStub);

        $this->assertEquals($payload, $result->getData(), 'Response payload');
        $this->assertEquals(
            "Klarna {$expected}",
            $curl->request->getHeader('Authorization'),
            'Header'
        );

        $this->assertEquals(
            $location,
            $this->orderStub->getLocation(),
            'Location should have beet updated'
        );

        $this->assertEquals(
            json_decode($payload, true),
            $this->orderStub->marshal(),
            'The data on the resource should not be updated!'
        );

        $this->assertEquals(
            $this->orderStub->getContentType(),
            $curl->request->getHeader('Accept'),
            'Accept Content Type'
        );
    }

    /**
     * Ensue that a 301 redirect is not followed by POST.
     *
     * @return void
     */
    public function testApplyPostDoesntFollowRedirect301()
    {
        $options = array('url' => 'localhost');

        $curl = new Klarna_Checkout_HTTP_TransportStub;

        $payload = 'Forbidden';
        $redirect = 'not localhost';

        $data = array(
            array(
                'code' => 503,
                'headers' => array(),
                'payload' => $payload
            ),
            array(
                'code' => 301,
                'headers' => array('Location' => $redirect),
                'payload' => ""
            )
        );
        foreach ($data as $response) {
            $curl->addResponse($response);
        }

        $object = new Klarna_Checkout_BasicConnector(
            $curl,
            $this->digest,
            'aboogie'
        );

        $result = null;
        try {
            $result = $object->apply('POST', $this->orderStub, $options);
        } catch (Exception $e) {
            throw $e;
        }
        $request = $curl->request;
        $this->assertNotEquals($redirect, $request->getUrl(), 'Url Option');
    }
}
