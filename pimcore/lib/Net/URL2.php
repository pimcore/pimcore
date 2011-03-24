<?php
// +-----------------------------------------------------------------------+
// | Copyright (c) 2007-2008, Christian Schmidt, Peytz & Co. A/S           |
// | All rights reserved.                                                  |
// |                                                                       |
// | Redistribution and use in source and binary forms, with or without    |
// | modification, are permitted provided that the following conditions    |
// | are met:                                                              |
// |                                                                       |
// | o Redistributions of source code must retain the above copyright      |
// |   notice, this list of conditions and the following disclaimer.       |
// | o Redistributions in binary form must reproduce the above copyright   |
// |   notice, this list of conditions and the following disclaimer in the |
// |   documentation and/or other materials provided with the distribution.|
// | o The names of the authors may not be used to endorse or promote      |
// |   products derived from this software without specific prior written  |
// |   permission.                                                         |
// |                                                                       |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS   |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT     |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR |
// | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT  |
// | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, |
// | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT      |
// | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
// | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY |
// | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT   |
// | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE |
// | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.  |
// |                                                                       |
// +-----------------------------------------------------------------------+
// | Author: Christian Schmidt <schmidt at php dot net>                    |
// +-----------------------------------------------------------------------+
//
// $Id: URL2.php,v 1.10 2008/04/26 21:57:08 schmidt Exp $
//
// Net_URL2 Class (PHP5 Only)

// This code is released under the BSD License - http://www.opensource.org/licenses/bsd-license.php
/**
 * @license BSD License
 */
class Net_URL2
{
    /**
     * Do strict parsing in resolve() (see RFC 3986, section 5.2.2). Default
     * is true.
     */
    const OPTION_STRICT           = 'strict';

    /**
     * Represent arrays in query using PHP's [] notation. Default is true.
     */
    const OPTION_USE_BRACKETS     = 'use_brackets';

    /**
     * URL-encode query variable keys. Default is true.
     */
    const OPTION_ENCODE_KEYS      = 'encode_keys';

    /**
     * Query variable separators when parsing the query string. Every character
     * is considered a separator. Default is specified by the
     * arg_separator.input php.ini setting (this defaults to "&").
     */
    const OPTION_SEPARATOR_INPUT  = 'input_separator';

    /**
     * Query variable separator used when generating the query string. Default
     * is specified by the arg_separator.output php.ini setting (this defaults
     * to "&").
     */
    const OPTION_SEPARATOR_OUTPUT = 'output_separator';

    /**
     * Default options corresponds to how PHP handles $_GET.
     */
    private $options = array(
        self::OPTION_STRICT           => true,
        self::OPTION_USE_BRACKETS     => true,
        self::OPTION_ENCODE_KEYS      => true,
        self::OPTION_SEPARATOR_INPUT  => 'x&',
        self::OPTION_SEPARATOR_OUTPUT => 'x&',
        );

    /**
     * @var  string|bool
     */
    private $scheme = false;

    /**
     * @var  string|bool
     */
    private $userinfo = false;

    /**
     * @var  string|bool
     */
    private $host = false;

    /**
     * @var  int|bool
     */
    private $port = false;

    /**
     * @var  string
     */
    private $path = '';

    /**
     * @var  string|bool
     */
    private $query = false;

    /**
     * @var  string|bool
     */
    private $fragment = false;

    /**
     * @param string $url     an absolute or relative URL
     * @param array  $options
     */
    public function __construct($url, $options = null)
    {
        $this->setOption(self::OPTION_SEPARATOR_INPUT,
                         ini_get('arg_separator.input'));
        $this->setOption(self::OPTION_SEPARATOR_OUTPUT,
                         ini_get('arg_separator.output'));
        if (is_array($options)) {
            foreach ($options as $optionName => $value) {
                $this->setOption($optionName);
            }
        }

        if (preg_match('@^([a-z][a-z0-9.+-]*):@i', $url, $reg)) {
            $this->scheme = $reg[1];
            $url = substr($url, strlen($reg[0]));
        }

        if (preg_match('@^//([^/#?]+)@', $url, $reg)) {
            $this->setAuthority($reg[1]);
            $url = substr($url, strlen($reg[0]));
        }

        $i = strcspn($url, '?#');
        $this->path = substr($url, 0, $i);
        $url = substr($url, $i);

        if (preg_match('@^\?([^#]*)@', $url, $reg)) {
            $this->query = $reg[1];
            $url = substr($url, strlen($reg[0]));
        }

        if ($url) {
            $this->fragment = substr($url, 1);
        }
    }

