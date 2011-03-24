<?php
/**
 * Class representing a HTTP response
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
 * @version    CVS: $Id: Response.php,v 1.3 2009/01/06 21:30:39 avb Exp $
 * @link       http://pear.php.net/package/HTTP_Request2
 */

/**
 * Exception class for HTTP_Request2 package
 */ 
require_once 'HTTP/Request2/Exception.php';

/**
 * Class representing a HTTP response
 *
 * The class is designed to be used in "streaming" scenario, building the
 * response as it is being received:
 * <code>
 * $statusLine = read_status_line();
 * $response = new HTTP_Request2_Response($statusLine);
 * do {
 *     $headerLine = read_header_line();
 *     $response->parseHeaderLine($headerLine);
 * } while ($headerLine != '');
 * 
 * while ($chunk = read_body()) {
 *     $response->appendBody($chunk);
 * }
 * 
 * var_dump($response->getHeader(), $response->getCookies(), $response->getBody());
 * </code>
 *
 *
 * @category   HTTP
 * @package    HTTP_Request2
 * @author     Alexey Borzov <avb@php.net>
 * @version    Release: 0.4.0
 * @link       http://tools.ietf.org/html/rfc2616#section-6
 */
class HTTP_Request2_Response
{
   /**
    * HTTP protocol version (e.g. 1.0, 1.1)
    * @var  string
    */
    protected $version;

   /**
    * Status code
    * @var  integer
    * @link http://tools.ietf.org/html/rfc2616#section-6.1.1
    */
    protected $code;

   /**
    * Reason phrase
    * @var  string
    * @link http://tools.ietf.org/html/rfc2616#section-6.1.1
    */
    protected $reasonPhrase;

   /**
    * Associative array of response headers
    * @var  array
    */
    protected $headers = array();

   /**
    * Cookies set in the response
    * @var  array
    */
    protected $cookies = array();

   /**
    * Name of last header processed by parseHederLine()
    *
    * Used to handle the headers that span multiple lines
    *
    * @var  string
    */
    protected $lastHeader = null;

   /**
    * Response body
    * @var  string
    */
    protected $body = '';

   /**
    * Whether the body is still encoded by Content-Encoding
    *
    * cURL provides the decoded body to the callback; if we are reading from
    * socket the body is still gzipped / deflated
    *
    * @var  bool
    */
    protected $bodyEncoded;

   /**
    * Associative array of HTTP status code / reason phrase.
    *
    * @var  array
    * @link http://tools.ietf.org/html/rfc2616#section-10
    */
    protected static $phrases = array(

        // 1xx: Informational - Request received, continuing process
        100 => 'Continue',
        101 => 'Switching Protocols',

        // 2xx: Success - The action was successfully received, understood and
        // accepted
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        // 3xx: Redirection - Further action must be taken in order to complete
        // the request
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',  // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',

        // 4xx: Client Error - The request contains bad syntax or cannot be 
        // fulfilled
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',

        // 5xx: Server Error - The server failed to fulfill an apparently
        // valid request
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded',

    );

   /**
    * Constructor, parses the response status line
    *
    * @param    string  Response status line (e.g. "HTTP/1.1 200 OK")
    * @param    bool    Whether body is still encoded by Content-Encoding
    * @throws   HTTP_Request2_Exception if status line is invalid according to spec
    */
    public function __construct($statusLine, $bodyEncoded = true)
    {
        if (!preg_match('!^HTTP/(\d\.\d) (\d{3})(?: (.+))?!', $statusLine, $m)) {
            throw new HTTP_Request2_Exception("Malformed response: {$statusLine}");
        }
        $this->version = $m[1];
        $this->code    = intval($m[2]);
        if (!empty($m[3])) {
            $this->reasonPhrase = trim($m[3]);
        } elseif (!empty(self::$phrases[$this->code])) {
            $this->reasonPhrase = self::$phrases[$this->code];
        }
        $this->bodyEncoded = (bool)$bodyEncoded;
    }

