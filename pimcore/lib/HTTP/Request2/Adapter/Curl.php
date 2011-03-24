<?php
/**
 * Adapter for HTTP_Request2 wrapping around cURL extension
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
 * @version    CVS: $Id: Curl.php,v 1.9 2009/04/03 21:32:48 avb Exp $
 * @link       http://pear.php.net/package/HTTP_Request2
 */

/**
 * Base class for HTTP_Request2 adapters
 */
require_once 'HTTP/Request2/Adapter.php';

/**
 * Adapter for HTTP_Request2 wrapping around cURL extension
 *
 * @category    HTTP
 * @package     HTTP_Request2
 * @author      Alexey Borzov <avb@php.net>
 * @version     Release: 0.4.0
 */
class HTTP_Request2_Adapter_Curl extends HTTP_Request2_Adapter
{
   /**
    * Mapping of header names to cURL options
    * @var  array
    */
    protected static $headerMap = array(
        'accept-encoding' => CURLOPT_ENCODING,
        'cookie'          => CURLOPT_COOKIE,
        'referer'         => CURLOPT_REFERER,
        'user-agent'      => CURLOPT_USERAGENT
    );

   /**
    * Mapping of SSL context options to cURL options
    * @var  array
    */
    protected static $sslContextMap = array(
        'ssl_verify_peer' => CURLOPT_SSL_VERIFYPEER,
        'ssl_cafile'      => CURLOPT_CAINFO,
        'ssl_capath'      => CURLOPT_CAPATH,
        'ssl_local_cert'  => CURLOPT_SSLCERT,
        'ssl_passphrase'  => CURLOPT_SSLCERTPASSWD
   );

   /**
    * Response being received
    * @var  HTTP_Request2_Response
    */
    protected $response;

   /**
    * Whether 'sentHeaders' event was sent to observers
    * @var  boolean
    */
    protected $eventSentHeaders = false;

   /**
    * Whether 'receivedHeaders' event was sent to observers
    * @var boolean
    */
    protected $eventReceivedHeaders = false;

   /**
    * Position within request body
    * @var  integer
    * @see  callbackReadBody()
    */
    protected $position = 0;

   /**
    * Information about last transfer, as returned by curl_getinfo()
    * @var  array
    */
    protected $lastInfo;

   /**
    * Sends request to the remote server and returns its response
    *
    * @param    HTTP_Request2
    * @return   HTTP_Request2_Response
    * @throws   HTTP_Request2_Exception
    */
    public function sendRequest(HTTP_Request2 $request)
    {
        if (!extension_loaded('curl')) {
            throw new HTTP_Request2_Exception('cURL extension not available');
        }

        $this->request              = $request;
        $this->response             = null;
        $this->position             = 0;
        $this->eventSentHeaders     = false;
        $this->eventReceivedHeaders = false;

        try {
            if (false === curl_exec($ch = $this->createCurlHandle())) {
                $errorMessage = 'Error sending request: #' . curl_errno($ch) .
                                                       ' ' . curl_error($ch);
            }
        } catch (Exception $e) {
        }
        $this->lastInfo = curl_getinfo($ch);
        curl_close($ch);

        if (!empty($e)) {
            throw $e;
        } elseif (!empty($errorMessage)) {
            throw new HTTP_Request2_Exception($errorMessage);
        }

        if (0 < $this->lastInfo['size_download']) {
            $this->request->setLastEvent('receivedBody', $this->response);
        }
        return $this->response;
    }

   /**
    * Returns information about last transfer
    *
    * @return   array   associative array as returned by curl_getinfo()
    */
    public function getInfo()
    {
        return $this->lastInfo;
    }

   /**
    * Creates a new cURL handle and populates it with data from the request
    *
    * @return   resource    a cURL handle, as created by curl_init()
    * @throws   HTTP_Request2_Exception
    */
    protected function createCurlHandle()
    {
        $ch = curl_init();

        curl_setopt_array($ch, array(
            // setup callbacks
            CURLOPT_READFUNCTION   => array($this, 'callbackReadBody'),
            CURLOPT_HEADERFUNCTION => array($this, 'callbackWriteHeader'),
            CURLOPT_WRITEFUNCTION  => array($this, 'callbackWriteBody'),
            // disallow redirects
            CURLOPT_FOLLOWLOCATION => false,
            // buffer size
            CURLOPT_BUFFERSIZE     => $this->request->getConfig('buffer_size'),
            // connection timeout
            CURLOPT_CONNECTTIMEOUT => $this->request->getConfig('connect_timeout'),
            // save full outgoing headers, in case someone is interested
            CURLINFO_HEADER_OUT    => true,
            // request url
            CURLOPT_URL            => $this->request->getUrl()->getUrl()
        ));

        // request timeout
        if ($timeout = $this->request->getConfig('timeout')) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        }

        // set HTTP version
        switch ($this->request->getConfig('protocol_version')) {
            case '1.0':
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
                break;
            case '1.1':
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        }

