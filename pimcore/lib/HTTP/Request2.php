<?php
/**
 * Class representing a HTTP request
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
 * @version    CVS: $Id: Request2.php,v 1.12 2009/04/03 21:32:48 avb Exp $
 * @link       http://pear.php.net/package/HTTP_Request2
 */

/**
 * A class representing an URL as per RFC 3986.
 */
require_once 'Net/URL2.php';

/**
 * Exception class for HTTP_Request2 package
 */ 
require_once 'HTTP/Request2/Exception.php';

/**
 * Class representing a HTTP request
 *
 * @category   HTTP
 * @package    HTTP_Request2
 * @author     Alexey Borzov <avb@php.net>
 * @version    Release: 0.4.0
 * @link       http://tools.ietf.org/html/rfc2616#section-5
 */
class HTTP_Request2 implements SplSubject
{
   /**#@+
    * Constants for HTTP request methods
    *
    * @link http://tools.ietf.org/html/rfc2616#section-5.1.1
    */
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_GET     = 'GET';
    const METHOD_HEAD    = 'HEAD';
    const METHOD_POST    = 'POST';
    const METHOD_PUT     = 'PUT';
    const METHOD_DELETE  = 'DELETE';
    const METHOD_TRACE   = 'TRACE';
    const METHOD_CONNECT = 'CONNECT';
   /**#@-*/

   /**#@+
    * Constants for HTTP authentication schemes 
    *
    * @link http://tools.ietf.org/html/rfc2617
    */
    const AUTH_BASIC  = 'basic';
    const AUTH_DIGEST = 'digest';
   /**#@-*/

   /**
    * Regular expression used to check for invalid symbols in RFC 2616 tokens
    * @link http://pear.php.net/bugs/bug.php?id=15630
    */
    const REGEXP_INVALID_TOKEN = '![\x00-\x1f\x7f-\xff()<>@,;:\\\\"/\[\]?={}\s]!';

   /**
    * Regular expression used to check for invalid symbols in cookie strings
    * @link http://pear.php.net/bugs/bug.php?id=15630
    * @link http://cgi.netscape.com/newsref/std/cookie_spec.html
    */
    const REGEXP_INVALID_COOKIE = '/[\s,;]/';

   /**
    * Fileinfo magic database resource
    * @var  resource
    * @see  detectMimeType()
    */
    private static $_fileinfoDb;

   /**
    * Observers attached to the request (instances of SplObserver)
    * @var  array
    */
    protected $observers = array();

   /**
    * Request URL
    * @var  Net_URL2
    */
    protected $url;

   /**
    * Request method
    * @var  string
    */
    protected $method = self::METHOD_GET;

   /**
    * Authentication data
    * @var  array
    * @see  getAuth()
    */
    protected $auth;

   /**
    * Request headers
    * @var  array
    */
    protected $headers = array();

   /**
    * Configuration parameters
    * @var  array
    * @see  setConfig()
    */
    protected $config = array(
        'adapter'           => 'HTTP_Request2_Adapter_Socket',
        'connect_timeout'   => 10,
        'timeout'           => 0,
        'use_brackets'      => true,
        'protocol_version'  => '1.1',
        'buffer_size'       => 16384,
        'store_body'        => true,

        'proxy_host'        => '',
        'proxy_port'        => '',
        'proxy_user'        => '',
        'proxy_password'    => '',
        'proxy_auth_scheme' => self::AUTH_BASIC,

        'ssl_verify_peer'   => true,
        'ssl_verify_host'   => true,
        'ssl_cafile'        => null,
        'ssl_capath'        => null,
        'ssl_local_cert'    => null,
        'ssl_passphrase'    => null,

        'digest_compat_ie'  => false
    );

   /**
    * Last event in request / response handling, intended for observers
    * @var  array
    * @see  getLastEvent()
    */
    protected $lastEvent = array(
        'name' => 'start',
        'data' => null
    );

   /**
    * Request body
    * @var  string|resource
    * @see  setBody()
    */
    protected $body = '';

   /**
    * Array of POST parameters
    * @var  array
    */
    protected $postParams = array();

