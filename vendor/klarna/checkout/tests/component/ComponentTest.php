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
 * File containing the Klarna_Checkout_Component Component Tests
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
 * Component Tests for the Klarna Checkout
 *
 * @category  Payment
 * @package   Klarna_Checkout
 * @author    Rickard D. <rickard.dybeck@klarna.com>
 * @author    Christer G. <christer.gustavsson@klarna.com>
 * @copyright 2012 Klarna AB
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link      http://developers.klarna.com/
 */
class Klarna_Checkout_Component extends PHPUnit_Framework_TestCase
{


    /**
     * Set up test
     *
     * @return void
     */
    public function setUp()
    {
        global $_SESSION;

        $_SESSION = array();

        $factory = new Klarna_Checkout_CurlFactoryStub();

        Klarna_Checkout_Order::$baseUri = 'test1';

        $factory->addHandle('test1', 201, array('Location: test2'));
        $factory->addHandle('test2', 200, array());

        $this->connector = new Klarna_Checkout_BasicConnector(
            new Klarna_Checkout_HTTP_CURLTransport($factory),
            new Klarna_Checkout_Digest,
            'sharedSecret'
        );
    }

    /**
     * Sample component test
     *
     * @group component
     *
     * @return void
     */
    public function testShow()
    {
        // Start new session
        $banana = array(
            'type' => 'physical',
            'reference' => 'BANAN01',
            'name' => 'Bananana',
            'unit_price' => 450,
            'discount_rate' => 0,
            'tax_rate' => 2500
        );

        $shipping = array(
            'type' => 'shipping_fee',
            'reference' => 'SHIPPING',
            'name' => 'Shipping Fee',
            'unit_price' => 450,
            'discount_rate' => 0,
            'tax_rate' => 2500
        );

        $order = new Klarna_Checkout_Order($this->connector);
        $order->create(
            array(
                'purchase_country' => 'SE',
                'purchase_currency' => 'SEK',
                'locale' => 'sv-se',
                'merchant' => array(
                    'id' => 2,
                    'terms_uri' => 'http://localhost/terms.html',
                    'checkout_uri' => 'http://localhost/checkout.php',
                    'confirmation_uri' =>'http://localhost/thank-you.php',
                    'push_uri' => 'http://localhost/push.php'
                ),
                'cart' => array(
                    'total_price_including_tax' => 9000,
                    'items' => array(
                        $banana,
                        $shipping
                    )
                )
            )
        );

        $this->assertEquals($order->getLocation(), 'test2');
        $order->fetch();
    }

}
