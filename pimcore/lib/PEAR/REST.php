<?php
/**
 * PEAR_REST
 *
 * PHP versions 4 and 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: REST.php,v 1.40 2009/03/26 23:12:46 dufuz Exp $
 * @link       http://pear.php.net/package/PEAR
 * @since      File available since Release 1.4.0a1
 */

/**
 * For downloading xml files
 */
require_once 'PEAR.php';
require_once 'PEAR/XMLParser.php';

/**
 * Intelligently retrieve data, following hyperlinks if necessary, and re-directing
 * as well
 * @category   pear
 * @package    PEAR
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    Release: 1.8.1
 * @link       http://pear.php.net/package/PEAR
 * @since      Class available since Release 1.4.0a1
 */
class PEAR_REST
{
    var $config;
    var $_options;

    function PEAR_REST(&$config, $options = array())
    {
        $this->config = &$config;
        $this->_options = $options;
    }

    /**
     * Retrieve REST data, but always retrieve the local cache if it is available.
     *
     * This is useful for elements that should never change, such as information on a particular
     * release
     * @param string full URL to this resource
     * @param array|false contents of the accept-encoding header
     * @param boolean     if true, xml will be returned as a string, otherwise, xml will be
     *                    parsed using PEAR_XMLParser
     * @return string|array
     */
    function retrieveCacheFirst($url, $accept = false, $forcestring = false, $channel = false)
    {

        $cachefile = $this->config->get('cache_dir') . DIRECTORY_SEPARATOR .
            md5($url) . 'rest.cachefile';

        if (file_exists($cachefile)) {
            return unserialize(implode('', file($cachefile)));
        }

        return $this->retrieveData($url, $accept, $forcestring, $channel);
    }

    /**
     * Retrieve a remote REST resource
     * @param string full URL to this resource
     * @param array|false contents of the accept-encoding header
     * @param boolean     if true, xml will be returned as a string, otherwise, xml will be
     *                    parsed using PEAR_XMLParser
     * @return string|array
     */
    function retrieveData($url, $accept = false, $forcestring = false, $channel = false)
    {
        $cacheId = $this->getCacheId($url);
        if ($ret = $this->useLocalCache($url, $cacheId)) {
            return $ret;
        }

        $file = $trieddownload = false;
        if (!isset($this->_options['offline'])) {
            $trieddownload = true;
            $file = $this->downloadHttp($url, $cacheId ? $cacheId['lastChange'] : false, $accept, $channel);
        }

        if (PEAR::isError($file)) {
            if ($file->getCode() !== -9276) {
                return $file;
            }

            $trieddownload = false;
            $file = false; // use local copy if available on socket connect error
        }

        if (!$file) {
            $ret = $this->getCache($url);
            if (!PEAR::isError($ret) && $trieddownload) {
                // reset the age of the cache if the server says it was unmodified
                $this->saveCache($url, $ret, null, true, $cacheId);
            }

            return $ret;
        }

        if (is_array($file)) {
            $headers      = $file[2];
            $lastmodified = $file[1];
            $content      = $file[0];
        } else {
            $headers      = array();
            $lastmodified = false;
            $content      = $file;
        }

        if ($forcestring) {
            $this->saveCache($url, $content, $lastmodified, false, $cacheId);
            return $content;
        }

        if (isset($headers['content-type'])) {
            switch ($headers['content-type']) {
                case 'text/xml' :
                case 'application/xml' :
                case 'text/plain' :
                    if ($headers['content-type'] === 'text/plain') {
                        $check = substr($content, 0, 5);
                        if ($check !== '<?xml') {
                            break;
                        }
                    }

                    $parser = new PEAR_XMLParser;
                    PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
                    $err = $parser->parse($content);
                    PEAR::popErrorHandling();
                    if (PEAR::isError($err)) {
                        return PEAR::raiseError('Invalid xml downloaded from "' . $url . '": ' .
                            $err->getMessage());
                    }
                    $content = $parser->getData();
                case 'text/html' :
                default :
                    // use it as a string
            }
        } else {
            // assume XML
            $parser = new PEAR_XMLParser;
            $parser->parse($content);
            $content = $parser->getData();
        }

        $this->saveCache($url, $content, $lastmodified, false, $cacheId);
        return $content;
    }

    function useLocalCache($url, $cacheid = null)
    {
        if ($cacheid === null) {
            $cacheidfile = $this->config->get('cache_dir') . DIRECTORY_SEPARATOR .
                md5($url) . 'rest.cacheid';
            if (!file_exists($cacheidfile)) {
                return false;
            }

            $cacheid = unserialize(implode('', file($cacheidfile)));
        }

        $cachettl = $this->config->get('cache_ttl');
        // If cache is newer than $cachettl seconds, we use the cache!
        if (time() - $cacheid['age'] < $cachettl) {
            return $this->getCache($url);
        }

        return false;
    }