   /**
    * Array of file uploads (for multipart/form-data POST requests) 
    * @var  array
    */
    protected $uploads = array();

   /**
    * Adapter used to perform actual HTTP request
    * @var  HTTP_Request2_Adapter
    */
    protected $adapter;


   /**
    * Constructor. Can set request URL, method and configuration array.
    *
    * Also sets a default value for User-Agent header. 
    *
    * @param    string|Net_Url2     Request URL
    * @param    string              Request method
    * @param    array               Configuration for this Request instance
    */
    public function __construct($url = null, $method = self::METHOD_GET, array $config = array())
    {
        if (!empty($url)) {
            $this->setUrl($url);
        }
        if (!empty($method)) {
            $this->setMethod($method);
        }
        $this->setConfig($config);
        $this->setHeader('user-agent', 'HTTP_Request2/0.4.0 ' .
                         '(http://pear.php.net/package/http_request2) ' .
                         'PHP/' . phpversion());
    }

   /**
    * Sets the URL for this request
    *
    * If the URL has userinfo part (username & password) these will be removed
    * and converted to auth data. If the URL does not have a path component,
    * that will be set to '/'.
    *
    * @param    string|Net_URL2 Request URL
    * @return   HTTP_Request2
    * @throws   HTTP_Request2_Exception
    */
    public function setUrl($url)
    {
        if (is_string($url)) {
            $url = new Net_URL2($url);
        }
        if (!$url instanceof Net_URL2) {
            throw new HTTP_Request2_Exception('Parameter is not a valid HTTP URL');
        }
        // URL contains username / password?
        if ($url->getUserinfo()) {
            $username = $url->getUser();
            $password = $url->getPassword();
            $this->setAuth(rawurldecode($username), $password? rawurldecode($password): '');
            $url->setUserinfo('');
        }
        if ('' == $url->getPath()) {
            $url->setPath('/');
        }
        $this->url = $url;

        return $this;
    }

   /**
    * Returns the request URL
    *
    * @return   Net_URL2
    */
    public function getUrl()
    {
        return $this->url;
    }

   /**
    * Sets the request method
    *
    * @param    string
    * @return   HTTP_Request2
    * @throws   HTTP_Request2_Exception if the method name is invalid
    */
    public function setMethod($method)
    {
        // Method name should be a token: http://tools.ietf.org/html/rfc2616#section-5.1.1
        if (preg_match(self::REGEXP_INVALID_TOKEN, $method)) {
            throw new HTTP_Request2_Exception("Invalid request method '{$method}'");
        }
        $this->method = $method;

        return $this;
    }

   /**
    * Returns the request method
    *
    * @return   string
    */
    public function getMethod()
    {
        return $this->method;
    }

