<?php
/**
 * Base class for HTTP_Request2 adapters
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
 * @version    CVS: $Id: Adapter.php,v 1.4 2009/01/26 23:07:27 avb Exp $
 * @link       http://pear.php.net/package/HTTP_Request2
 */

/**
 * Class representing a HTTP response
 */
require_once 'HTTP/Request2/Response.php';

/**
 * Base class for HTTP_Request2 adapters
 *
 * HTTP_Request2 class itself only defines methods for aggregating the request
 * data, all actual work of sending the request to the remote server and 
 * receiving its response is performed by adapters.
 *
 * @category   HTTP
 * @package    HTTP_Request2
 * @author     Alexey Borzov <avb@php.net>
 * @version    Release: 0.4.0
 */
abstract class HTTP_Request2_Adapter
{
   /**
    * A list of methods that MUST NOT have a request body, per RFC 2616
    * @var  array
    */
    protected static $bodyDisallowed = array('TRACE');

   /**
    * Methods having defined semantics for request body
    *
    * Content-Length header (indicating that the body follows, section 4.3 of
    * RFC 2616) will be sent for these methods even if no body was added
    *
    * @var  array
    * @link http://pear.php.net/bugs/bug.php?id=12900
    * @link http://pear.php.net/bugs/bug.php?id=14740
    */
    protected static $bodyRequired = array('POST', 'PUT');

   /**
    * Request being sent
    * @var  HTTP_Request2
    */
    protected $request;

   /**
    * Request body
    * @var  string|resource|HTTP_Request2_MultipartBody
    * @see  HTTP_Request2::getBody()
    */
    protected $requestBody;

   /**
    * Length of the request body
    * @var  integer
    */
    protected $contentLength;

   /**
    * Sends request to the remote server and returns its response
    *
    * @param    HTTP_Request2
    * @return   HTTP_Request2_Response
    * @throws   HTTP_Request2_Exception
    */
    abstract public function sendRequest(HTTP_Request2 $request);

   /**
    * Calculates length of the request body, adds proper headers
    *
    * @param    array   associative array of request headers, this method will 
    *                   add proper 'Content-Length' and 'Content-Type' headers 
    *                   to this array (or remove them if not needed)
    */
    protected function calculateRequestLength(&$headers)
    {
        $this->requestBody = $this->request->getBody();

        if (is_string($this->requestBody)) {
            $this->contentLength = strlen($this->requestBody);
        } elseif (is_resource($this->requestBody)) {
            $stat = fstat($this->requestBody);
            $this->contentLength = $stat['size'];
            rewind($this->requestBody);
        } else {
            $this->contentLength = $this->requestBody->getLength();
            $headers['content-type'] = 'multipart/form-data; boundary=' .
                                       $this->requestBody->getBoundary();
            $this->requestBody->rewind();
        }

        if (in_array($this->request->getMethod(), self::$bodyDisallowed) ||
            0 == $this->contentLength
        ) {
            unset($headers['content-type']);
            // No body: send a Content-Length header nonetheless (request #12900),
            // but do that only for methods that require a body (bug #14740)
            if (in_array($this->request->getMethod(), self::$bodyRequired)) {
                $headers['content-length'] = 0;
            } else {
                unset($headers['content-length']);
            }
        } else {
            if (empty($headers['content-type'])) {
                $headers['content-type'] = 'application/x-www-form-urlencoded';
            }
            $headers['content-length'] = $this->contentLength;
        }
    }
}
?>