    /**
     * Returns the scheme, e.g. "http" or "urn", or false if there is no
     * scheme specified, i.e. if this is a relative URL.
     *
     * @return  string|bool
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @param string|bool $scheme
     *
     * @return void
     * @see    getScheme()
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
    }

    /**
     * Returns the user part of the userinfo part (the part preceding the first
     *  ":"), or false if there is no userinfo part.
     *
     * @return  string|bool
     */
    public function getUser()
    {
        return $this->userinfo !== false ? preg_replace('@:.*$@', '', $this->userinfo) : false;
    }

    /**
     * Returns the password part of the userinfo part (the part after the first
     *  ":"), or false if there is no userinfo part (i.e. the URL does not
     * contain "@" in front of the hostname) or the userinfo part does not
     * contain ":".
     *
     * @return  string|bool
     */
    public function getPassword()
    {
        return $this->userinfo !== false ? substr(strstr($this->userinfo, ':'), 1) : false;
    }

    /**
     * Returns the userinfo part, or false if there is none, i.e. if the
     * authority part does not contain "@".
     *
     * @return  string|bool
     */
    public function getUserinfo()
    {
        return $this->userinfo;
    }

    /**
     * Sets the userinfo part. If two arguments are passed, they are combined
     * in the userinfo part as username ":" password.
     *
     * @param string|bool $userinfo userinfo or username
     * @param string|bool $password
     *
     * @return void
     */
    public function setUserinfo($userinfo, $password = false)
    {
        $this->userinfo = $userinfo;
        if ($password !== false) {
            $this->userinfo .= ':' . $password;
        }
    }

    /**
     * Returns the host part, or false if there is no authority part, e.g.
     * relative URLs.
     *
     * @return  string|bool
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string|bool $host
     *
     * @return void
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * Returns the port number, or false if there is no port number specified,
     * i.e. if the default port is to be used.
     *
     * @return  int|bool
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int|bool $port
     *
     * @return void
     */
    public function setPort($port)
    {
        $this->port = intval($port);
    }

    /**
     * Returns the authority part, i.e. [ userinfo "@" ] host [ ":" port ], or
     * false if there is no authority none.
     *
     * @return string|bool
     */
    public function getAuthority()
    {
        if (!$this->host) {
            return false;
        }

        $authority = '';

        if ($this->userinfo !== false) {
            $authority .= $this->userinfo . '@';
        }

        $authority .= $this->host;

        if ($this->port !== false) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    /**
     * @param string|false $authority
     *
     * @return void
     */
    public function setAuthority($authority)
    {
        $this->user = false;
        $this->pass = false;
        $this->host = false;
        $this->port = false;
        if (preg_match('@^(([^\@]+)\@)?([^:]+)(:(\d*))?$@', $authority, $reg)) {
            if ($reg[1]) {
                $this->userinfo = $reg[2];
            }

            $this->host = $reg[3];
            if (isset($reg[5])) {
                $this->port = intval($reg[5]);
            }
        }
    }

    /**
     * Returns the path part (possibly an empty string).
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * @return void
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Returns the query string (excluding the leading "?"), or false if "?"
     * isn't present in the URL.
     *
     * @return  string|bool
     * @see     self::getQueryVariables()
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param string|bool $query
     *
     * @return void
     * @see   self::setQueryVariables()
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * Returns the fragment name, or false if "#" isn't present in the URL.
     *
     * @return  string|bool
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * @param string|bool $fragment
     *
     * @return void
     */
    public function setFragment($fragment)
    {
        $this->fragment = $fragment;
    }

    /**
     * Returns the query string like an array as the variables would appear in
     * $_GET in a PHP script.
     *
     * @return  array
     */
    public function getQueryVariables()
    {
        $pattern = '/[' .
                   preg_quote($this->getOption(self::OPTION_SEPARATOR_INPUT), '/') .
                   ']/';
        $parts   = preg_split($pattern, $this->query, -1, PREG_SPLIT_NO_EMPTY);
        $return  = array();

        foreach ($parts as $part) {
            if (strpos($part, '=') !== false) {
                list($key, $value) = explode('=', $part, 2);
            } else {
                $key   = $part;
                $value = null;
            }

            if ($this->getOption(self::OPTION_ENCODE_KEYS)) {
                $key = rawurldecode($key);
            }
            $value = rawurldecode($value);

            if ($this->getOption(self::OPTION_USE_BRACKETS) &&
                preg_match('#^(.*)\[([0-9a-z_-]*)\]#i', $key, $matches)) {

                $key = $matches[1];
                $idx = $matches[2];

                // Ensure is an array
                if (empty($return[$key]) || !is_array($return[$key])) {
                    $return[$key] = array();
                }

                // Add data
                if ($idx === '') {
                    $return[$key][] = $value;
                } else {
                    $return[$key][$idx] = $value;
                }
            } elseif (!$this->getOption(self::OPTION_USE_BRACKETS)
                      && !empty($return[$key])
            ) {
                $return[$key]   = (array) $return[$key];
                $return[$key][] = $value;
            } else {
                $return[$key] = $value;
            }
        }

        return $return;
    }

    /**
     * @param array $array (name => value) array
     *
     * @return void
     */
    public function setQueryVariables(array $array)
    {
        if (!$array) {
            $this->query = false;
        } else {
            foreach ($array as $name => $value) {
                if ($this->getOption(self::OPTION_ENCODE_KEYS)) {
                    $name = rawurlencode($name);
                }

                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $parts[] = $this->getOption(self::OPTION_USE_BRACKETS)
                            ? sprintf('%s[%s]=%s', $name, $k, $v)
                            : ($name . '=' . $v);
                    }
                } elseif (!is_null($value)) {
                    $parts[] = $name . '=' . $value;
                } else {
                    $parts[] = $name;
                }
            }
            $this->query = implode($this->getOption(self::OPTION_SEPARATOR_OUTPUT),
                                   $parts);
        }
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return  array
     */
    public function setQueryVariable($name, $value)
    {
        $array = $this->getQueryVariables();
        $array[$name] = $value;
        $this->setQueryVariables($array);
    }

