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
 * File containing the Klarna_Checkout_Digest unittest
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
 * UnitTest for the Digester class
 *
 * @category  Payment
 * @package   Klarna_Checkout
 * @author    Rickard D. <rickard.dybeck@klarna.com>
 * @author    Christer G. <christer.gustavsson@klarna.com>
 * @copyright 2012 Klarna AB
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link      http://developers.klarna.com/
 */
class Klarna_Checkout_DigestTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test to create a Digest hash of a json encoded hash.
     *
     * @return void
     */
    public function testCreateDigest()
    {
        $expected = 'MO/6KvzsY2y+F+/SexH7Hyg16gFpsPDx5A2PtLZd0Zs=';

        $digester = new Klarna_Checkout_Digest;

        $json = array(
            'eid' => 1245,
            'goods_list' => array(
                array(
                    'artno' => 'id_1',
                    'name' => 'product',
                    'price' => 12345,
                    'vat' => 25,
                    'qty' => 1
                )
            ),
            'currency' => 'SEK',
            'country' => 'SWE',
            'language' => 'SV'
        );

        $this->assertEquals(
            $expected,
            $digester->create(json_encode($json).'mySecret'),
            'JsonEncoded Hash Digest.'
        );
    }

}
