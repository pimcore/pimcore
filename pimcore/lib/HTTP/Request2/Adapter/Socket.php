<?php
/**
 * Socket-based adapter for HTTP_Request2
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
 * @version    CVS: $Id: Socket.php,v 1.12 2009/05/03 10:46:42 avb Exp $
 * @link       http://pear.php.net/package/HTTP_Request2
 */

/**
 * Base class for HTTP_Request2 adapters
 */
require_once 'HTTP/Request2/Adapter.php';

/**
 * Socket-based adapter for HTTP_Request2
 *
 * This adapter uses only PHP sockets and will work on almost any PHP
 * environment. Code is based on original HTTP_Request PEAR package.
 *
 * @category    HTTP
 * @package     HTTP_Request2
 * @author      Alexey Borzov <avb@php.net>
 * @version     Release: 0.4.0
 */
class HTTP_Request2_Adapter_Socket extends HTTP_Request2_Adapter
{
   /**
    * Regular expression for 'token' rule from RFC 2616
    */ 
    const REGEXP_TOKEN = '[^\x00-\x1f\x7f-\xff()<>@,;:\\\\"/\[\]?={}\s]+';

   /**
    * Regular expression for 'quoted-string' rule from RFC 2616
    */
    const REGEXP_QUOTED_STRING = '"(?:\\\\.|[^\\\\"])*"';

   /**
    * Connected sockets, needed for Keep-Alive support
    * @var  array
    * @see  connect()
    */
    protected static $sockets = array();

   /**
    * Data for digest authentication scheme
    *
    * The keys for the array are URL prefixes. 
    *
    * The values are associative arrays with data (realm, nonce, nonce-count, 
    * opaque...) needed for digest authentication. Stored here to prevent making 
    * duplicate requests to digest-protected resources after we have already 
    * received the challenge.
    *
    * @var  array
    */
    protected static $challenges = array();

   /**
    * Connected socket
    * @var  resource
    * @see  connect()
    */
    protected $socket;

   /**
    * Challenge used for server digest authentication
    * @var  array
    */
    protected $serverChallenge;

   /**
    * Challenge used for proxy digest authentication
    * @var  array
    */
    protected $proxyChallenge;

   /**
    * Global timeout, exception will be raised if request continues past this time
    * @var  integer
    */
    protected $timeout = null;

   /**
    * Remaining length of the current chunk, when reading chunked response
    * @var  integer
    * @see  readChunked()
    */ 
    protected $chunkLength = 0;

   /**
    * Sends request to the remote server and returns its response
    *
    * @param    HTTP_Request2
    * @return   HTTP_Request2_Response
    * @throws   HTTP_Request2_Exception
    */
    public function sendRequest(HTTP_Request2 $request)
    {
        $this->request = $request;
        $keepAlive     = $this->connect();
        $headers       = $this->prepareHeaders();

        // Use global request timeout if given, see feature requests #5735, #8964 
        if ($timeout = $request->getConfig('timeout')) {
            $this->timeout = time() + $timeout;
        } else {
            $this->timeout = null;
        }

        try {
            if (false === @fwrite($this->socket, $headers, strlen($headers))) {
                throw new HTTP_Request2_Exception('Error writing request');
            }
            // provide request headers to the observer, see request #7633
            $this->request->setLastEvent('sentHeaders', $headers);
            $this->writeBody();

            if ($this->timeout && time() > $this->timeout) {
                throw new HTTP_Request2_Exception(
                    'Request timed out after ' . 
                    $request->getConfig('timeout') . ' second(s)'
                );
            }

            $response = $this->readResponse();

            if (!$this->canKeepAlive($keepAlive, $response)) {
                $this->disconnect();
            }

            if ($this->shouldUseProxyDigestAuth($response)) {
                return $this->sendRequest($request);
            }
            if ($this->shouldUseServerDigestAuth($response)) {
                return $this->sendRequest($request);
            }
            if ($authInfo = $response->getHeader('authentication-info')) {
                $this->updateChallenge($this->serverChallenge, $authInfo);
            }
            if ($proxyInfo = $response->getHeader('proxy-authentication-info')) {
                $this->updateChallenge($this->proxyChallenge, $proxyInfo);
            }

        } catch (Exception $e) {
            $this->disconnect();
            throw $e;
        }

        return $response;
    }

