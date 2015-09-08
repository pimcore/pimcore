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
 * A simple class handling the header callback for cURL.
 *
 * @category   Payment
 * @package    Payment_Klarna
 * @subpackage HTTP
 * @author     Klarna <support@klarna.com>
 * @copyright  2012 Klarna AB
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link       http://developers.klarna.com/
 */
class Klarna_Checkout_HTTP_CURLHeaders
{
    /**
     * Response headers, cleared for each request.
     *
     * @var array
     */
    protected $headers;

    /**
     * Initializes a new instance of the HTTP cURL class.
     */
    public function __construct()
    {
        $this->headers = array();
    }

    /**
     * Callback method to handle custom headers.
     *
     * @param resource $curl   the cURL resource.
     * @param string   $header the header data.
     *
     * @return int the number of bytes handled.
     */
    public function processHeader($curl, $header)
    {
        $curl = null;
        //TODO replace with regexp, e.g. /^([^:]+):([^:]*)$/ ?
        $pos = strpos($header, ':');
        // Didn't find a colon.
        if ($pos === false) {
            // Not real header, abort.
            return strlen($header);
        }

        $key = substr($header, 0, $pos);
        $value = trim(substr($header, $pos+1));

        $this->headers[$key] = trim($value);

        return strlen($header);
    }

    /**
     * Gets the accumulated headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