        // set request method
        switch ($this->request->getMethod()) {
            case HTTP_Request2::METHOD_GET:
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
            case HTTP_Request2::METHOD_POST:
                curl_setopt($ch, CURLOPT_POST, true);
                break;
            default:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->request->getMethod());
        }

        // set proxy, if needed
        if ($host = $this->request->getConfig('proxy_host')) {
            if (!($port = $this->request->getConfig('proxy_port'))) {
                throw new HTTP_Request2_Exception('Proxy port not provided');
            }
            curl_setopt($ch, CURLOPT_PROXY, $host . ':' . $port);
            if ($user = $this->request->getConfig('proxy_user')) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $user . ':' .
                            $this->request->getConfig('proxy_password'));
                switch ($this->request->getConfig('proxy_auth_scheme')) {
                    case HTTP_Request2::AUTH_BASIC:
                        curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
                        break;
                    case HTTP_Request2::AUTH_DIGEST:
                        curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_DIGEST);
                }
            }
        }

        // set authentication data
        if ($auth = $this->request->getAuth()) {
            curl_setopt($ch, CURLOPT_USERPWD, $auth['user'] . ':' . $auth['password']);
            switch ($auth['scheme']) {
                case HTTP_Request2::AUTH_BASIC:
                    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                    break;
                case HTTP_Request2::AUTH_DIGEST:
                    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
            }
        }

        // set SSL options
        if (0 == strcasecmp($this->request->getUrl()->getScheme(), 'https')) {
            foreach ($this->request->getConfig() as $name => $value) {
                if ('ssl_verify_host' == $name && null !== $value) {
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $value? 2: 0);
                } elseif (isset(self::$sslContextMap[$name]) && null !== $value) {
                    curl_setopt($ch, self::$sslContextMap[$name], $value);
                }
            }
        }

        $headers = $this->request->getHeaders();
        // make cURL automagically send proper header
        if (!isset($headers['accept-encoding'])) {
            $headers['accept-encoding'] = '';
        }

        // set headers having special cURL keys
        foreach (self::$headerMap as $name => $option) {
            if (isset($headers[$name])) {
                curl_setopt($ch, $option, $headers[$name]);
                unset($headers[$name]);
            }
        }

        $this->calculateRequestLength($headers);

        // set headers not having special keys
        $headersFmt = array();
        foreach ($headers as $name => $value) {
            $canonicalName = implode('-', array_map('ucfirst', explode('-', $name)));
            $headersFmt[]  = $canonicalName . ': ' . $value;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headersFmt);

        return $ch;
    }

   /**
    * Callback function called by cURL for reading the request body
    *
    * @param    resource    cURL handle
    * @param    resource    file descriptor (not used)
    * @param    integer     maximum length of data to return
    * @return   string      part of the request body, up to $length bytes 
    */
    protected function callbackReadBody($ch, $fd, $length)
    {
        if (!$this->eventSentHeaders) {
            $this->request->setLastEvent(
                'sentHeaders', curl_getinfo($ch, CURLINFO_HEADER_OUT)
            );
            $this->eventSentHeaders = true;
        }
        if (in_array($this->request->getMethod(), self::$bodyDisallowed) ||
            0 == $this->contentLength || $this->position >= $this->contentLength
        ) {
            return '';
        }
        if (is_string($this->requestBody)) {
            $string = substr($this->requestBody, $this->position, $length);
        } elseif (is_resource($this->requestBody)) {
            $string = fread($this->requestBody, $length);
        } else {
            $string = $this->requestBody->read($length);
        }
        $this->request->setLastEvent('sentBodyPart', strlen($string));
        $this->position += strlen($string);
        return $string;
    }

   /**
    * Callback function called by cURL for saving the response headers
    *
    * @param    resource    cURL handle
    * @param    string      response header (with trailing CRLF)
    * @return   integer     number of bytes saved
    * @see      HTTP_Request2_Response::parseHeaderLine()
    */
    protected function callbackWriteHeader($ch, $string)
    {
        // we may receive a second set of headers if doing e.g. digest auth
        if ($this->eventReceivedHeaders || !$this->eventSentHeaders) {
            // don't bother with 100-Continue responses (bug #15785)
            if (!$this->eventSentHeaders ||
                $this->response->getStatus() >= 200
            ) {
                $this->request->setLastEvent(
                    'sentHeaders', curl_getinfo($ch, CURLINFO_HEADER_OUT)
                );
            }
            $this->eventSentHeaders = true;
            // we'll need a new response object
            if ($this->eventReceivedHeaders) {
                $this->eventReceivedHeaders = false;
                $this->response             = null;
            }
        }
        if (empty($this->response)) {
            $this->response = new HTTP_Request2_Response($string, false);
        } else {
            $this->response->parseHeaderLine($string);
            if ('' == trim($string)) {
                // don't bother with 100-Continue responses (bug #15785)
                if (200 <= $this->response->getStatus()) {
                    $this->request->setLastEvent('receivedHeaders', $this->response);
                }
                $this->eventReceivedHeaders = true;
            }
        }
        return strlen($string);
    }

   /**
    * Callback function called by cURL for saving the response body
    *
    * @param    resource    cURL handle (not used)
    * @param    string      part of the response body
    * @return   integer     number of bytes saved
    * @see      HTTP_Request2_Response::appendBody()
    */
    protected function callbackWriteBody($ch, $string)
    {
        // cURL calls WRITEFUNCTION callback without calling HEADERFUNCTION if 
        // response doesn't start with proper HTTP status line (see bug #15716)
        if (empty($this->response)) {
            throw new HTTP_Request2_Exception("Malformed response: {$string}");
        }
        if ($this->request->getConfig('store_body')) {
            $this->response->appendBody($string);
        }
        $this->request->setLastEvent('receivedBodyPart', $string);
        return strlen($string);
    }
}
?>