   /**
    * Parses the line from HTTP response filling $headers array
    *
    * The method should be called after reading the line from socket or receiving 
    * it into cURL callback. Passing an empty string here indicates the end of
    * response headers and triggers additional processing, so be sure to pass an
    * empty string in the end.
    *
    * @param    string  Line from HTTP response
    */
    public function parseHeaderLine($headerLine)
    {
        $headerLine = trim($headerLine, "\r\n");

        // empty string signals the end of headers, process the received ones
        if ('' == $headerLine) {
            if (!empty($this->headers['set-cookie'])) {
                $cookies = is_array($this->headers['set-cookie'])?
                           $this->headers['set-cookie']:
                           array($this->headers['set-cookie']);
                foreach ($cookies as $cookieString) {
                    $this->parseCookie($cookieString);
                }
                unset($this->headers['set-cookie']);
            }
            foreach (array_keys($this->headers) as $k) {
                if (is_array($this->headers[$k])) {
                    $this->headers[$k] = implode(', ', $this->headers[$k]);
                }
            }

        // string of the form header-name: header value
        } elseif (preg_match('!^([^\x00-\x1f\x7f-\xff()<>@,;:\\\\"/\[\]?={}\s]+):(.+)$!', $headerLine, $m)) {
            $name  = strtolower($m[1]);
            $value = trim($m[2]);
            if (empty($this->headers[$name])) {
                $this->headers[$name] = $value;
            } else {
                if (!is_array($this->headers[$name])) {
                    $this->headers[$name] = array($this->headers[$name]);
                }
                $this->headers[$name][] = $value;
            }
            $this->lastHeader = $name;

        // string 
        } elseif (preg_match('!^\s+(.+)$!', $headerLine, $m) && $this->lastHeader) {
            if (!is_array($this->headers[$this->lastHeader])) {
                $this->headers[$this->lastHeader] .= ' ' . trim($m[1]);
            } else {
                $key = count($this->headers[$this->lastHeader]) - 1;
                $this->headers[$this->lastHeader][$key] .= ' ' . trim($m[1]);
            }
        }
    } 

   /**
    * Parses a Set-Cookie header to fill $cookies array
    *
    * @param    string    value of Set-Cookie header
    * @link     http://cgi.netscape.com/newsref/std/cookie_spec.html
    */
    protected function parseCookie($cookieString)
    {
        $cookie = array(
            'expires' => null,
            'domain'  => null,
            'path'    => null,
            'secure'  => false
        );

        // Only a name=value pair
        if (!strpos($cookieString, ';')) {
            $pos = strpos($cookieString, '=');
            $cookie['name']  = trim(substr($cookieString, 0, $pos));
            $cookie['value'] = trim(substr($cookieString, $pos + 1));

        // Some optional parameters are supplied
        } else {
            $elements = explode(';', $cookieString);
            $pos = strpos($elements[0], '=');
            $cookie['name']  = trim(substr($elements[0], 0, $pos));
            $cookie['value'] = trim(substr($elements[0], $pos + 1));

            for ($i = 1; $i < count($elements); $i++) {
                if (false === strpos($elements[$i], '=')) {
                    $elName  = trim($elements[$i]);
                    $elValue = null;
                } else {
                    list ($elName, $elValue) = array_map('trim', explode('=', $elements[$i]));
                }
                $elName = strtolower($elName);
                if ('secure' == $elName) {
                    $cookie['secure'] = true;
                } elseif ('expires' == $elName) {
                    $cookie['expires'] = str_replace('"', '', $elValue);
                } elseif ('path' == $elName || 'domain' == $elName) {
                    $cookie[$elName] = urldecode($elValue);
                } else {
                    $cookie[$elName] = $elValue;
                }
            }
        }
        $this->cookies[] = $cookie;
    }

   /**
    * Appends a string to the response body
    * @param    string
    */
    public function appendBody($bodyChunk)
    {
        $this->body .= $bodyChunk;
    }

   /**
    * Returns the status code
    * @return   integer 
    */
    public function getStatus()
    {
        return $this->code;
    }

   /**
    * Returns the reason phrase
    * @return   string
    */
    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

   /**
    * Returns either the named header or all response headers
    *
    * @param    string          Name of header to return
    * @return   string|array    Value of $headerName header (null if header is
    *                           not present), array of all response headers if
    *                           $headerName is null
    */
    public function getHeader($headerName = null)
    {
        if (null === $headerName) {
            return $this->headers;
        } else {
            $headerName = strtolower($headerName);
            return isset($this->headers[$headerName])? $this->headers[$headerName]: null;
        }
    }

   /**
    * Returns cookies set in response
    *
    * @return   array
    */
    public function getCookies()
    {
        return $this->cookies;
    }

   /**
    * Returns the body of the response
    *
    * @return   string
    * @throws   HTTP_Request2_Exception if body cannot be decoded
    */
    public function getBody()
    {
        if ($this->bodyEncoded) {
            switch (strtolower($this->getHeader('content-encoding'))) {
                case 'gzip':
                    return self::decodeGzip($this->body);
                case 'deflate':
                    return self::decodeDeflate($this->body);
            }
        }
        return $this->body;
    }

   /**
    * Get the HTTP version of the response
    *
    * @return   string
    */ 
    public function getVersion()
    {
        return $this->version;
    }