    function getCacheId($url)
    {
        $cacheidfile = $this->config->get('cache_dir') . DIRECTORY_SEPARATOR .
            md5($url) . 'rest.cacheid';

        if (!file_exists($cacheidfile)) {
            return false;
        }

        $ret = unserialize(implode('', file($cacheidfile)));
        return $ret;
    }

    function getCache($url)
    {
        $cachefile = $this->config->get('cache_dir') . DIRECTORY_SEPARATOR .
            md5($url) . 'rest.cachefile';

        if (!file_exists($cachefile)) {
            return PEAR::raiseError('No cached content available for "' . $url . '"');
        }

        return unserialize(implode('', file($cachefile)));
    }

    /**
     * @param string full URL to REST resource
     * @param string original contents of the REST resource
     * @param array  HTTP Last-Modified and ETag headers
     * @param bool   if true, then the cache id file should be regenerated to
     *               trigger a new time-to-live value
     */
    function saveCache($url, $contents, $lastmodified, $nochange = false, $cacheid = null)
    {
        $cacheidfile = $this->config->get('cache_dir') . DIRECTORY_SEPARATOR .
            md5($url) . 'rest.cacheid';

        $cachefile = $this->config->get('cache_dir') . DIRECTORY_SEPARATOR .
            md5($url) . 'rest.cachefile';

        if ($cacheid === null && $nochange) {
            $cacheid = unserialize(implode('', file($cacheidfile)));
        }

        $fp = @fopen($cacheidfile, 'wb');
        if (!$fp) {
            $cache_dir = $this->config->get('cache_dir');
            if (is_dir($cache_dir)) {
                return false;
            }

            System::mkdir(array('-p', $cache_dir));
            $fp = @fopen($cacheidfile, 'wb');
            if (!$fp) {
                return false;
            }
        }

        if ($nochange) {
            fwrite($fp, serialize(array(
                'age'        => time(),
                'lastChange' => $cacheid['lastChange'],
                ))
            );

            fclose($fp);
            return true;
        }

        fwrite($fp, serialize(array(
            'age'        => time(),
            'lastChange' => $lastmodified,
            ))
        );

        fclose($fp);
        $fp = @fopen($cachefile, 'wb');
        if (!$fp) {
            if (file_exists($cacheidfile)) {
                @unlink($cacheidfile);
            }

            return false;
        }

        fwrite($fp, serialize($contents));
        fclose($fp);
        return true;
    }

