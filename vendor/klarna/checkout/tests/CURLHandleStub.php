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
 * File containing the Klarna_Checkout_ConnectorStub class
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
 * Stub implementation of a cURL handle
 *
 * @category  Payment
 * @package   Klarna_Checkout
 * @author    David K. <david.keijser@klarna.com>
 * @copyright 2012 Klarna AB
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link      http://developers.klarna.com/
 */
class Klarna_Checkout_HTTP_CURLHandleStub
    implements Klarna_Checkout_HTTP_CURLHandleInterface
{
    /**
     * Options set
     *
     * @var array
     */
    public $options = array();

    /**
     * The expected URL
     *
     * @var string
     */
    public $expectedURL;

    /**
     * @var array
     */
    public $info = array(
        'http_code' => 200,
        'content_type' => 'text/json'
    );

    /**
     * @var string
     */
    public $error = 'Error message';

    /**
     * Response to return
     *
     * @var mixed
     */
    public $response = null;

    /**
     * @var boolean
     */
    public $closed = false;

    /**
     * @var array
     */
    public $headers = array();

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
        $this->options[$name] = $value;
    }

    /**
     * Perform the cURL session
     *
     * @return mixed response
     */
    public function execute()
    {
        if ($this->options[CURLOPT_URL] !== $this->expectedURL) {
            throw new Klarna_Checkout_Exception(
                "Unexpected url: " . $this->options[CURLOPT_URL], 998
            );
        }

        $response = $this->response;
        $response = is_callable($response)
            ? $response($this)
            : $response;

        foreach ($this->headers as $header) {
            call_user_func(
                $this->options[CURLOPT_HEADERFUNCTION],
                $this,
                $header
            );
        }
        return $response;
    }

    /**
     * Get information regarding this transfer
     *
     * @return array
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Get error message regarding this transfer
     *
     * @return string Error message
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Close the cURL session
     *
     * @return void
     */
    public function close()
    {
        $this->closed = true;
    }
}
