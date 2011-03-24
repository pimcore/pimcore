<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker: */

// {{{ Header

/**
 * This is a driver file contains the Image_Tools_Border class.
 *
 * PHP versions 4 and 5
 *
 * LICENSE:
 * Copyright (c) 2005-2008 Firman Wandayandi <firman@php.net>
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
 * @copyright   Copyright (c) 2005-2008 Firman Wandayandi <firman@php.net>
 * @license     http://www.opensource.org/licenses/bsd-license.php
 *              BSD License
 * @version     CVS: $Id: Border.php,v 1.7 2008/05/26 06:13:06 firman Exp $
 */

// }}}
// {{{ Dependencies

/**
 * Image_Tools
 */
require_once 'Image/Tools.php';

/**
 * Image_Tools_Utils
 */
require_once 'Image/Tools/Utils.php';

// }}}
// {{{ Class: Image_Tools_Border

/**
 * This class provide border creation on an image.
 *
 * @category    Images
 * @package     Image_Tools
 * @author      Firman Wandayandi <firman@php.net>
 * @copyright   Copyright (c) 2005-2006 Firman Wandayandi <firman@php.net>
 * @license     http://www.opensource.org/licenses/bsd-license.php
 *              BSD License
 * @version     Release: 1.0.0RC1
 */
class Image_Tools_Border extends Image_Tools
{
    // {{{ Properties

    /**
     * Border options:
     * <pre>
     * image    mixed   Destination image, a filename or an image string
     *                  data or a GD image resource.
     * style    string  Border style
     * params   array   Parameters
     * </pre>
     *
     * @var     array
     * @access  protected
     */
    var $options = array(
        'image'  => null,
        'style'  => 'rounded',
        'params' => array()
    );

    /**
     * Available options for Image_Tools_Border.
     *
     * @var     array
     * @access  protected
     */
    var $availableOptions = array(
        'image'  => 'mixed',
        'style'  => 'string',
        'params' => 'array'
    );

    /**
     * Available methods for Image_Tool_Border (only public methods).
     *
     * @var     array
     * @access  protected
     */
    var $availableMethods = array(
        'rounded' => array(
            'radius'        => 'integer',
            'background'    => 'mixed',
            'antiAlias'     => 'integer'
        ),
        'bevel' => array(
            'size'          => 'integer',
            'highlight'     => 'mixed',
            'shadow'        => 'mixed'
        ),
        'line' => array(
            'width'         => 'integer',
            'color'         => 'mixed',
            'offset'        => 'integer',
            'background'    => 'mixed'
        )
    );

    /**
     * Image_Tools_Border API version.
     *
     * @var     string
     * @access  protected
     */
    var $version = '1.0';

    /**
     * Image width.
     *
     * @var     int
     * @access  private
     */
    var $_iWidth = 0;

    /**
     * Image height.
     *
     * @var     int
     * @access  private
     */
    var $_iHeight = 0;

    // }}}
    // {{{ preRender()
    /**
     * Function which called before render.
     *
     * @return  bool|PEAR_Error TRUE on success or PEAR_Error on failure.
     * @access  protected
     */
    function preRender()
    {
        $res = Image_Tools::createImage($this->options['image']);
        if (PEAR::isError($res)) {
            return $res;
        }

        $this->resultImage = $res;
        $this->_iWidth = imagesx($this->resultImage);
        $this->_iHeight = imagesy($this->resultImage);

        return true;
    }

    // }}}
    // {{{ _rounded()

    /**
     * Make an image be a rounded edge.
     *
     * @param   int $radius optional Radius size.
     * @param   mixed $background Background color.
     * @param   int $antiAlias Anti-alias factor.
     *
     * @return  bool|PEAR_Error TRUE on success or PEAR_Error on failure.
     * @access  private
     */
    function _rounded($radius = 3, $background = 'FFFFFF', $antiAlias = 3)
    {
        if (!Image_Tools::isGDImageResource($this->resultImage)) {
            return PEAR::raiseError('Invalid image resource Image_Tools_Mask::$_resultImage');
        }

        $background = Image_Tools_Utils::colorToRGBA($background);
        $antiAlias = min(3, $antiAlias);

        $iDot = imagecreate(1, 1);
        imagecolorallocate($iDot, $background['r'], $background['g'], $background['b']);

        for ($i = 0 - $radius; $i <= $radius; $i++) {
            $y = $i < 0 ? $i + $radius - 1 : $this->_iHeight - ($radius - $i);
            for ($j = 0 - $radius; $j <= $radius; $j++) {
                $x = $j < 0 ? $j + $radius - 1 : $this->_iWidth - ($radius - $j);
                if ($i != 0 || $j != 0) {
                    $distance = round(sqrt(($i * $i) + ($j * $j)));
                    $opacity = $distance < $radius - $antiAlias ?
                               0 : max(0, 100 - (($radius - $distance) * 33));
                    $opacity = $distance > $radius ? 100 : $opacity;
                    imagecopymerge($this->resultImage, $iDot, $x, $y, 0, 0, 1, 1, $opacity);
                }
            }
        }

        imagedestroy($iDot);

        return true;
    }