   /**
    * Connects to the remote server
    *
    * @return   bool    whether the connection can be persistent
    * @throws   HTTP_Request2_Exception
    */
    protected function connect()
    {
        $secure  = 0 == strcasecmp($this->request->getUrl()->getScheme(), 'https');
        $tunnel  = HTTP_Request2::METHOD_CONNECT == $this->request->getMethod();
        $headers = $this->request->getHeaders();
        $reqHost = $this->request->getUrl()->getHost();
        if (!($reqPort = $this->request->getUrl()->getPort())) {
            $reqPort = $secure? 443: 80;
        }

        if ($host = $this->request->getConfig('proxy_host')) {
            if (!($port = $this->request->getConfig('proxy_port'))) {
                throw new HTTP_Request2_Exception('Proxy port not provided');
            }
            $proxy = true;
        } else {
            $host  = $reqHost;
            $port  = $reqPort;
            $proxy = false;
        }

        if ($tunnel && !$proxy) {
            throw new HTTP_Request2_Exception(
                "Trying to perform CONNECT request without proxy"
            );
        }
        if ($secure && !in_array('ssl', stream_get_transports())) {
            throw new HTTP_Request2_Exception(
                'Need OpenSSL support for https:// requests'
            );
        }

        // RFC 2068, section 19.7.1: A client MUST NOT send the Keep-Alive
        // connection token to a proxy server...
        if ($proxy && !$secure && 
            !empty($headers['connection']) && 'Keep-Alive' == $headers['connection']
        ) {
            $this->request->setHeader('connection');
        }

        $keepAlive = ('1.1' == $this->request->getConfig('protocol_version') && 
                      empty($headers['connection'])) ||
                     (!empty($headers['connection']) &&
                      'Keep-Alive' == $headers['connection']);
        $host = ((!$secure || $proxy)? 'tcp://': 'ssl://') . $host;

        $options = array();
        if ($secure || $tunnel) {
            foreach ($this->request->getConfig() as $name => $value) {
                if ('ssl_' == substr($name, 0, 4) && null !== $value) {
                    if ('ssl_verify_host' == $name) {
                        if ($value) {
                            $options['CN_match'] = $reqHost;
                        }
                    } else {
                        $options[substr($name, 4)] = $value;
                    }
                }
            }
            ksort($options);
        }

        // Changing SSL context options after connection is established does *not*
        // work, we need a new connection if options change
        $remote    = $host . ':' . $port;
        $socketKey = $remote . (($secure && $proxy)? "->{$reqHost}:{$reqPort}": '') .
                     (empty($options)? '': ':' . serialize($options));
        unset($this->socket);

        // We use persistent connections and have a connected socket?
        // Ensure that the socket is still connected, see bug #16149
        if ($keepAlive && !empty(self::$sockets[$socketKey]) &&
            !feof(self::$sockets[$socketKey])
        ) {
            $this->socket =& self::$sockets[$socketKey];

        } elseif ($secure && $proxy && !$tunnel) {
            $this->establishTunnel();
            $this->request->setLastEvent(
                'connect', "ssl://{$reqHost}:{$reqPort} via {$host}:{$port}"
            );
            self::$sockets[$socketKey] =& $this->socket;

        } else {
            // Set SSL context options if doing HTTPS request or creating a tunnel
            $context = stream_context_create();
            foreach ($options as $name => $value) {
                if (!stream_context_set_option($context, 'ssl', $name, $value)) {
                    throw new HTTP_Request2_Exception(
                        "Error setting SSL context option '{$name}'"
                    );
                }
            }
            $this->socket = @stream_socket_client(
                $remote, $errno, $errstr,
                $this->request->getConfig('connect_timeout'),
                STREAM_CLIENT_CONNECT, $context
            );
            if (!$this->socket) {
                throw new HTTP_Request2_Exception(
                    "Unable to connect to {$remote}. Error #{$errno}: {$errstr}"
                );
            }
            $this->request->setLastEvent('connect', $remote);
            self::$sockets[$socketKey] =& $this->socket;
        }
        return $keepAlive;
    }

