<?php
/**
 * Mock adapter intended for testing
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright (c) 2008, 2009, Alexey Borzov <avb@php.net>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * The names of the authors may not be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   HTTP
 * @package    HTTP_Request2
 * @author     Alexey Borzov <avb@php.net>
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: Mock.php,v 1.4 2009/01/23 18:01:57 avb Exp $
 * @link       http://pear.php.net/package/HTTP_Request2
 */

/**
 * Base class for HTTP_Request2 adapters
 */
require_once 'HTTP/Request2/Adapter.php';

/**
 * Mock adapter intended for testing
 *
 * Can be used to test applications depending on HTTP_Request2 package without
 * actually performing any HTTP requests. This adapter will return responses
 * previously added via addResponse()
 * <code>
 * $mock = new HTTP_Request2_Adapter_Mock();
 * $mock->addResponse("HTTP/1.1 ... ");
 * 
 * $request = new HTTP_Request2();
 * $request->setAdapter($mock);
 * 
 * // This will return the response set above
 * $response = $req->send();
 * </code> 
 *
 * @category   HTTP
 * @package    HTTP_Request2
 * @author     Alexey Borzov <avb@php.net>
 * @version    Release: 0.4.0
 */
class HTTP_Request2_Adapter_Mock extends HTTP_Request2_Adapter
{
   /**
    * A queue of responses to be returned by sendRequest()
    * @var  array 
    */
    protected $responses = array();

   /**
    * Returns the next response from the queue built by addResponse()
    *
    * If the queue is empty will return default empty response with status 400,
    * if an Exception object was added to the queue it will be thrown.
    *
    * @param    HTTP_Request2
    * @return   HTTP_Request2_Response
    * @throws   Exception
    */
    public function sendRequest(HTTP_Request2 $request)
    {
        if (count($this->responses) > 0) {
            $response = array_shift($this->responses);
            if ($response instanceof HTTP_Request2_Response) {
                return $response;
            } else {
                // rethrow the exception,
                $class   = get_class($response);
                $message = $response->getMessage();
                $code    = $response->getCode();
                throw new $class($message, $code);
            }
        } else {
            return self::createResponseFromString("HTTP/1.1 400 Bad Request\r\n\r\n");
        }
    }

   /**
    * Adds response to the queue
    *
    * @param    mixed   either a string, a pointer to an open file,
    *                   a HTTP_Request2_Response or Exception object
    * @throws   HTTP_Request2_Exception
    */
    public function addResponse($response)
    {
        if (is_string($response)) {
            $response = self::createResponseFromString($response);
        } elseif (is_resource($response)) {
            $response = self::createResponseFromFile($response);
        } elseif (!$response instanceof HTTP_Request2_Response &&
                  !$response instanceof Exception
        ) {
            throw new HTTP_Request2_Exception('Parameter is not a valid response');
        }
        $this->responses[] = $response;
    }

   /**
    * Creates a new HTTP_Request2_Response object from a string
    *
    * @param    string
    * @return   HTTP_Request2_Response
    * @throws   HTTP_Request2_Exception
    */
    public static function createResponseFromString($str)
    {
        $parts       = preg_split('!(\r?\n){2}!m', $str, 2);
        $headerLines = explode("\n", $parts[0]); 
        $response    = new HTTP_Request2_Response(array_shift($headerLines));
        foreach ($headerLines as $headerLine) {
            $response->parseHeaderLine($headerLine);
        }
        $response->parseHeaderLine('');
        if (isset($parts[1])) {
            $response->appendBody($parts[1]);
        }
        return $response;
    }

   /**
    * Creates a new HTTP_Request2_Response object from a file
    *
    * @param    resource    file pointer returned by fopen()
    * @return   HTTP_Request2_Response
    * @throws   HTTP_Request2_Exception
    */
    public static function createResponseFromFile($fp)
    {
        $response = new HTTP_Request2_Response(fgets($fp));
        do {
            $headerLine = fgets($fp);
            $response->parseHeaderLine($headerLine);
        } while ('' != trim($headerLine));

        while (!feof($fp)) {
            $response->appendBody(fread($fp, 8192));
        }
        return $response;
    }
}
?>