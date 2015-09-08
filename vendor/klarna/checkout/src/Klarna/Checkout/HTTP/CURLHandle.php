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
 * File containing the Klarna_Checkout_HTTP_CURLHeaders class
 *
 * PHP version 5.3
 *
 * @category   Payment
 * @package    Payment_Klarna
 * @subpackage HTTP
 * @author     Klarna <support@klarna.com>
 * @copyright  2012 Klarna AB
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link       http://developers.klarna.com/
 */

/**
 * A wrapper around the cURL functions
 *
 * @category   Payment
 * @package    Payment_Klarna
 * @subpackage HTTP
 * @author     David K. <david.keijser@klarna.com>
 * @copyright  2012 Klarna AB
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link       http://developers.klarna.com/
 */
class Klarna_Checkout_HTTP_CURLHandle
    implements Klarna_Checkout_HTTP_CURLHandleInterface
{
    /**
     * cURL handle
     * @var resource
     */
    private $_handle = null;

    /**
     * Create a new cURL handle
     */
    public function __construct()
    {
        if (!extension_loaded('curl')) {
            throw new RuntimeException(
                'cURL extension is requred.'
            );
        }
        $this->_handle = curl_init();
    }

    /**
     * Set an option for the cURL transfer
     *
     * @param int   $name  option the set
     * @param mixed $value the value to be set on option
     *
     * @return void
     */
    public function setOption($name, $value)
    {
        curl_setopt($this->_handle, $name, $value);
    }

    /**
     * Perform the cURL session
     *
     * @return mixed response
     */
    public function execute()
    {
        return curl_exec($this->_handle);
    }

    /**
     * Get information regarding this transfer
     *
     * @return array
     */
    public function getInfo()
    {
        return curl_getinfo($this->_handle);
    }

    /**
     * Get error message regarding this transfer
     *
     * @return string Error message
     */
    public function getError()
    {
        return curl_error($this->_handle);
    }

    /**
     * Close the cURL session
     *
     * @return void
     */
    public function close()
    {
        curl_close($this->_handle);
    }
}