   /**
    * Establishes a tunnel to a secure remote server via HTTP CONNECT request
    *
    * This method will fail if 'ssl_verify_peer' is enabled. Probably because PHP
    * sees that we are connected to a proxy server (duh!) rather than the server
    * that presents its certificate.
    *
    * @link     http://tools.ietf.org/html/rfc2817#section-5.2
    * @throws   HTTP_Request2_Exception
    */
    protected function establishTunnel()
    {
        $donor   = new self;
        $connect = new HTTP_Request2(
            $this->request->getUrl(), HTTP_Request2::METHOD_CONNECT,
            array_merge($this->request->getConfig(),
                        array('adapter' => $donor))
        );
        $response = $connect->send();
        // Need any successful (2XX) response
        if (200 > $response->getStatus() || 300 <= $response->getStatus()) {
            throw new HTTP_Request2_Exception(
                'Failed to connect via HTTPS proxy. Proxy response: ' .
                $response->getStatus() . ' ' . $response->getReasonPhrase()
            );
        }
        $this->socket = $donor->socket;

        $modes = array(
            STREAM_CRYPTO_METHOD_TLS_CLIENT, 
            STREAM_CRYPTO_METHOD_SSLv3_CLIENT,
            STREAM_CRYPTO_METHOD_SSLv23_CLIENT,
            STREAM_CRYPTO_METHOD_SSLv2_CLIENT 
        );

        foreach ($modes as $mode) {
            if (stream_socket_enable_crypto($this->socket, true, $mode)) {
                return;
            }
        }
        throw new HTTP_Request2_Exception(
            'Failed to enable secure connection when connecting through proxy'
        );
    }

   /**
    * Checks whether current connection may be reused or should be closed
    *
    * @param    boolean                 whether connection could be persistent 
    *                                   in the first place
    * @param    HTTP_Request2_Response  response object to check
    * @return   boolean
    */
    protected function canKeepAlive($requestKeepAlive, HTTP_Request2_Response $response)
    {
        // Do not close socket on successful CONNECT request
        if (HTTP_Request2::METHOD_CONNECT == $this->request->getMethod() &&
            200 <= $response->getStatus() && 300 > $response->getStatus()
        ) {
            return true;
        }

        $lengthKnown = 'chunked' == strtolower($response->getHeader('transfer-encoding')) ||
                       null !== $response->getHeader('content-length');
        $persistent  = 'keep-alive' == strtolower($response->getHeader('connection')) ||
                       (null === $response->getHeader('connection') &&
                        '1.1' == $response->getVersion());
        return $requestKeepAlive && $lengthKnown && $persistent;
    }