   /**
    * Sets the configuration parameter(s)
    *
    * The following parameters are available:
    * <ul>
    *   <li> 'adapter'           - adapter to use (string)</li>
    *   <li> 'connect_timeout'   - Connection timeout in seconds (integer)</li>
    *   <li> 'timeout'           - Total number of seconds a request can take.
    *                              Use 0 for no limit, should be greater than 
    *                              'connect_timeout' if set (integer)</li>
    *   <li> 'use_brackets'      - Whether to append [] to array variable names (bool)</li>
    *   <li> 'protocol_version'  - HTTP Version to use, '1.0' or '1.1' (string)</li>
    *   <li> 'buffer_size'       - Buffer size to use for reading and writing (int)</li>
    *   <li> 'store_body'        - Whether to store response body in response object.
    *                              Set to false if receiving a huge response and
    *                              using an Observer to save it (boolean)</li>
    *   <li> 'proxy_host'        - Proxy server host (string)</li>
    *   <li> 'proxy_port'        - Proxy server port (integer)</li>
    *   <li> 'proxy_user'        - Proxy auth username (string)</li>
    *   <li> 'proxy_password'    - Proxy auth password (string)</li>
    *   <li> 'proxy_auth_scheme' - Proxy auth scheme, one of HTTP_Request2::AUTH_* constants (string)</li>
    *   <li> 'ssl_verify_peer'   - Whether to verify peer's SSL certificate (bool)</li>
    *   <li> 'ssl_verify_host'   - Whether to check that Common Name in SSL
    *                              certificate matches host name (bool)</li>
    *   <li> 'ssl_cafile'        - Cerificate Authority file to verify the peer
    *                              with (use with 'ssl_verify_peer') (string)</li>
    *   <li> 'ssl_capath'        - Directory holding multiple Certificate 
    *                              Authority files (string)</li>
    *   <li> 'ssl_local_cert'    - Name of a file containing local cerificate (string)</li>
    *   <li> 'ssl_passphrase'    - Passphrase with which local certificate
    *                              was encoded (string)</li>
    *   <li> 'digest_compat_ie'  - Whether to imitate behaviour of MSIE 5 and 6
    *                              in using URL without query string in digest
    *                              authentication (boolean)</li>
    * </ul>
    *
    * @param    string|array    configuration parameter name or array
    *                           ('parameter name' => 'parameter value')
    * @param    mixed           parameter value if $nameOrConfig is not an array
    * @return   HTTP_Request2
    * @throws   HTTP_Request2_Exception If the parameter is unknown
    */
    public function setConfig($nameOrConfig, $value = null)
    {
        if (is_array($nameOrConfig)) {
            foreach ($nameOrConfig as $name => $value) {
                $this->setConfig($name, $value);
            }

        } else {
            if (!array_key_exists($nameOrConfig, $this->config)) {
                throw new HTTP_Request2_Exception(
                    "Unknown configuration parameter '{$nameOrConfig}'"
                );
            }
            $this->config[$nameOrConfig] = $value;
        }

        return $this;
    }

   /**
    * Returns the value(s) of the configuration parameter(s)
    *
    * @param    string  parameter name
    * @return   mixed   value of $name parameter, array of all configuration 
    *                   parameters if $name is not given
    * @throws   HTTP_Request2_Exception If the parameter is unknown
    */
    public function getConfig($name = null)
    {
        if (null === $name) {
            return $this->config;
        } elseif (!array_key_exists($name, $this->config)) {
            throw new HTTP_Request2_Exception(
                "Unknown configuration parameter '{$name}'"
            );
        }
        return $this->config[$name];
    }

   /**
    * Sets the autentification data
    *
    * @param    string  user name
    * @param    string  password
    * @param    string  authentication scheme
    * @return   HTTP_Request2
    */ 
    public function setAuth($user, $password = '', $scheme = self::AUTH_BASIC)
    {
        if (empty($user)) {
            $this->auth = null;
        } else {
            $this->auth = array(
                'user'     => (string)$user,
                'password' => (string)$password,
                'scheme'   => $scheme
            );
        }

        return $this;
    }

   /**
    * Returns the authentication data
    *
    * The array has the keys 'user', 'password' and 'scheme', where 'scheme'
    * is one of the HTTP_Request2::AUTH_* constants.
    *
    * @return   array
    */
    public function getAuth()
    {
        return $this->auth;
    }