   /**
    * Decodes the message-body encoded by gzip
    *
    * The real decoding work is done by gzinflate() built-in function, this
    * method only parses the header and checks data for compliance with
    * RFC 1952
    *
    * @param    string  gzip-encoded data
    * @return   string  decoded data
    * @throws   HTTP_Request2_Exception
    * @link     http://tools.ietf.org/html/rfc1952
    */
    public static function decodeGzip($data)
    {
        $length = strlen($data);
        // If it doesn't look like gzip-encoded data, don't bother
        if (18 > $length || strcmp(substr($data, 0, 2), "\x1f\x8b")) {
            return $data;
        }
        if (!function_exists('gzinflate')) {
            throw new HTTP_Request2_Exception('Unable to decode body: gzip extension not available');
        }
        $method = ord(substr($data, 2, 1));
        if (8 != $method) {
            throw new HTTP_Request2_Exception('Error parsing gzip header: unknown compression method');
        }
        $flags = ord(substr($data, 3, 1));
        if ($flags & 224) {
            throw new HTTP_Request2_Exception('Error parsing gzip header: reserved bits are set');
        }

        // header is 10 bytes minimum. may be longer, though.
        $headerLength = 10;
        // extra fields, need to skip 'em
        if ($flags & 4) {
            if ($length - $headerLength - 2 < 8) {
                throw new HTTP_Request2_Exception('Error parsing gzip header: data too short');
            }
            $extraLength = unpack('v', substr($data, 10, 2));
            if ($length - $headerLength - 2 - $extraLength[1] < 8) {
                throw new HTTP_Request2_Exception('Error parsing gzip header: data too short');
            }
            $headerLength += $extraLength[1] + 2;
        }
        // file name, need to skip that
        if ($flags & 8) {
            if ($length - $headerLength - 1 < 8) {
                throw new HTTP_Request2_Exception('Error parsing gzip header: data too short');
            }
            $filenameLength = strpos(substr($data, $headerLength), chr(0));
            if (false === $filenameLength || $length - $headerLength - $filenameLength - 1 < 8) {
                throw new HTTP_Request2_Exception('Error parsing gzip header: data too short');
            }
            $headerLength += $filenameLength + 1;
        }
        // comment, need to skip that also
        if ($flags & 16) {
            if ($length - $headerLength - 1 < 8) {
                throw new HTTP_Request2_Exception('Error parsing gzip header: data too short');
            }
            $commentLength = strpos(substr($data, $headerLength), chr(0));
            if (false === $commentLength || $length - $headerLength - $commentLength - 1 < 8) {
                throw new HTTP_Request2_Exception('Error parsing gzip header: data too short');
            }
            $headerLength += $commentLength + 1;
        }
        // have a CRC for header. let's check
        if ($flags & 2) {
            if ($length - $headerLength - 2 < 8) {
                throw new HTTP_Request2_Exception('Error parsing gzip header: data too short');
            }
            $crcReal   = 0xffff & crc32(substr($data, 0, $headerLength));
            $crcStored = unpack('v', substr($data, $headerLength, 2));
            if ($crcReal != $crcStored[1]) {
                throw new HTTP_Request2_Exception('Header CRC check failed');
            }
            $headerLength += 2;
        }
        // unpacked data CRC and size at the end of encoded data
        $tmp = unpack('V2', substr($data, -8));
        $dataCrc  = $tmp[1];
        $dataSize = $tmp[2];

        // finally, call the gzinflate() function
        // don't pass $dataSize to gzinflate, see bugs #13135, #14370
        $unpacked = gzinflate(substr($data, $headerLength, -8));
        if (false === $unpacked) {
            throw new HTTP_Request2_Exception('gzinflate() call failed');
        } elseif ($dataSize != strlen($unpacked)) {
            throw new HTTP_Request2_Exception('Data size check failed');
        } elseif ((0xffffffff & $dataCrc) != (0xffffffff & crc32($unpacked))) {
            throw new HTTP_Request2_Exception('Data CRC check failed');
        }
        return $unpacked;
    }

   /**
    * Decodes the message-body encoded by deflate
    *
    * @param    string  deflate-encoded data
    * @return   string  decoded data
    * @throws   HTTP_Request2_Exception
    */
    public static function decodeDeflate($data)
    {
        if (!function_exists('gzuncompress')) {
            throw new HTTP_Request2_Exception('Unable to decode body: gzip extension not available');
        }
        // RFC 2616 defines 'deflate' encoding as zlib format from RFC 1950,
        // while many applications send raw deflate stream from RFC 1951.
        // We should check for presence of zlib header and use gzuncompress() or
        // gzinflate() as needed. See bug #15305
        $header = unpack('n', substr($data, 0, 2));
        return (0 == $header[1] % 31)? gzuncompress($data): gzinflate($data);
    }
}
?>