    /**
     * @param string $name
     *
     * @return void
     */
    public function unsetQueryVariable($name)
    {
        $array = $this->getQueryVariables();
        unset($array[$name]);
        $this->setQueryVariables($array);
    }

    /**
     * Returns a string representation of this URL.
     *
     * @return  string
     */
    public function getURL()
    {
        // See RFC 3986, section 5.3
        $url = "";

        if ($this->scheme !== false) {
            $url .= $this->scheme . ':';
        }

        $authority = $this->getAuthority();
        if ($authority !== false) {
            $url .= '//' . $authority;
        }
        $url .= $this->path;

        if ($this->query !== false) {
            $url .= '?' . $this->query;
        }

        if ($this->fragment !== false) {
            $url .= '#' . $this->fragment;
        }
    
        return $url;
    }

    /** 
     * Returns a normalized string representation of this URL. This is useful
     * for comparison of URLs.
     *
     * @return  string
     */
    public function getNormalizedURL()
    {
        $url = clone $this;
        $url->normalize();
        return $url->getUrl();
    }

    /** 
     * Returns a normalized Net_URL2 instance.
     *
     * @return  Net_URL2
     */
    public function normalize()
    {
        // See RFC 3886, section 6

        // Schemes are case-insensitive
        if ($this->scheme) {
            $this->scheme = strtolower($this->scheme);
        }

        // Hostnames are case-insensitive
        if ($this->host) {
            $this->host = strtolower($this->host);
        }

        // Remove default port number for known schemes (RFC 3986, section 6.2.3)
        if ($this->port &&
            $this->scheme &&
            $this->port == getservbyname($this->scheme, 'tcp')) {

            $this->port = false;
        }

        // Normalize case of %XX percentage-encodings (RFC 3986, section 6.2.2.1)
        foreach (array('userinfo', 'host', 'path') as $part) {
            if ($this->$part) {
                $this->$part  = preg_replace('/%[0-9a-f]{2}/ie', 'strtoupper("\0")', $this->$part);
            }
        }

        // Path segment normalization (RFC 3986, section 6.2.2.3)
        $this->path = self::removeDotSegments($this->path);

        // Scheme based normalization (RFC 3986, section 6.2.3)
        if ($this->host && !$this->path) {
            $this->path = '/';
        }
    }

    /**
     * Returns whether this instance represents an absolute URL.
     *
     * @return  bool
     */
    public function isAbsolute()
    {
        return (bool) $this->scheme;
    }