   /**
    * Sets request header(s)
    *
    * The first parameter may be either a full header string 'header: value' or
    * header name. In the former case $value parameter is ignored, in the latter 
    * the header's value will either be set to $value or the header will be
    * removed if $value is null. The first parameter can also be an array of
    * headers, in that case method will be called recursively.
    *
    * Note that headers are treated case insensitively as per RFC 2616.
    * 
    * <code>
    * $req->setHeader('Foo: Bar'); // sets the value of 'Foo' header to 'Bar'
    * $req->setHeader('FoO', 'Baz'); // sets the value of 'Foo' header to 'Baz'
    * $req->setHeader(array('foo' => 'Quux')); // sets the value of 'Foo' header to 'Quux'
    * $req->setHeader('FOO'); // removes 'Foo' header from request
    * </code>
    *
    * @param    string|array    header name, header string ('Header: value')
    *                           or an array of headers
    * @param    string|null     header value, header will be removed if null
    * @return   HTTP_Request2
    * @throws   HTTP_Request2_Exception
    */
    public function setHeader($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                if (is_string($k)) {
                    $this->setHeader($k, $v);
                } else {
                    $this->setHeader($v);
                }
            }
        } else {
            if (null === $value && strpos($name, ':')) {
                list($name, $value) = array_map('trim', explode(':', $name, 2));
            }
            // Header name should be a token: http://tools.ietf.org/html/rfc2616#section-4.2
            if (preg_match(self::REGEXP_INVALID_TOKEN, $name)) {
                throw new HTTP_Request2_Exception("Invalid header name '{$name}'");
            }
            // Header names are case insensitive anyway
            $name = strtolower($name);
            if (null === $value) {
                unset($this->headers[$name]);
            } else {
                $this->headers[$name] = $value;
            }
        }
        
        return $this;
    }

   /**
    * Returns the request headers
    *
    * The array is of the form ('header name' => 'header value'), header names
    * are lowercased
    *
    * @return   array
    */
    public function getHeaders()
    {
        return $this->headers;
    }

   /**
    * Appends a cookie to "Cookie:" header
    *
    * @param    string  cookie name
    * @param    string  cookie value
    * @return   HTTP_Request2
    * @throws   HTTP_Request2_Exception
    */
    public function addCookie($name, $value)
    {
        $cookie = $name . '=' . $value;
        if (preg_match(self::REGEXP_INVALID_COOKIE, $cookie)) {
            throw new HTTP_Request2_Exception("Invalid cookie: '{$cookie}'");
        }
        $cookies = empty($this->headers['cookie'])? '': $this->headers['cookie'] . '; ';
        $this->setHeader('cookie', $cookies . $cookie);

        return $this;
    }

   /**
    * Sets the request body
    *
    * @param    string  Either a string with the body or filename containing body
    * @param    bool    Whether first parameter is a filename
    * @return   HTTP_Request2
    * @throws   HTTP_Request2_Exception
    */
    public function setBody($body, $isFilename = false)
    {
        if (!$isFilename) {
            $this->body = (string)$body;
        } else {
            if (!($fp = @fopen($body, 'rb'))) {
                throw new HTTP_Request2_Exception("Cannot open file {$body}");
            }
            $this->body = $fp;
            if (empty($this->headers['content-type'])) {
                $this->setHeader('content-type', self::detectMimeType($body));
            }
        }

        return $this;
    }

   /**
    * Returns the request body
    *
    * @return   string|resource|HTTP_Request2_MultipartBody
    */
    public function getBody()
    {
        if (self::METHOD_POST == $this->method && 
            (!empty($this->postParams) || !empty($this->uploads))
        ) {
            if ('application/x-www-form-urlencoded' == $this->headers['content-type']) {
                $body = http_build_query($this->postParams, '', '&');
                if (!$this->getConfig('use_brackets')) {
                    $body = preg_replace('/%5B\d+%5D=/', '=', $body);
                }
                // support RFC 3986 by not encoding '~' symbol (request #15368)
                return str_replace('%7E', '~', $body);

            } elseif ('multipart/form-data' == $this->headers['content-type']) {
                require_once 'HTTP/Request2/MultipartBody.php';
                return new HTTP_Request2_MultipartBody(
                    $this->postParams, $this->uploads, $this->getConfig('use_brackets')
                );
            }
        }
        return $this->body;
    }

   /**
    * Adds a file to form-based file upload
    *
    * Used to emulate file upload via a HTML form. The method also sets
    * Content-Type of HTTP request to 'multipart/form-data'.
    *
    * If you just want to send the contents of a file as the body of HTTP
    * request you should use setBody() method.
    *
    * @param    string  name of file-upload field
    * @param    mixed   full name of local file
    * @param    string  filename to send in the request 
    * @param    string  content-type of file being uploaded
    * @return   HTTP_Request2
    * @throws   HTTP_Request2_Exception
    */
    public function addUpload($fieldName, $filename, $sendFilename = null,
                              $contentType = null)
    {
        if (!is_array($filename)) {
            if (!($fp = @fopen($filename, 'rb'))) {
                throw new HTTP_Request2_Exception("Cannot open file {$filename}");
            }
            $this->uploads[$fieldName] = array(
                'fp'        => $fp,
                'filename'  => empty($sendFilename)? basename($filename): $sendFilename,
                'size'      => filesize($filename),
                'type'      => empty($contentType)? self::detectMimeType($filename): $contentType
            );
        } else {
            $fps = $names = $sizes = $types = array();
            foreach ($filename as $f) {
                if (!is_array($f)) {
                    $f = array($f);
                }
                if (!($fp = @fopen($f[0], 'rb'))) {
                    throw new HTTP_Request2_Exception("Cannot open file {$f[0]}");
                }
                $fps[]   = $fp;
                $names[] = empty($f[1])? basename($f[0]): $f[1];
                $sizes[] = filesize($f[0]);
                $types[] = empty($f[2])? self::detectMimeType($f[0]): $f[2];
            }
            $this->uploads[$fieldName] = array(
                'fp' => $fps, 'filename' => $names, 'size' => $sizes, 'type' => $types
            );
        }
        if (empty($this->headers['content-type']) ||
            'application/x-www-form-urlencoded' == $this->headers['content-type']
        ) {
            $this->setHeader('content-type', 'multipart/form-data');
        }

        return $this;
    }

   /**
    * Adds POST parameter(s) to the request.
    *
    * @param    string|array    parameter name or array ('name' => 'value')
    * @param    mixed           parameter value (can be an array)
    * @return   HTTP_Request2
    */
    public function addPostParameter($name, $value = null)
    {
        if (!is_array($name)) {
            $this->postParams[$name] = $value;
        } else {
            foreach ($name as $k => $v) {
                $this->addPostParameter($k, $v);
            }
        }
        if (empty($this->headers['content-type'])) {
            $this->setHeader('content-type', 'application/x-www-form-urlencoded');
        }

        return $this;
    }

   /**
    * Attaches a new observer
    *
    * @param    SplObserver
    */
    public function attach(SplObserver $observer)
    {
        foreach ($this->observers as $attached) {
            if ($attached === $observer) {
                return;
            }
        }
        $this->observers[] = $observer;
    }

   /**
    * Detaches an existing observer
    *
    * @param    SplObserver
    */
    public function detach(SplObserver $observer)
    {
        foreach ($this->observers as $key => $attached) {
            if ($attached === $observer) {
                unset($this->observers[$key]);
                return;
            }
        }
    }

   /**
    * Notifies all observers
    */
    public function notify()
    {
        foreach ($this->observers as $observer) {
            $observer->update($this);
        }
    }

   /**
    * Sets the last event
    *
    * Adapters should use this method to set the current state of the request
    * and notify the observers.
    *
    * @param    string  event name
    * @param    mixed   event data
    */
    public function setLastEvent($name, $data = null)
    {
        $this->lastEvent = array(
            'name' => $name,
            'data' => $data
        );
        $this->notify();
    }

   /**
    * Returns the last event
    *
    * Observers should use this method to access the last change in request.
    * The following event names are possible:
    * <ul>
    *   <li>'connect'                 - after connection to remote server,
    *                                   data is the destination (string)</li>
    *   <li>'disconnect'              - after disconnection from server</li>
    *   <li>'sentHeaders'             - after sending the request headers,
    *                                   data is the headers sent (string)</li>
    *   <li>'sentBodyPart'            - after sending a part of the request body, 
    *                                   data is the length of that part (int)</li>
    *   <li>'receivedHeaders'         - after receiving the response headers,
    *                                   data is HTTP_Request2_Response object</li>
    *   <li>'receivedBodyPart'        - after receiving a part of the response
    *                                   body, data is that part (string)</li>
    *   <li>'receivedEncodedBodyPart' - as 'receivedBodyPart', but data is still
    *                                   encoded by Content-Encoding</li>
    *   <li>'receivedBody'            - after receiving the complete response
    *                                   body, data is HTTP_Request2_Response object</li>
    * </ul>
    * Different adapters may not send all the event types. Mock adapter does
    * not send any events to the observers.
    *
    * @return   array   The array has two keys: 'name' and 'data'
    */
    public function getLastEvent()
    {
        return $this->lastEvent;
    }

   /**
    * Sets the adapter used to actually perform the request
    *
    * You can pass either an instance of a class implementing HTTP_Request2_Adapter
    * or a class name. The method will only try to include a file if the class
    * name starts with HTTP_Request2_Adapter_, it will also try to prepend this
    * prefix to the class name if it doesn't contain any underscores, so that
    * <code>
    * $request->setAdapter('curl');
    * </code>
    * will work.
    *
    * @param    string|HTTP_Request2_Adapter
    * @return   HTTP_Request2
    * @throws   HTTP_Request2_Exception
    */
    public function setAdapter($adapter)
    {
        if (is_string($adapter)) {
            if (!class_exists($adapter, false)) {
                if (false === strpos($adapter, '_')) {
                    $adapter = 'HTTP_Request2_Adapter_' . ucfirst($adapter);
                }
                if (preg_match('/^HTTP_Request2_Adapter_([a-zA-Z0-9]+)$/', $adapter)) {
                    include_once str_replace('_', DIRECTORY_SEPARATOR, $adapter) . '.php';
                }
                if (!class_exists($adapter, false)) {
                    throw new HTTP_Request2_Exception("Class {$adapter} not found");
                }
            }
            $adapter = new $adapter;
        }
        if (!$adapter instanceof HTTP_Request2_Adapter) {
            throw new HTTP_Request2_Exception('Parameter is not a HTTP request adapter');
        }
        $this->adapter = $adapter;

        return $this;
    }

   /**
    * Sends the request and returns the response
    *
    * @throws   HTTP_Request2_Exception
    * @return   HTTP_Request2_Response
    */
    public function send()
    {
        // Sanity check for URL
        if (!$this->url instanceof Net_URL2) {
            throw new HTTP_Request2_Exception('No URL given');
        } elseif (!$this->url->isAbsolute()) {
            throw new HTTP_Request2_Exception('Absolute URL required');
        } elseif (!in_array(strtolower($this->url->getScheme()), array('https', 'http'))) {
            throw new HTTP_Request2_Exception('Not a HTTP URL');
        }
        if (empty($this->adapter)) {
            $this->setAdapter($this->getConfig('adapter'));
        }
        // magic_quotes_runtime may break file uploads and chunked response
        // processing; see bug #4543
        if ($magicQuotes = ini_get('magic_quotes_runtime')) {
            ini_set('magic_quotes_runtime', false);
        }
        // force using single byte encoding if mbstring extension overloads
        // strlen() and substr(); see bug #1781, bug #10605
        if (extension_loaded('mbstring') && (2 & ini_get('mbstring.func_overload'))) {
            $oldEncoding = mb_internal_encoding();
            mb_internal_encoding('iso-8859-1');
        }

        try {
            $response = $this->adapter->sendRequest($this);
        } catch (Exception $e) {
        }
        // cleanup in either case (poor man's "finally" clause)
        if ($magicQuotes) {
            ini_set('magic_quotes_runtime', true);
        }
        if (!empty($oldEncoding)) {
            mb_internal_encoding($oldEncoding);
        }
        // rethrow the exception
        if (!empty($e)) {
            throw $e;
        }
        return $response;
    }

   /**
    * Tries to detect MIME type of a file
    *
    * The method will try to use fileinfo extension if it is available,
    * deprecated mime_content_type() function in the other case. If neither
    * works, default 'application/octet-stream' MIME type is returned
    *
    * @param    string  filename
    * @return   string  file MIME type
    */
    protected static function detectMimeType($filename)
    {
        // finfo extension from PECL available 
        if (function_exists('finfo_open')) {
            if (!isset(self::$_fileinfoDb)) {
                self::$_fileinfoDb = @finfo_open(FILEINFO_MIME);
            }
            if (self::$_fileinfoDb) { 
                $info = finfo_file(self::$_fileinfoDb, $filename);
            }
        }
        // (deprecated) mime_content_type function available
        if (empty($info) && function_exists('mime_content_type')) {
            return mime_content_type($filename);
        }
        return empty($info)? 'application/octet-stream': $info;
    }
}
?>