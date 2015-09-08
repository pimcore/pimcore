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
 * File containing the Klarna_Checkout_Order unittest
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
 * UnitTest for the Order class, interactions with connector
 *
 * @category  Payment
 * @package   Klarna_Checkout
 * @author    Majid G. <majid.garmaroudi@klarna.com>
 * @author    David K. <david.keijser@klarna.com>
 * @copyright 2012 Klarna AB
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link      http://developers.klarna.com/
 */
class Klarna_Checkout_OrderWithConnectorTest extends PHPUnit_Framework_TestCase
{
    /**
     * Order Instance
     *
     * @var Klarna_Checkout_Order
     */
    protected $order;

    /**
     * Connector Instance
     *
     * @var Klarna_Checkout_ConnectorStub
     */
    protected $connector;

    /**
     * Setup function
     *
     * @return void
     */
    public function setUp()
    {
        $this->connector = new Klarna_Checkout_ConnectorStub();
        $this->order = new Klarna_Checkout_Order($this->connector);
    }

    /**
     * Test that create works as intended
     *
     * @return void
     */
    public function testCreate()
    {
        $location = 'http://stub';
        $this->connector->location = $location;
        $data = array("foo" => "boo");
        $order = new Klarna_Checkout_Order($this->connector);
        $order->create($data);

        $this->assertEquals("POST", $this->connector->applied["method"]);
        $this->assertEquals($order, $this->connector->applied["resource"]);
        $this->assertEquals($location, $order->getLocation());
        $this->assertArrayHasKey(
            "url",
            $this->connector->applied["options"]
        );
        $this->assertArrayHasKey(
            "data",
            $this->connector->applied["options"]
        );
        $this->assertEquals(
            $this->connector->applied['options']['data']['foo'],
            'boo'
        );
    }

    /**
     * Test that fetch works as intended
     *
     * @return void
     */
    public function testFetch()
    {
        $this->order->setLocation("http://klarna.com/foo/bar/15");
        $url = $this->order->getLocation();
        $this->order->fetch();

        $this->assertEquals("GET", $this->connector->applied["method"]);
        $this->assertEquals($this->order, $this->connector->applied["resource"]);
        $this->assertArrayHasKey("url", $this->connector->applied["options"]);
        $this->assertEquals($url, $this->connector->applied["options"]["url"]);
    }

    /**
     * Test that update works as intended
     *
     * @return void
     */
    public function testUpdate()
    {
        $this->order->setLocation("http://klarna.com/foo/bar/15");
        $uri = $this->order->getLocation();
        $this->order->update(
            array(
                'foo' => 'boo'
            )
        );

        $this->assertEquals("POST", $this->connector->applied["method"]);
        $this->assertEquals($this->order, $this->connector->applied["resource"]);
        $this->assertArrayHasKey("url", $this->connector->applied["options"]);
        $this->assertEquals($uri, $this->connector->applied["options"]["url"]);
    }

    /**
     * Test that entry point (Base URL) can be changed
     *
     * @return void
     */
    public function testCreateAlternativeEntryPoint()
    {
        $data = array("foo" => "boo");
        $baseUri = "https://checkout.klarna.com/beta/checkout/orders";
        Klarna_Checkout_Order::$baseUri = $baseUri;
        $order = new Klarna_Checkout_Order($this->connector);
        $order->create($data);

        $this->assertEquals($baseUri, $this->connector->applied["options"]["url"]);
    }

}
