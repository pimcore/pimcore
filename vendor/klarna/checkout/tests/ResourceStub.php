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
 * File containing the Klarna_Checkout_ResourceStub class
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
 * Stub implementation of the Klarna_Checkout_ResourceInterface
 *
 * @category  Payment
 * @package   Klarna_Checkout
 * @author    Rickard D. <rickard.dybeck@klarna.com>
 * @author    Christer G. <christer.gustavsson@klarna.com>
 * @copyright 2012 Klarna AB
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link      http://developers.klarna.com/
 */
class Klarna_Checkout_ResourceStub implements Klarna_Checkout_ResourceInterface
{

    public $location;
    public $data = array();

    /**
     * Get the URL of the resource
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set the URL of the resource
     *
     * @param string $location URL of the resource
     *
     * @return void
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * Return content type of the resource
     *
     * @return string Content type
     */
    public function getContentType()
    {
        return 'klarna-stubbed-order+json';
    }

    /**
     * Replare resource data
     *
     * @param array $data data
     *
     * @return void
     */
    public function parse(array $data)
    {
        $this->data = $data;

    }

    /**
     * Basic representation of the object
     *
     * @return array data
     */
    public function marshal()
    {
        return $this->data;
    }
}