    // }}}
    // {{{ _bevel()

    /**
     * Make an image bevel border.
     *
     * @param   int $size Border size.
     * @param   mixed $highlight Highlight color.
     * @param   mixed $shadow Shadow color.
     *
     * @return  bool|PEAR_Error TRUE on success or PEAR_Error on failure.
     * @access  private
     */
    function _bevel($size = 8, $highlight = 'FFFFFF', $shadow = '000000')
    {
        if (!Image_Tools::isGDImageResource($this->resultImage)) {
            return PEAR::raiseError('Invalid image resource Image_Tools_Mask::$_resultImage');
        }

        $highlight = Image_Tools_Utils::colorToRGBA($highlight);
        $shadow = Image_Tools_Utils::colorToRGBA($shadow);

        // Create an image resource for highlight.
        $iLight = imagecreate($this->_iWidth, $this->_iHeight);
        imagecolorallocate($iLight, $highlight['r'], $highlight['g'], $highlight['b']);

        // Create an image resource for shadow.
        $iShadow = imagecreate(1, 1);
        imagecolorallocate($iShadow, $shadow['r'], $shadow['g'], $shadow['b']);

        for ($j = 0; $j < $size; $j++) {
            $opacity =  100 - (($j + 1) * (100 / $size));
            imagecopymerge($this->resultImage, $iLight, $j, $j,
                           0, 0, 1, $this->_iHeight - (2 * $j), $opacity);
            imagecopymerge($this->resultImage, $iLight, $j - 1, $j - 1,
                           0, 0, $this->_iWidth - (2 * $j), 1, $opacity);
            imagecopymerge($this->resultImage, $iShadow, $this->_iWidth - ($j + 1), $j,
                           0, 0, 1, $this->_iHeight - (2 * $j), max(0, $opacity - 10));
            imagecopymerge($this->resultImage, $iShadow, $j, $this->_iHeight - ($j + 1),
                           0, 0, $this->_iWidth - (2 * $j), 1, max(0, $opacity - 10));
        }

        // Free highlight and shadow image resources.
        imagedestroy($iLight);
        imagedestroy($iShadow);

        return true;
    }

    // }}}
    // {{{ _line()

    /**
     * Draw a line around an image
     *
     * Draw a line around an image. If $offset is 0, the line is just around the image.
     * If $offset is positive, the line is outside the image, and the gad is filled with background color.
     * If $offset is -width / 2, the line is centered on the border of the image.
     * If $offset is -$width, the line in just inside the image.
     * If $offset is negative, the line is inside the image.
     *
     * @param   int $width Line width
     * @param   mixed $color Line color
     * @param   int $offset Distance between line and image.
     *
     * @return  bool|PEAR_Error TRUE on success or PEAR_Error on failure.
     * @access  private
     * @author  Charles Brunet <charles.fmj@gmail.com>
     */
    function _line($width = 2, $color = '000000', $offset = 0, $background = 'FFFFFF')
    {
        // Agrandir l'image si nécessaire
        $mag = $width + $offset;
        if ($mag > 0) {
            $w= $this->_iWidth + (2 * $mag);
            $h= $this->_iHeight + (2 * $mag);

            // Create the target image
            if ( function_exists('imagecreatetruecolor') ) {
                $target = imagecreatetruecolor($w, $h);
            } else {
                $target = imagecreate($w, $h);
            }
            if ( ! Image_Tools::isGDImageResource($target) ) {
                return PEAR::raiseError('Cannot initialize new GD image stream');
            }
            $background = Image_Tools_Utils::colorToRGBA($background);
            $background = imagecolorallocate($target, $background['r'], $background['g'], $background['b']);
            imagefilledrectangle($target, 0, 0, $w-1, $h-1, $background);
            imagecopy($target, $this->resultImage, $mag, $mag, 0, 0, $this->_iWidth, $this->_iHeight);
            $this->_iWidth = $w;
            $this->_iHeight = $h;
            imagedestroy($this->resultImage);
            $this->resultImage = $target;
        }

        if (!Image_Tools::isGDImageResource($this->resultImage)) {
            return PEAR::raiseError('Invalid image resource Image_Tools_Mask::$_resultImage');
        }

        $color = Image_Tools_Utils::colorToRGBA($color);
        $color = imagecolorallocate($this->resultImage, $color['r'], $color['g'], $color['b']);

        $a = ($mag < 0)?0-$mag:0;
        for ($i=$a; $i<$a+$width; ++$i) {
            imagerectangle($this->resultImage, $i, $i, $this->_iWidth-$i-1, $this->_iHeight-$i-1, $color);
        }

        return true;
    }

    // }}}
    // {{{ render()

    /**
     * This method is useless, use directly call for specific border style
     * method.
     *
     * @return  TRUE|PEAR_Error
     * @access  protected
     */
    function render()
    {
        $callback = array($this, "_{$this->options['style']}");
        if (!is_callable($callback)) {
            return PEAR::raiseError('Invalid border style or not supported');
        }

        return call_user_func_array($callback, $this->options['params']);
    }

    // }}}
}

// }}}

/*
 * Local variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
?>