   /**
    * Disconnects from the remote server
    */
    protected function disconnect()
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
            $this->socket = null;
            $this->request->setLastEvent('disconnect');
        }
    }

   /**
    * Checks whether another request should be performed with server digest auth
    *
    * Several conditions should be satisfied for it to return true:
    *   - response status should be 401
    *   - auth credentials should be set in the request object
    *   - response should contain WWW-Authenticate header with digest challenge
    *   - there is either no challenge stored for this URL or new challenge
    *     contains stale=true parameter (in other case we probably just failed 
    *     due to invalid username / password)
    *
    * The method stores challenge values in $challenges static property
    *
    * @param    HTTP_Request2_Response  response to check
    * @return   boolean whether another request should be performed
    * @throws   HTTP_Request2_Exception in case of unsupported challenge parameters
    */
    protected function shouldUseServerDigestAuth(HTTP_Request2_Response $response)
    {
        // no sense repeating a request if we don't have credentials
        if (401 != $response->getStatus() || !$this->request->getAuth()) {
            return false;
        }
        if (!$challenge = $this->parseDigestChallenge($response->getHeader('www-authenticate'))) {
            return false;
        }

        $url    = $this->request->getUrl();
        $scheme = $url->getScheme();
        $host   = $scheme . '://' . $url->getHost();
        if ($port = $url->getPort()) {
            if ((0 == strcasecmp($scheme, 'http') && 80 != $port) ||
                (0 == strcasecmp($scheme, 'https') && 443 != $port)
            ) {
                $host .= ':' . $port;
            }
        }

        if (!empty($challenge['domain'])) {
            $prefixes = array();
            foreach (preg_split('/\\s+/', $challenge['domain']) as $prefix) {
                // don't bother with different servers
                if ('/' == substr($prefix, 0, 1)) {
                    $prefixes[] = $host . $prefix;
                }
            }
        }
        if (empty($prefixes)) {
            $prefixes = array($host . '/');
        }

        $ret = true;
        foreach ($prefixes as $prefix) {
            if (!empty(self::$challenges[$prefix]) &&
                (empty($challenge['stale']) || strcasecmp('true', $challenge['stale']))
            ) {
                // probably credentials are invalid
                $ret = false;
            }
            self::$challenges[$prefix] =& $challenge;
        }
        return $ret;
    }

   /**
    * Checks whether another request should be performed with proxy digest auth
    *
    * Several conditions should be satisfied for it to return true:
    *   - response status should be 407
    *   - proxy auth credentials should be set in the request object
    *   - response should contain Proxy-Authenticate header with digest challenge
    *   - there is either no challenge stored for this proxy or new challenge
    *     contains stale=true parameter (in other case we probably just failed 
    *     due to invalid username / password)
    *
    * The method stores challenge values in $challenges static property
    *
    * @param    HTTP_Request2_Response  response to check
    * @return   boolean whether another request should be performed
    * @throws   HTTP_Request2_Exception in case of unsupported challenge parameters
    */
    protected function shouldUseProxyDigestAuth(HTTP_Request2_Response $response)
    {
        if (407 != $response->getStatus() || !$this->request->getConfig('proxy_user')) {
            return false;
        }
        if (!($challenge = $this->parseDigestChallenge($response->getHeader('proxy-authenticate')))) {
            return false;
        }

        $key = 'proxy://' . $this->request->getConfig('proxy_host') .
               ':' . $this->request->getConfig('proxy_port');

        if (!empty(self::$challenges[$key]) &&
            (empty($challenge['stale']) || strcasecmp('true', $challenge['stale']))
        ) {
            $ret = false;
        } else {
            $ret = true;
        }
        self::$challenges[$key] = $challenge;
        return $ret;
    }

   /**
    * Extracts digest method challenge from (WWW|Proxy)-Authenticate header value
    *
    * There is a problem with implementation of RFC 2617: several of the parameters
    * here are defined as quoted-string and thus may contain backslash escaped
    * double quotes (RFC 2616, section 2.2). However, RFC 2617 defines unq(X) as
    * just value of quoted-string X without surrounding quotes, it doesn't speak
    * about removing backslash escaping.
    *
    * Now realm parameter is user-defined and human-readable, strange things
    * happen when it contains quotes:
    *   - Apache allows quotes in realm, but apparently uses realm value without
    *     backslashes for digest computation
    *   - Squid allows (manually escaped) quotes there, but it is impossible to
    *     authorize with either escaped or unescaped quotes used in digest,
    *     probably it can't parse the response (?)
    *   - Both IE and Firefox display realm value with backslashes in 
    *     the password popup and apparently use the same value for digest
    *
    * HTTP_Request2 follows IE and Firefox (and hopefully RFC 2617) in
    * quoted-string handling, unfortunately that means failure to authorize 
    * sometimes
    *
    * @param    string  value of WWW-Authenticate or Proxy-Authenticate header
    * @return   mixed   associative array with challenge parameters, false if
    *                   no challenge is present in header value
    * @throws   HTTP_Request2_Exception in case of unsupported challenge parameters
    */
    protected function parseDigestChallenge($headerValue)
    {
        $authParam   = '(' . self::REGEXP_TOKEN . ')\\s*=\\s*(' .
                       self::REGEXP_TOKEN . '|' . self::REGEXP_QUOTED_STRING . ')';
        $challenge   = "!(?<=^|\\s|,)Digest ({$authParam}\\s*(,\\s*|$))+!";
        if (!preg_match($challenge, $headerValue, $matches)) {
            return false;
        }

        preg_match_all('!' . $authParam . '!', $matches[0], $params);
        $paramsAry   = array();
        $knownParams = array('realm', 'domain', 'nonce', 'opaque', 'stale',
                             'algorithm', 'qop');
        for ($i = 0; $i < count($params[0]); $i++) {
            // section 3.2.1: Any unrecognized directive MUST be ignored.
            if (in_array($params[1][$i], $knownParams)) {
                if ('"' == substr($params[2][$i], 0, 1)) {
                    $paramsAry[$params[1][$i]] = substr($params[2][$i], 1, -1);
                } else {
                    $paramsAry[$params[1][$i]] = $params[2][$i];
                }
            }
        }
        // we only support qop=auth
        if (!empty($paramsAry['qop']) && 
            !in_array('auth', array_map('trim', explode(',', $paramsAry['qop'])))
        ) {
            throw new HTTP_Request2_Exception(
                "Only 'auth' qop is currently supported in digest authentication, " .
                "server requested '{$paramsAry['qop']}'"
            );
        }
        // we only support algorithm=MD5
        if (!empty($paramsAry['algorithm']) && 'MD5' != $paramsAry['algorithm']) {
            throw new HTTP_Request2_Exception(
                "Only 'MD5' algorithm is currently supported in digest authentication, " .
                "server requested '{$paramsAry['algorithm']}'"
            );
        }

        return $paramsAry; 
    }

   /**
    * Parses [Proxy-]Authentication-Info header value and updates challenge
    *
    * @param    array   challenge to update
    * @param    string  value of [Proxy-]Authentication-Info header
    * @todo     validate server rspauth response
    */ 
    protected function updateChallenge(&$challenge, $headerValue)
    {
        $authParam   = '!(' . self::REGEXP_TOKEN . ')\\s*=\\s*(' .
                       self::REGEXP_TOKEN . '|' . self::REGEXP_QUOTED_STRING . ')!';
        $paramsAry   = array();

        preg_match_all($authParam, $headerValue, $params);
        for ($i = 0; $i < count($params[0]); $i++) {
            if ('"' == substr($params[2][$i], 0, 1)) {
                $paramsAry[$params[1][$i]] = substr($params[2][$i], 1, -1);
            } else {
                $paramsAry[$params[1][$i]] = $params[2][$i];
            }
        }
        // for now, just update the nonce value
        if (!empty($paramsAry['nextnonce'])) {
            $challenge['nonce'] = $paramsAry['nextnonce'];
            $challenge['nc']    = 1;
        }
    }

   /**
    * Creates a value for [Proxy-]Authorization header when using digest authentication
    *
    * @param    string  user name
    * @param    string  password
    * @param    string  request URL
    * @param    array   digest challenge parameters
    * @return   string  value of [Proxy-]Authorization request header
    * @link     http://tools.ietf.org/html/rfc2617#section-3.2.2
    */ 
    protected function createDigestResponse($user, $password, $url, &$challenge)
    {
        if (false !== ($q = strpos($url, '?')) && 
            $this->request->getConfig('digest_compat_ie')
        ) {
            $url = substr($url, 0, $q);
        }

        $a1 = md5($user . ':' . $challenge['realm'] . ':' . $password);
        $a2 = md5($this->request->getMethod() . ':' . $url);

        if (empty($challenge['qop'])) {
            $digest = md5($a1 . ':' . $challenge['nonce'] . ':' . $a2);
        } else {
            $challenge['cnonce'] = 'Req2.' . rand();
            if (empty($challenge['nc'])) {
                $challenge['nc'] = 1;
            }
            $nc     = sprintf('%08x', $challenge['nc']++);
            $digest = md5($a1 . ':' . $challenge['nonce'] . ':' . $nc . ':' .
                          $challenge['cnonce'] . ':auth:' . $a2);
        }
        return 'Digest username="' . str_replace(array('\\', '"'), array('\\\\', '\\"'), $user) . '", ' .
               'realm="' . $challenge['realm'] . '", ' .
               'nonce="' . $challenge['nonce'] . '", ' .
               'uri="' . $url . '", ' .
               'response="' . $digest . '"' .
               (!empty($challenge['opaque'])? 
                ', opaque="' . $challenge['opaque'] . '"':
                '') .
               (!empty($challenge['qop'])?
                ', qop="auth", nc=' . $nc . ', cnonce="' . $challenge['cnonce'] . '"':
                '');
    }

   /**
    * Adds 'Authorization' header (if needed) to request headers array
    *
    * @param    array   request headers
    * @param    string  request host (needed for digest authentication)
    * @param    string  request URL (needed for digest authentication)
    * @throws   HTTP_Request2_Exception
    */
    protected function addAuthorizationHeader(&$headers, $requestHost, $requestUrl)
    {
        if (!($auth = $this->request->getAuth())) {
            return;
        }
        switch ($auth['scheme']) {
            case HTTP_Request2::AUTH_BASIC:
                $headers['authorization'] = 
                    'Basic ' . base64_encode($auth['user'] . ':' . $auth['password']);
                break;

            case HTTP_Request2::AUTH_DIGEST:
                unset($this->serverChallenge);
                $fullUrl = ('/' == $requestUrl[0])?
                           $this->request->getUrl()->getScheme() . '://' .
                            $requestHost . $requestUrl:
                           $requestUrl;
                foreach (array_keys(self::$challenges) as $key) {
                    if ($key == substr($fullUrl, 0, strlen($key))) {
                        $headers['authorization'] = $this->createDigestResponse(
                            $auth['user'], $auth['password'], 
                            $requestUrl, self::$challenges[$key]
                        );
                        $this->serverChallenge =& self::$challenges[$key];
                        break;
                    }
                }
                break;

            default:
                throw new HTTP_Request2_Exception(
                    "Unknown HTTP authentication scheme '{$auth['scheme']}'"
                );
        }
    }

   /**
    * Adds 'Proxy-Authorization' header (if needed) to request headers array
    *
    * @param    array   request headers
    * @param    string  request URL (needed for digest authentication)
    * @throws   HTTP_Request2_Exception
    */
    protected function addProxyAuthorizationHeader(&$headers, $requestUrl)
    {
        if (!$this->request->getConfig('proxy_host') ||
            !($user = $this->request->getConfig('proxy_user')) ||
            (0 == strcasecmp('https', $this->request->getUrl()->getScheme()) &&
             HTTP_Request2::METHOD_CONNECT != $this->request->getMethod())
        ) {
            return;
        }

        $password = $this->request->getConfig('proxy_password');
        switch ($this->request->getConfig('proxy_auth_scheme')) {
            case HTTP_Request2::AUTH_BASIC:
                $headers['proxy-authorization'] =
                    'Basic ' . base64_encode($user . ':' . $password);
                break;

            case HTTP_Request2::AUTH_DIGEST:
                unset($this->proxyChallenge);
                $proxyUrl = 'proxy://' . $this->request->getConfig('proxy_host') .
                            ':' . $this->request->getConfig('proxy_port');
                if (!empty(self::$challenges[$proxyUrl])) {
                    $headers['proxy-authorization'] = $this->createDigestResponse(
                        $user, $password,
                        $requestUrl, self::$challenges[$proxyUrl]
                    );
                    $this->proxyChallenge =& self::$challenges[$proxyUrl];
                }
                break;

            default:
                throw new HTTP_Request2_Exception(
                    "Unknown HTTP authentication scheme '" .
                    $this->request->getConfig('proxy_auth_scheme') . "'"
                );
        }
    }


   /**
    * Creates the string with the Request-Line and request headers
    *
    * @return   string
    * @throws   HTTP_Request2_Exception
    */
    protected function prepareHeaders()
    {
        $headers = $this->request->getHeaders();
        $url     = $this->request->getUrl();
        $connect = HTTP_Request2::METHOD_CONNECT == $this->request->getMethod();
        $host    = $url->getHost();

        $defaultPort = 0 == strcasecmp($url->getScheme(), 'https')? 443: 80;
        if (($port = $url->getPort()) && $port != $defaultPort || $connect) {
            $host .= ':' . (empty($port)? $defaultPort: $port);
        }
        // Do not overwrite explicitly set 'Host' header, see bug #16146
        if (!isset($headers['host'])) {
            $headers['host'] = $host;
        }

        if ($connect) {
            $requestUrl = $host;

        } else {
            if (!$this->request->getConfig('proxy_host') ||
                0 == strcasecmp($url->getScheme(), 'https')
            ) {
                $requestUrl = '';
            } else {
                $requestUrl = $url->getScheme() . '://' . $host;
            }
            $path        = $url->getPath();
            $query       = $url->getQuery();
            $requestUrl .= (empty($path)? '/': $path) . (empty($query)? '': '?' . $query);
        }

        if ('1.1' == $this->request->getConfig('protocol_version') &&
            extension_loaded('zlib') && !isset($headers['accept-encoding'])
        ) {
            $headers['accept-encoding'] = 'gzip, deflate';
        }

        $this->addAuthorizationHeader($headers, $host, $requestUrl);
        $this->addProxyAuthorizationHeader($headers, $requestUrl);
        $this->calculateRequestLength($headers);

        $headersStr = $this->request->getMethod() . ' ' . $requestUrl . ' HTTP/' .
                      $this->request->getConfig('protocol_version') . "\r\n";
        foreach ($headers as $name => $value) {
            $canonicalName = implode('-', array_map('ucfirst', explode('-', $name)));
            $headersStr   .= $canonicalName . ': ' . $value . "\r\n";
        }
        return $headersStr . "\r\n";
    }

   /**
    * Sends the request body
    *
    * @throws   HTTP_Request2_Exception
    */
    protected function writeBody()
    {
        if (in_array($this->request->getMethod(), self::$bodyDisallowed) ||
            0 == $this->contentLength
        ) {
            return;
        }

        $position   = 0;
        $bufferSize = $this->request->getConfig('buffer_size');
        while ($position < $this->contentLength) {
            if (is_string($this->requestBody)) {
                $str = substr($this->requestBody, $position, $bufferSize);
            } elseif (is_resource($this->requestBody)) {
                $str = fread($this->requestBody, $bufferSize);
            } else {
                $str = $this->requestBody->read($bufferSize);
            }
            if (false === @fwrite($this->socket, $str, strlen($str))) {
                throw new HTTP_Request2_Exception('Error writing request');
            }
            // Provide the length of written string to the observer, request #7630
            $this->request->setLastEvent('sentBodyPart', strlen($str));
            $position += strlen($str); 
        }
    }

   /**
    * Reads the remote server's response
    *
    * @return   HTTP_Request2_Response
    * @throws   HTTP_Request2_Exception
    */
    protected function readResponse()
    {
        $bufferSize = $this->request->getConfig('buffer_size');

        do {
            $response = new HTTP_Request2_Response($this->readLine($bufferSize), true);
            do {
                $headerLine = $this->readLine($bufferSize);
                $response->parseHeaderLine($headerLine);
            } while ('' != $headerLine);
        } while (in_array($response->getStatus(), array(100, 101)));

        $this->request->setLastEvent('receivedHeaders', $response);

        // No body possible in such responses
        if (HTTP_Request2::METHOD_HEAD == $this->request->getMethod() ||
            (HTTP_Request2::METHOD_CONNECT == $this->request->getMethod() &&
             200 <= $response->getStatus() && 300 > $response->getStatus()) ||
            in_array($response->getStatus(), array(204, 304))
        ) {
            return $response;
        }

        $chunked = 'chunked' == $response->getHeader('transfer-encoding');
        $length  = $response->getHeader('content-length');
        $hasBody = false;
        if ($chunked || null === $length || 0 < intval($length)) {
            // RFC 2616, section 4.4:
            // 3. ... If a message is received with both a
            // Transfer-Encoding header field and a Content-Length header field,
            // the latter MUST be ignored.
            $toRead = ($chunked || null === $length)? null: $length;
            $this->chunkLength = 0;

            while (!feof($this->socket) && (is_null($toRead) || 0 < $toRead)) {
                if ($chunked) {
                    $data = $this->readChunked($bufferSize);
                } elseif (is_null($toRead)) {
                    $data = $this->fread($bufferSize);
                } else {
                    $data    = $this->fread(min($toRead, $bufferSize));
                    $toRead -= strlen($data);
                }
                if ('' == $data && (!$this->chunkLength || feof($this->socket))) {
                    break;
                }

                $hasBody = true;
                if ($this->request->getConfig('store_body')) {
                    $response->appendBody($data);
                }
                if (!in_array($response->getHeader('content-encoding'), array('identity', null))) {
                    $this->request->setLastEvent('receivedEncodedBodyPart', $data);
                } else {
                    $this->request->setLastEvent('receivedBodyPart', $data);
                }
            }
        }

        if ($hasBody) {
            $this->request->setLastEvent('receivedBody', $response);
        }
        return $response;
    }

   /**
    * Reads until either the end of the socket or a newline, whichever comes first 
    *
    * Strips the trailing newline from the returned data, handles global 
    * request timeout. Method idea borrowed from Net_Socket PEAR package. 
    *
    * @param    int     buffer size to use for reading
    * @return   Available data up to the newline (not including newline)
    * @throws   HTTP_Request2_Exception     In case of timeout
    */
    protected function readLine($bufferSize)
    {
        $line = '';
        while (!feof($this->socket)) {
            if ($this->timeout) {
                stream_set_timeout($this->socket, max($this->timeout - time(), 1));
            }
            $line .= @fgets($this->socket, $bufferSize);
            $info  = stream_get_meta_data($this->socket);
            if ($info['timed_out'] || $this->timeout && time() > $this->timeout) {
                throw new HTTP_Request2_Exception(
                    'Request timed out after ' . 
                    $this->request->getConfig('timeout') . ' second(s)'
                );
            }
            if (substr($line, -1) == "\n") {
                return rtrim($line, "\r\n");
            }
        }
        return $line;
    }

   /**
    * Wrapper around fread(), handles global request timeout
    *
    * @param    int     Reads up to this number of bytes
    * @return   Data read from socket
    * @throws   HTTP_Request2_Exception     In case of timeout
    */
    protected function fread($length)
    {
        if ($this->timeout) {
            stream_set_timeout($this->socket, max($this->timeout - time(), 1));
        }
        $data = fread($this->socket, $length);
        $info = stream_get_meta_data($this->socket);
        if ($info['timed_out'] || $this->timeout && time() > $this->timeout) {
            throw new HTTP_Request2_Exception(
                'Request timed out after ' . 
                $this->request->getConfig('timeout') . ' second(s)'
            );
        }
        return $data;
    }

   /**
    * Reads a part of response body encoded with chunked Transfer-Encoding
    *
    * @param    int     buffer size to use for reading
    * @return   string
    * @throws   HTTP_Request2_Exception
    */
    protected function readChunked($bufferSize)
    {
        // at start of the next chunk?
        if (0 == $this->chunkLength) {
            $line = $this->readLine($bufferSize);
            if (!preg_match('/^([0-9a-f]+)/i', $line, $matches)) {
                throw new HTTP_Request2_Exception(
                    "Cannot decode chunked response, invalid chunk length '{$line}'"
                );
            } else {
                $this->chunkLength = hexdec($matches[1]);
                // Chunk with zero length indicates the end
                if (0 == $this->chunkLength) {
                    $this->readLine($bufferSize);
                    return '';
                }
            }
        }
        $data = $this->fread(min($this->chunkLength, $bufferSize));
        $this->chunkLength -= strlen($data);
        if (0 == $this->chunkLength) {
            $this->readLine($bufferSize); // Trailing CRLF
        }
        return $data;
    }
}

?>