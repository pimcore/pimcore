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
 * File containing the Klarna_Checkout_CurlFactory class
 *
 * PHP version 5.2
 *
 * @category   Payment
 * @package    Payment_Klarna
 * @subpackage HTTP
 * @author     Klarna <support@klarna.com>
 * @copyright  2012 Klarna AB AB
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link       http://developers.klarna.com/
 */

/**
 * Stub implemenation of the Curl Factory
 *
 * @category   Payment
 * @package    Payment_Klarna
 * @subpackage HTTP
 * @author     Klarna <support@klarna.com>
 * @copyright  2012 Klarna AB
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link       http://developers.klarna.com/
 */
class Klarna_Checkout_CurlFactoryStub extends Klarna_Checkout_HTTP_CURLFactory
{
    /**
     * @var array
     */
    public $handles;

    /**
     * @var array
     */
    public $data = array();

    /**
     * Get a handle
     *
     * @return Klarna_Checkout_HTTP_CURLHandleStub
     */
    public function handle()
    {
        if ($this->handles === array()) {
            throw new Klarna_Checkout_Exception("No more handles.", 999);
        }

        return array_shift($this->handles);
    }

    /**
     * Add a new handle
     *
     * @param string $url        expected url response
     * @param int    $statusCode HTTP Status code
     * @param array  $headers    HTTP Headers
     *
     * @return void
     */
    public function addHandle($url, $statusCode, array $headers)
    {
        $curl = new Klarna_Checkout_HTTP_CURLHandleStub();

        $curl->headers = $headers;

        $curl->info['http_code'] = $statusCode;
        $curl->expectedURL = $url;

        $self = $this;

        $curl->response = function ($curl) use ($self) {
            if (array_key_exists(CURLOPT_POSTFIELDS, $curl->options)) {
                $data = json_decode($curl->options[CURLOPT_POSTFIELDS], true);
                if (array_key_exists('test', $data)) {
                    $data['test'] = strtoupper($data['test']);
                }
                $self->data = $data;
            }
            return json_encode($self->data);
        };

        $this->handles[] = $curl;

    }
}
