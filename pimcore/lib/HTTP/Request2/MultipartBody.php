<?php
/**
 * Helper class for building multipart/form-data request body
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
 * @version    CVS: $Id: MultipartBody.php,v 1.4 2009/01/07 19:28:22 avb Exp $
 * @link       http://pear.php.net/package/HTTP_Request2
 */

/**
 * Class for building multipart/form-data request body
 *
 * The class helps to reduce memory consumption by streaming large file uploads
 * from disk, it also allows monitoring of upload progress (see request #7630)
 *
 * @category   HTTP
 * @package    HTTP_Request2
 * @author     Alexey Borzov <avb@php.net>
 * @version    Release: 0.4.0
 * @link       http://tools.ietf.org/html/rfc1867
 */
class HTTP_Request2_MultipartBody
{
   /**
    * MIME boundary
    * @var  string
    */
    private $_boundary;

   /**
    * Form parameters added via {@link HTTP_Request2::addPostParameter()}
    * @var  array
    */
    private $_params = array();

   /**
    * File uploads added via {@link HTTP_Request2::addUpload()}
    * @var  array
    */
    private $_uploads = array();

   /**
    * Header for parts with parameters
    * @var  string
    */
    private $_headerParam = "--%s\r\nContent-Disposition: form-data; name=\"%s\"\r\n\r\n";

   /**
    * Header for parts with uploads
    * @var  string
    */
    private $_headerUpload = "--%s\r\nContent-Disposition: form-data; name=\"%s\"; filename=\"%s\"\r\nContent-Type: %s\r\n\r\n";

   /**
    * Current position in parameter and upload arrays
    *
    * First number is index of "current" part, second number is position within
    * "current" part
    *
    * @var  array
    */
    private $_pos = array(0, 0);


   /**
    * Constructor. Sets the arrays with POST data.
    *
    * @param    array   values of form fields set via {@link HTTP_Request2::addPostParameter()}
    * @param    array   file uploads set via {@link HTTP_Request2::addUpload()}
    * @param    bool    whether to append brackets to array variable names
    */
    public function __construct(array $params, array $uploads, $useBrackets = true)
    {
        $this->_params = self::_flattenArray('', $params, $useBrackets);
        foreach ($uploads as $fieldName => $f) {
            if (!is_array($f['fp'])) {
                $this->_uploads[] = $f + array('name' => $fieldName);
            } else {
                for ($i = 0; $i < count($f['fp']); $i++) {
                    $upload = array(
                        'name' => ($useBrackets? $fieldName . '[' . $i . ']': $fieldName)
                    );
                    foreach (array('fp', 'filename', 'size', 'type') as $key) {
                        $upload[$key] = $f[$key][$i];
                    }
                    $this->_uploads[] = $upload;
                }
            }
        }
    }

   /**
    * Returns the length of the body to use in Content-Length header
    *
    * @return   integer
    */
    public function getLength()
    {
        $boundaryLength     = strlen($this->getBoundary());
        $headerParamLength  = strlen($this->_headerParam) - 4 + $boundaryLength;
        $headerUploadLength = strlen($this->_headerUpload) - 8 + $boundaryLength;
        $length             = $boundaryLength + 6;
        foreach ($this->_params as $p) {
            $length += $headerParamLength + strlen($p[0]) + strlen($p[1]) + 2;
        }
        foreach ($this->_uploads as $u) {
            $length += $headerUploadLength + strlen($u['name']) + strlen($u['type']) +
                       strlen($u['filename']) + $u['size'] + 2;
        }
        return $length;
    }

   /**
    * Returns the boundary to use in Content-Type header
    *
    * @return   string
    */
    public function getBoundary()
    {
        if (empty($this->_boundary)) {
            $this->_boundary = 'PEAR-HTTP_Request2-' . md5(microtime());
        }
        return $this->_boundary;
    }

   /**
    * Returns next chunk of request body
    *
    * @param    integer Amount of bytes to read
    * @return   string  Up to $length bytes of data, empty string if at end
    */
    public function read($length)
    {
        $ret         = '';
        $boundary    = $this->getBoundary();
        $paramCount  = count($this->_params);
        $uploadCount = count($this->_uploads);
        while ($length > 0 && $this->_pos[0] <= $paramCount + $uploadCount) {
            $oldLength = $length;
            if ($this->_pos[0] < $paramCount) {
                $param = sprintf($this->_headerParam, $boundary, 
                                 $this->_params[$this->_pos[0]][0]) .
                         $this->_params[$this->_pos[0]][1] . "\r\n";
                $ret    .= substr($param, $this->_pos[1], $length);
                $length -= min(strlen($param) - $this->_pos[1], $length);

            } elseif ($this->_pos[0] < $paramCount + $uploadCount) {
                $pos    = $this->_pos[0] - $paramCount;
                $header = sprintf($this->_headerUpload, $boundary,
                                  $this->_uploads[$pos]['name'],
                                  $this->_uploads[$pos]['filename'],
                                  $this->_uploads[$pos]['type']);
                if ($this->_pos[1] < strlen($header)) {
                    $ret    .= substr($header, $this->_pos[1], $length);
                    $length -= min(strlen($header) - $this->_pos[1], $length);
                }
                $filePos  = max(0, $this->_pos[1] - strlen($header));
                if ($length > 0 && $filePos < $this->_uploads[$pos]['size']) {
                    $ret     .= fread($this->_uploads[$pos]['fp'], $length);
                    $length  -= min($length, $this->_uploads[$pos]['size'] - $filePos);
                }
                if ($length > 0) {
                    $start   = $this->_pos[1] + ($oldLength - $length) -
                               strlen($header) - $this->_uploads[$pos]['size'];
                    $ret    .= substr("\r\n", $start, $length);
                    $length -= min(2 - $start, $length);
                }

            } else {
                $closing  = '--' . $boundary . "--\r\n";
                $ret     .= substr($closing, $this->_pos[1], $length);
                $length  -= min(strlen($closing) - $this->_pos[1], $length);
            }
            if ($length > 0) {
                $this->_pos     = array($this->_pos[0] + 1, 0);
            } else {
                $this->_pos[1] += $oldLength;
            }
        }
        return $ret;
    }

   /**
    * Sets the current position to the start of the body
    *
    * This allows reusing the same body in another request
    */
    public function rewind()
    {
        $this->_pos = array(0, 0);
        foreach ($this->_uploads as $u) {
            rewind($u['fp']);
        }
    }

   /**
    * Returns the body as string
    *
    * Note that it reads all file uploads into memory so it is a good idea not
    * to use this method with large file uploads and rely on read() instead.
    *
    * @return   string
    */
    public function __toString()
    {
        $this->rewind();
        return $this->read($this->getLength());
    }


   /**
    * Helper function to change the (probably multidimensional) associative array
    * into the simple one.
    *
    * @param    string  name for item
    * @param    mixed   item's values
    * @param    bool    whether to append [] to array variables' names
    * @return   array   array with the following items: array('item name', 'item value');
    */
    private static function _flattenArray($name, $values, $useBrackets)
    {
        if (!is_array($values)) {
            return array(array($name, $values));
        } else {
            $ret = array();
            foreach ($values as $k => $v) {
                if (empty($name)) {
                    $newName = $k;
                } elseif ($useBrackets) {
                    $newName = $name . '[' . $k . ']';
                } else {
                    $newName = $name;
                }
                $ret = array_merge($ret, self::_flattenArray($newName, $v, $useBrackets));
            }
            return $ret;
        }
    }
}
?>
