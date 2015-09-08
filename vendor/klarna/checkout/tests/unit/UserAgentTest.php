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
 * File containing the Klarna_Checkout_UserAgent unittest
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
 * unittest of the UserAgent class
 *
 * @category  Payment
 * @package   Klarna_Checkout
 * @author    Rickard D. <rickard.dybeck@klarna.com>
 * @author    Christer G. <christer.gustavsson@klarna.com>
 * @copyright 2012 Klarna AB
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link      http://developers.klarna.com/
 */
class Klarna_Checkout_UserAgentTest extends PHPUnit_Framework_TestCase
{
    /**
     * Object under test
     *
     * @var Klarna_Checkout_UserAgent
     */
    public $ua;

    /**
     * Setup a useragent for test
     *
     * @return void
     */
    public function setUp()
    {
        $this->ua = new Klarna_Checkout_UserAgent;
    }

    /**
     * Test that the default keys are in the string
     *
     * @return void
     */
    public function testBasic()
    {
        $uastring = (string)$this->ua;
        $this->assertRegExp('/.*OS\\/[^\\ ]+_[^\\ ]+.*/', $uastring);
        $this->assertRegExp('/.*Library\\/[^\\ ]+_[^\\ ]+.*/', $uastring);
        $this->assertRegExp('/.*Language\\/[^\\ ]+_[^\\ ]+.*/', $uastring);
    }

    /**
     * Test that a custom field can be added
     *
     * @return void
     */
    public function testAnotherField()
    {
        $this->ua->addField(
            'Module',
            array(
                'name' => 'Magento',
                'version' => '5.0',
                'options' => array(
                    'LanguagePack/7',
                    'JsLib/2.0'
                )
            )
        );
        $uastring = (string)$this->ua;
        $this->assertRegExp(
            '/.*Module\\/Magento_5.0 \\(LanguagePack\\/7 ; JsLib\\/2.0\\).*/',
            $uastring
        );
    }

    /**
     * Test that a custom field can be added
     *
     * @return void
     */
    public function testCantRedefine()
    {
        $this->setExpectedException(
            'Klarna_Checkout_Exception',
            "Unable to redefine field OS"
        );
        $this->ua->addField(
            'OS',
            array(
                'name' => 'Haiku',
                'version' => '1.0-alpha3'
            )
        );
    }
}