    /**
     * Efficiently Download a file through HTTP.  Returns downloaded file as a string in-memory
     * This is best used for small files
     *
     * If an HTTP proxy has been configured (http_proxy PEAR_Config
     * setting), the proxy will be used.
     *
     * @param string  $url       the URL to download
     * @param string  $save_dir  directory to save file in
     * @param false|string|array $lastmodified header values to check against for caching
     *                           use false to return the header values from this download
     * @param false|array $accept Accept headers to send
     * @return string|array  Returns the contents of the downloaded file or a PEAR
     *                       error on failure.  If the error is caused by
     *                       socket-related errors, the error object will
     *                       have the fsockopen error code available through
     *                       getCode().  If caching is requested, then return the header
     *                       values.
     *
     * @access public
     */
    function downloadHttp($url, $lastmodified = null, $accept = false, $channel = false)
    {
        static $redirect = 0;
        // always reset , so we are clean case of error
        $wasredirect = $redirect;
        $redirect = 0;

        $info = parse_url($url);
        if (!isset($info['scheme']) || !in_array($info['scheme'], array('http', 'https'))) {
            return PEAR::raiseError('Cannot download non-http URL "' . $url . '"');
        }

        if (!isset($info['host'])) {
            return PEAR::raiseError('Cannot download from non-URL "' . $url . '"');
        }

        $host   = isset($info['host']) ? $info['host'] : null;
        $port   = isset($info['port']) ? $info['port'] : null;
        $path   = isset($info['path']) ? $info['path'] : null;
        $schema = (isset($info['scheme']) && $info['scheme'] == 'https') ? 'https' : 'http';

        $proxy_host = $proxy_port = $proxy_user = $proxy_pass = '';
        if ($this->config->get('http_proxy')&&
              $proxy = parse_url($this->config->get('http_proxy'))
        ) {
            $proxy_host = isset($proxy['host']) ? $proxy['host'] : null;
            if ($schema === 'https') {
                $proxy_host = 'ssl://' . $proxy_host;
            }

            $proxy_port   = isset($proxy['port']) ? $proxy['port'] : 8080;
            $proxy_user   = isset($proxy['user']) ? urldecode($proxy['user']) : null;
            $proxy_pass   = isset($proxy['pass']) ? urldecode($proxy['pass']) : null;
            $proxy_schema = (isset($proxy['scheme']) && $proxy['scheme'] == 'https') ? 'https' : 'http';
        }

        if (empty($port)) {
            $port = (isset($info['scheme']) && $info['scheme'] == 'https')  ? 443 : 80;
        }

        if (isset($proxy['host'])) {
            $request = "GET $url HTTP/1.1\r\n";
        } else {
            $request = "GET $path HTTP/1.1\r\n";
        }

        $request .= "Host: $host:$port\r\n";
        $ifmodifiedsince = '';
        if (is_array($lastmodified)) {
            if (isset($lastmodified['Last-Modified'])) {
                $ifmodifiedsince = 'If-Modified-Since: ' . $lastmodified['Last-Modified'] . "\r\n";
            }

            if (isset($lastmodified['ETag'])) {
                $ifmodifiedsince .= "If-None-Match: $lastmodified[ETag]\r\n";
            }
        } else {
            $ifmodifiedsince = ($lastmodified ? "If-Modified-Since: $lastmodified\r\n" : '');
        }

        $request .= $ifmodifiedsince .
            "User-Agent: PEAR/1.8.1/PHP/" . PHP_VERSION . "\r\n";

        $username = $this->config->get('username', null, $channel);
        $password = $this->config->get('password', null, $channel);

        if ($username && $password) {
            $tmp = base64_encode("$username:$password");
            $request .= "Authorization: Basic $tmp\r\n";
        }

        if ($proxy_host != '' && $proxy_user != '') {
            $request .= 'Proxy-Authorization: Basic ' .
                base64_encode($proxy_user . ':' . $proxy_pass) . "\r\n";
        }

        if ($accept) {
            $request .= 'Accept: ' . implode(', ', $accept) . "\r\n";
        }

        $request .= "Accept-Encoding:\r\n";
        $request .= "Connection: close\r\n";
        $request .= "\r\n";

        if ($proxy_host != '') {
            $fp = @fsockopen($proxy_host, $proxy_port, $errno, $errstr, 15);
            if (!$fp) {
                return PEAR::raiseError("Connection to `$proxy_host:$proxy_port' failed: $errstr", -9276);
            }
        } else {
            if ($schema === 'https') {
                $host = 'ssl://' . $host;
            }

            $fp = @fsockopen($host, $port, $errno, $errstr);
            if (!$fp) {
                return PEAR::raiseError("Connection to `$host:$port' failed: $errstr", $errno);
            }
        }

        fwrite($fp, $request);

        $headers = array();
        $reply   = 0;
        while ($line = trim(fgets($fp, 1024))) {
            if (preg_match('/^([^:]+):\s+(.*)\s*\\z/', $line, $matches)) {
                $headers[strtolower($matches[1])] = trim($matches[2]);
            } elseif (preg_match('|^HTTP/1.[01] ([0-9]{3}) |', $line, $matches)) {
                $reply = (int)$matches[1];
                if ($reply == 304 && ($lastmodified || ($lastmodified === false))) {
                    return false;
                }

                if (!in_array($reply, array(200, 301, 302, 303, 305, 307))) {
                    return PEAR::raiseError("File $schema://$host:$port$path not valid (received: $line)");
                }
            }
        }

        if ($reply != 200) {
            if (!isset($headers['location'])) {
                return PEAR::raiseError("File $schema://$host:$port$path not valid (redirected but no location)");
            }

            if ($wasredirect > 4) {
                return PEAR::raiseError("File $schema://$host:$port$path not valid (redirection looped more than 5 times)");
            }

            $redirect = $wasredirect + 1;
            return $this->downloadHttp($headers['location'], $lastmodified, $accept, $channel);
        }

        $length = isset($headers['content-length']) ? $headers['content-length'] : -1;

        $data = '';
        while ($chunk = @fread($fp, 8192)) {
            $data .= $chunk;
        }
        fclose($fp);

        if ($lastmodified === false || $lastmodified) {
            if (isset($headers['etag'])) {
                $lastmodified = array('ETag' => $headers['etag']);
            }

            if (isset($headers['last-modified'])) {
                if (is_array($lastmodified)) {
                    $lastmodified['Last-Modified'] = $headers['last-modified'];
                } else {
                    $lastmodified = $headers['last-modified'];
                }
            }

            return array($data, $lastmodified, $headers);
        }

        return $data;
    }
}