    /**
     * Returns an Net_URL2 instance representing an absolute URL relative to
     * this URL.
     *
     * @param Net_URL2|string $reference relative URL
     *
     * @return Net_URL2
     */
    public function resolve($reference)
    {
        if (is_string($reference)) {
            $reference = new self($reference);
        }
        if (!$this->isAbsolute()) {
            throw new Exception('Base-URL must be absolute');
        }

        // A non-strict parser may ignore a scheme in the reference if it is
        // identical to the base URI's scheme.
        if (!$this->getOption(self::OPTION_STRICT) && $reference->scheme == $this->scheme) {
            $reference->scheme = false;
        }

        $target = new self('');
        if ($reference->scheme !== false) {
            $target->scheme = $reference->scheme;
            $target->setAuthority($reference->getAuthority());
            $target->path  = self::removeDotSegments($reference->path);
            $target->query = $reference->query;
        } else {
            $authority = $reference->getAuthority();
            if ($authority !== false) {
                $target->setAuthority($authority);
                $target->path  = self::removeDotSegments($reference->path);
                $target->query = $reference->query;
            } else {
                if ($reference->path == '') {
                    $target->path = $this->path;
                    if ($reference->query !== false) {
                        $target->query = $reference->query;
                    } else {
                        $target->query = $this->query;
                    }
                } else {
                    if (substr($reference->path, 0, 1) == '/') {
                        $target->path = self::removeDotSegments($reference->path);
                    } else {
                        // Merge paths (RFC 3986, section 5.2.3)
                        if ($this->host !== false && $this->path == '') {
                            $target->path = '/' . $this->path;
                        } else {
                            $i = strrpos($this->path, '/');
                            if ($i !== false) {
                                $target->path = substr($this->path, 0, $i + 1);
                            }
                            $target->path .= $reference->path;
                        }
                        $target->path = self::removeDotSegments($target->path);
                    }
                    $target->query = $reference->query;
                }
                $target->setAuthority($this->getAuthority());
            }
            $target->scheme = $this->scheme;
        }

        $target->fragment = $reference->fragment;

        return $target;
    }

    /**
     * Removes dots as described in RFC 3986, section 5.2.4, e.g.
     * "/foo/../bar/baz" => "/bar/baz"
     *
     * @param string $path a path
     *
     * @return string a path
     */
    private static function removeDotSegments($path)
    {
        $output = '';

        // Make sure not to be trapped in an infinite loop due to a bug in this
        // method
        $j = 0; 
        while ($path && $j++ < 100) {
            // Step A
            if (substr($path, 0, 2) == './') {
                $path = substr($path, 2);
            } elseif (substr($path, 0, 3) == '../') {
                $path = substr($path, 3);

            // Step B
            } elseif (substr($path, 0, 3) == '/./' || $path == '/.') {
                $path = '/' . substr($path, 3);

            // Step C
            } elseif (substr($path, 0, 4) == '/../' || $path == '/..') {
                $path = '/' . substr($path, 4);
                $i = strrpos($output, '/');
                $output = $i === false ? '' : substr($output, 0, $i);

            // Step D
            } elseif ($path == '.' || $path == '..') {
                $path = '';

            // Step E
            } else {
                $i = strpos($path, '/');
                if ($i === 0) {
                    $i = strpos($path, '/', 1);
                }
                if ($i === false) {
                    $i = strlen($path);
                }
                $output .= substr($path, 0, $i);
                $path = substr($path, $i);
            }
        }

        return $output;
    }

    /**
     * Returns a Net_URL2 instance representing the canonical URL of the
     * currently executing PHP script.
     * 
     * @return  string
     */
    public static function getCanonical()
    {
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            // ALERT - no current URL
            throw new Exception('Script was not called through a webserver');
        }

        // Begin with a relative URL
        $url = new self($_SERVER['PHP_SELF']);
        $url->scheme = isset($_SERVER['HTTPS']) ? 'https' : 'http';
        $url->host = $_SERVER['SERVER_NAME'];
        $port = intval($_SERVER['SERVER_PORT']);
        if ($url->scheme == 'http' && $port != 80 ||
            $url->scheme == 'https' && $port != 443) {

            $url->port = $port;
        }
        return $url;
    }

    /**
     * Returns the URL used to retrieve the current request.
     *
     * @return  string
     */
    public static function getRequestedURL()
    {
        return self::getRequested()->getUrl();
    }

    /**
     * Returns a Net_URL2 instance representing the URL used to retrieve the
     * current request.
     *
     * @return  Net_URL2
     */
    public static function getRequested()
    {
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            // ALERT - no current URL
            throw new Exception('Script was not called through a webserver');
        }

        // Begin with a relative URL
        $url = new self($_SERVER['REQUEST_URI']);
        $url->scheme = isset($_SERVER['HTTPS']) ? 'https' : 'http';
        // Set host and possibly port
        $url->setAuthority($_SERVER['HTTP_HOST']);
        return $url;
    }

    /**
     * Sets the specified option.
     *
     * @param string $optionName a self::OPTION_ constant
     * @param mixed  $value      option value  
     *
     * @return void
     * @see  self::OPTION_STRICT
     * @see  self::OPTION_USE_BRACKETS
     * @see  self::OPTION_ENCODE_KEYS
     */
    function setOption($optionName, $value)
    {
        if (!array_key_exists($optionName, $this->options)) {
            return false;
        }
        $this->options[$optionName] = $value;
    }

    /**
     * Returns the value of the specified option.
     *
     * @param string $optionName The name of the option to retrieve
     *
     * @return  mixed
     */
    function getOption($optionName)
    {
        return isset($this->options[$optionName])
            ? $this->options[$optionName] : false;
    }
}
