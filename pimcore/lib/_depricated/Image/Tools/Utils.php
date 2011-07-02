<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker */

/**
 * This file contains the Image_Tools_Utils class.
 *
 * PHP version 4 and 5
 *
 * LICENSE:
 * Copyright (c) 2008 Firman Wandayandi <firman@php.net>
 * All rights reserved.
 *
 * This source file is subject to the BSD License license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.opensource.org/licenses/bsd-license.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to pear-dev@list.php.net so we can send you a copy immediately.
 *
 * @category    Images
 * @package     Image_Tools
 * @author      Firman Wandayandi <firman@php.net>
 * @copyright   Copyright (c) 2008 Firman Wandayandi <firman@php.net>
 * @license     http://www.opensource.org/licenses/bsd-license.php
 *              BSD License
 * @version     $Id: Utils.php,v 1.1 2008/05/26 05:03:59 firman Exp $
 * @since       File available since Release 1.0.0RC1
 */

/**
 * Image tools utilities class.
 *
 * @category    Images
 * @package     Image_Tools
 * @author      Firman Wandayandi <firman@php.net>
 * @copyright   Copyright (c) 2008 Firman Wandayandi <firman@php.net>
 * @license     http://www.opensource.org/licenses/bsd-license.php
 *              BSD License
 * @version     Release: 1.0.0RC1
 * @since       Class available since Release 1.0.0RC1
 */
class Image_Tools_Utils
{
    // {{{ colorToRGBA()

    /**
     * Convert various color format to RGBA array.
     *
     * @param mixed $color Color value (array, string or integer)
     * @return array|FALSE An RGBA array or FALSE on failure
     * @access public
     * @static
     */
    function colorToRGBA($color)
    {
        if (is_array($color)) {
            if (isset($color['r']) && isset($color['g']) && isset($color['b'])) {
                $color['a'] = isset($color['a']) ? $color['a'] : 0;
                return $color;
            } else if (isset($color[0]) && isset($color[1]) && isset($color[2])) {
                $color[3] = isset($color[3]) ? $color[3] : 0;
                return array(
                    'r' => $color[0],
                    'g' => $color[1],
                    'b' => $color[2],
                    'a' => $color[3]
                );
            }
        } else if (is_string($color)) {
            $regex = '/^[#|]?([a-f0-9]{2})?([a-f0-9]{2})([a-f0-9]{2})([a-f0-9]{2})/i';
            if (preg_match($regex, $color, $matches)) {
                return array(
                    'r' => hexdec($matches[2]),
                    'g' => hexdec($matches[3]),
                    'b' => hexdec($matches[4]),
                    'a' => !empty($matches[1]) ? hexdec($matches[1]) : 0
                );
            }
        } elseif (is_int($color)) {
            return array(
                'r' => ($color >> 16) & 0xff,
                'g' => ($color >> 8) & 0xff,
                'b' => ($color >> 0) & 0xff,
                'a' => ($color >> 24) & 0xff
            );
        }
        return false;
    }

    // }}}
    // {{{ getGDVersion()

    /**
     * Get the loaded GD version.
     *
     * @return string Version
     * @access protected
     * @static
     */
    function getGDVersion()
    {
        $info = gd_info();
        if (preg_match('/\((.+)\)/', $info['GD Version'], $matches)) {
            return $matches[1];
        }
        return false;
    }

    // }}}
    // {{{ compareGDVersion()

    /**
     * Compare the "PHP-standardized" version number string with the
     * current loaded GD.
     *
     * @param string $version
     * @param string $operator
     * @return boolean
     * @static
     */
    function compareGDVersion($version, $operator)
    {
        return version_compare(Image_Tools_Utils::getGDVersion(), $version, $operator);
    }

    // }}}
}
